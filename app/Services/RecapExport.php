<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\View;
use App\Helpers\Format;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Generator;
use Mpdf\Mpdf;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;
use Throwable;

/**
 * Exports of the recap pages (PRD §9, B8): Excel (sheets "Data" and "Ringkasan"), PDF (A4 landscape) and
 * Cetak (the same PDF opened in the browser with the print dialog). Each recap controller builds a report:
 *   module    file name start, e.g. "rekap-suhu"
 *   title     "Rekap Suhu Ruang Server" · name: module name on the Ringkasan sheet
 *   from, to  first and last day "Y-m-d" (file name) · period: text, e.g. "Oktober 2026"
 *   filters   list<array{label, value}> applied filters (period left out) · stats: the four recap cards
 *   columns   list<array{label, type: text|int|number|number1|datetime}>
 *   total     rows matching the filters (checked against the limits before any row is read)
 *   rows      iterable of array{cells: list<mixed>, group?: string, group_title?: string, ok?: bool}: values
 *             typed by column (int, float, DateTimeImmutable in WIB, string or null)
 *   grouped   true for Clear Desk: the PDF groups rows by 'group' (the session) with a subtotal of Patuh
 *             ('ok') and Temuan rows (FR-E10)
 *   error     a filter error of the page (nothing is exported)
 */
final class RecapExport
{
    public const FORMATS = ['xlsx', 'pdf', 'cetak'];
    /** Rows read from the database at a time. */
    private const CHUNK = 500;

    /** FR-E12: most rows of a format. */
    public static function limit(string $format): int
    {
        return (int) (config('report')['limits'][$format] ?? 0);
    }

    /** The message when a filtered set is too large for a format, or null (FR-E12). */
    public static function tooMany(string $format, int $total): ?string
    {
        $limit = self::limit($format);
        if ($total <= $limit) {
            return null;
        }
        return 'Maksimal ' . number_format($limit, 0, ',', '.') . ' baris untuk ' . ($format === 'xlsx' ? 'Excel' : 'PDF') . '. Persempit filter.';
    }

    /** FR-E4: rekap-suhu_20261001-20261031_202611011015.xlsx (the time in WIB). */
    public static function fileName(string $module, string $from, string $to, DateTimeImmutable $at, string $extension): string
    {
        $day = static fn (string $ymd): string => str_replace('-', '', $ymd);
        return $module . '_' . $day($from) . '-' . $day($to) . '_' . self::wib($at)->format('YmdHi') . '.' . $extension;
    }

    /** FR-E9: an ID made from the filters and the time, so a printed copy can be traced in the activity log. */
    public static function reportId(array $query, DateTimeImmutable $at): string
    {
        ksort($query);
        return 'RPT-' . strtoupper(substr(hash('sha256', json_encode($query, JSON_UNESCAPED_UNICODE) . '|' . $at->format('U.u')), 0, 10));
    }

    /** "Oktober 2026", "1 Okt 2026 – 15 Nov 2026", or "Semua periode". */
    public static function period(?string $month, ?string $from, ?string $to): string
    {
        if ($month !== null && $month !== '') {
            return Format::monthName($month . '-01');
        }
        if ($from !== null && $to !== null && $from !== '' && $to !== '') {
            return Format::date($from) . ' – ' . Format::date($to);
        }
        return 'Semua periode';
    }

    /** A UTC "Y-m-d H:i:s" from the database as a WIB date-time cell value. */
    public static function time(?string $utc): ?DateTimeImmutable
    {
        if ($utc === null || $utc === '') {
            return null;
        }
        return self::wib(new DateTimeImmutable($utc, new DateTimeZone('UTC')));
    }

    /**
     * Every row of a filtered set, read in chunks: $page(int $page, int $perPage) returns database rows and
     * $map(array $row) one export row. The database query orders by a unique column last, so chunks don't overlap.
     */
    public static function rows(callable $page, callable $map): Generator
    {
        for ($n = 1; ; $n++) {
            $rows = $page($n, self::CHUNK);
            foreach ($rows as $row) {
                yield $map($row);
            }
            if (count($rows) < self::CHUNK) {
                return;
            }
        }
    }

    /**
     * GET …/ekspor?format=xlsx|pdf|cetak&<filters>: checks the format, the filters and the limits, writes the
     * file, records it in the activity log (FR-E13) and sends it.
     */
    public static function send(string $format, array $report, array $query): void
    {
        if (!in_array($format, self::FORMATS, true)) {
            self::refuse('Format ekspor tidak dikenal. Pilih Excel, PDF, atau Cetak.', 404);
            return;
        }
        if (($report['error'] ?? null) !== null) {
            self::refuse('Filter tidak valid: ' . rtrim($report['error'], '.') . '. Perbaiki filter di halaman rekap lalu ekspor lagi.', 422);
            return;
        }
        $tooMany = self::tooMany($format, (int) $report['total']);
        if ($tooMany !== null) {
            self::refuse($tooMany, 422);
            return;
        }
        if ($format === 'xlsx' && !class_exists(\ZipArchive::class)) {
            self::refuse('Ekspor Excel memerlukan ekstensi PHP zip. Aktifkan extension=zip di php.ini lalu jalankan ulang server.', 503);
            return;
        }
        @set_time_limit(300);
        $at = new DateTimeImmutable('now');
        unset($query['format'], $query['halaman'], $query['per']);
        $reportId = self::reportId($query, $at);
        $extension = $format === 'xlsx' ? 'xlsx' : 'pdf';
        $name = self::fileName($report['module'], (string) $report['from'], (string) $report['to'], $at, $extension);
        try {
            if ($format === 'xlsx') {
                $path = self::tempDir() . '/' . bin2hex(random_bytes(8)) . '.xlsx';
                $written = self::xlsx($report, $path, $at);
                $body = (string) file_get_contents($path);
                @unlink($path);
            } else {
                $user = Auth::user();
                [$body, $written] = self::pdf($report, $at, $reportId, trim((string) ($user['name'] ?? '')) ?: (string) ($user['username'] ?? ''), $format === 'cetak');
            }
            Audit::log('export', 'rekap', null, [
                'module' => $report['module'],
                'format' => $format,
                'filters' => (object) $query,
                'rows' => $written,
                'report_id' => $reportId,
                'file' => $name,
            ]);
        } catch (Throwable $exception) {
            error_log('Ekspor ' . $report['module'] . ' (' . $format . '): ' . $exception->getMessage());
            self::refuse('Ekspor gagal dibuat. Coba lagi; bila tetap gagal, persempit filter.', 500);
            return;
        }
        header_remove('Content-Security-Policy');
        header('Content-Type: ' . ($format === 'xlsx' ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' : 'application/pdf'));
        header('Content-Length: ' . strlen($body));
        header('Content-Disposition: ' . ($format === 'cetak' ? 'inline' : 'attachment') . '; filename="' . $name . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        echo $body;
    }

    /**
     * FR-E1–E3: sheet "Data" (header frozen, auto-filter, fitted widths, typed cells) and sheet "Ringkasan".
     * @return int rows written
     */
    public static function xlsx(array $report, string $path, DateTimeImmutable $at): int
    {
        $columns = $report['columns'];
        $writer = new Writer();
        $writer->openToFile($path);
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Data');
        $sheet->setSheetView((new SheetView())->setFreezeRow(2));
        $header = (new Style())->setFontBold()->setBackgroundColor('E8EEF7')->setShouldWrapText(false);
        $styles = [
            'datetime' => (new Style())->setFormat('dd/mm/yyyy hh:mm'),
            'number1' => (new Style())->setFormat('0.0'),
            'number' => (new Style())->setFormat('General'),
            'int' => (new Style())->setFormat('0'),
        ];
        $widths = array_map(static fn (array $c): int => mb_strlen($c['label']) + 4, $columns);
        $writer->addRow(Row::fromValues(array_column($columns, 'label'), $header));
        $count = 0;
        foreach ($report['rows'] as $row) {
            $cells = [];
            foreach ($columns as $i => $column) {
                $value = $row['cells'][$i] ?? null;
                $cells[] = Cell::fromValue($value, $value === null ? null : ($styles[$column['type']] ?? null));
                $widths[$i] = max($widths[$i], match (true) {
                    $value instanceof DateTimeInterface => 18,
                    is_string($value) => min(60, mb_strlen($value) + 2),
                    $value === null => 0,
                    default => strlen((string) $value) + 4,
                });
            }
            $writer->addRow(new Row($cells));
            $count++;
        }
        foreach ($widths as $i => $width) {
            $sheet->setColumnWidth(min(60, $width), $i + 1);
        }
        $sheet->setAutoFilter(new AutoFilter(0, 1, max(0, count($columns) - 1), $count + 1));

        $summary = $writer->addNewSheetAndMakeItCurrent();
        $summary->setName('Ringkasan');
        $label = (new Style())->setFontBold();
        $config = config('report');
        $lines = [
            ['Rumah sakit', $config['hospital']],
            ['Modul', $report['name']],
            ['Periode', $report['period']],
            ['Filter', self::filterText($report['filters'])],
            ['Dibuat', Format::dateTime($at->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'))],
            ['Jumlah baris', $count],
        ];
        foreach ($lines as [$name, $value]) {
            $writer->addRow(new Row([Cell::fromValue($name, $label), Cell::fromValue($value)]));
        }
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(new Row([Cell::fromValue('Statistik', $label), Cell::fromValue('Nilai', $label), Cell::fromValue('Keterangan', $label)]));
        foreach ($report['stats'] as $stat) {
            $writer->addRow(Row::fromValues([$stat['label'], $stat['value'], $stat['note'] ?? '']));
        }
        $summary->setColumnWidth(26, 1);
        $summary->setColumnWidth(48, 2);
        $summary->setColumnWidth(40, 3);
        $writer->close();
        return $count;
    }

    /**
     * FR-E5–E11: the report as a PDF; with $print the PDF opens the print dialog (Cetak).
     * @return array{0: string, 1: int} the PDF bytes and the rows written
     */
    public static function pdf(array $report, DateTimeImmutable $at, string $reportId, string $author, bool $print): array
    {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 34,
            'margin_bottom' => 18,
            'margin_header' => 10,
            'margin_footer' => 8,
            'tempDir' => self::tempDir() . '/mpdf',
            'fontDir' => [BASE_PATH . '/resources/fonts'],
            'fontdata' => ['plex' => ['R' => 'IBMPlexSans-Regular.ttf', 'B' => 'IBMPlexSans-Bold.ttf']],
            'default_font' => 'plex',
            'default_font_size' => 9,
            'simpleTables' => true,
            'packTableData' => true,
        ]);
        $mpdf->SetTitle($report['title'] . ' — ' . $report['period']);
        $mpdf->SetAuthor(config('report')['hospital']);
        $mpdf->SetCreator('AB Portal Hub');
        $parts = self::pdfParts($report, $at, $reportId, $author);
        $mpdf->SetHTMLHeader($parts['header']);
        $mpdf->SetHTMLFooter($parts['footer']);
        $mpdf->WriteHTML($parts['css'], \Mpdf\HTMLParserMode::HEADER_CSS);
        foreach ($parts['body'] as $chunk) {
            $mpdf->WriteHTML($chunk, \Mpdf\HTMLParserMode::HTML_BODY);
        }
        if ($print) {
            $mpdf->SetJS('this.print();');
        }
        return [$mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN), $parts['rows']];
    }

    /**
     * The HTML of the PDF: header (every page), footer (every page, X of Y), and the body as chunks (statistics,
     * the table with its header repeated on every page, Clear Desk groups with subtotals, the signature block).
     * @return array{css: string, header: string, footer: string, body: list<string>, rows: int}
     */
    public static function pdfParts(array $report, DateTimeImmutable $at, string $reportId, string $author): array
    {
        $config = config('report');
        $printed = Format::dateTime($at->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'));
        $date = Format::date(self::wib($at)->format('Y-m-d'));
        $filters = self::filterText($report['filters']);
        $logo = is_file($config['logo']) ? '<img src="' . e($config['logo']) . '" height="11mm">' : '';
        $css = 'body { font-family: plex; font-size: 9pt; color: #1f2937; }
            .head td { vertical-align: middle; } .org { font-size: 8pt; color: #4b5563; } .title { font-size: 13pt; font-weight: bold; color: #0b3a6f; }
            .meta { font-size: 8pt; color: #374151; } .rule { border-bottom: 0.4mm solid #0b3a6f; }
            .stats { width: 100%; border-collapse: separate; border-spacing: 2mm 0; margin: 0 -2mm 4mm -2mm; }
            .stats td { border: 0.2mm solid #cbd5e1; padding: 2mm 3mm; width: 25%; vertical-align: top; }
            .stat-label { font-size: 7.5pt; color: #4b5563; } .stat-value { font-size: 12pt; font-weight: bold; } .stat-note { font-size: 7pt; color: #6b7280; }
            table.data { width: 100%; border-collapse: collapse; }
            table.data th { background-color: #e8eef7; font-weight: bold; text-align: left; padding: 1.4mm 1.6mm; border: 0.2mm solid #94a3b8; font-size: 8pt; }
            table.data td { padding: 1.2mm 1.6mm; border: 0.2mm solid #cbd5e1; vertical-align: top; }
            table.data td.num { text-align: right; } .group td { background-color: #f1f5f9; font-weight: bold; } .subtotal td { font-size: 8pt; color: #374151; background-color: #f8fafc; }
            .empty { padding: 6mm; text-align: center; color: #6b7280; border: 0.2mm solid #cbd5e1; }
            .sign { width: 100%; margin-top: 10mm; page-break-inside: avoid; } .sign td { width: 50%; vertical-align: top; text-align: center; }
            .sign-space { height: 18mm; } .sign-name { font-weight: bold; }';
        $header = '<table width="100%" class="head"><tr>'
            . '<td width="70mm">' . $logo . '</td>'
            . '<td><div class="org">' . e($config['hospital'] . ' — ' . $config['unit']) . '</div>'
            . '<div class="title">' . e($report['title']) . '</div>'
            . '<div class="meta">Periode: ' . e($report['period']) . ' · Filter: ' . e($filters) . '</div></td>'
            . '</tr></table><div class="rule"></div>';
        $footer = '<table width="100%" class="meta"><tr>'
            . '<td>Dicetak ' . e($printed) . ' · Halaman {PAGENO} dari {nbpg}</td>'
            . '<td align="right">ID laporan ' . e($reportId) . '</td>'
            . '</tr></table>';

        $body = [];
        $stats = '<table class="stats"><tr>';
        foreach ($report['stats'] as $stat) {
            $stats .= '<td><div class="stat-label">' . e($stat['label']) . '</div><div class="stat-value">' . e($stat['value']) . '</div>'
                . (($stat['note'] ?? '') !== '' ? '<div class="stat-note">' . e($stat['note']) . '</div>' : '') . '</td>';
        }
        $body[] = $stats . '</tr></table>';

        $columns = $report['columns'];
        $head = '<table class="data" repeat_header="1"><thead><tr>';
        foreach ($columns as $column) {
            $head .= '<th>' . e($column['label']) . '</th>';
        }
        $head .= '</tr></thead><tbody>';
        $line = static function (array $cells) use ($columns): string {
            $html = '<tr>';
            foreach ($columns as $i => $column) {
                $value = $cells[$i] ?? null;
                $numeric = in_array($column['type'], ['int', 'number', 'number1'], true);
                $html .= '<td' . ($numeric ? ' class="num"' : '') . '>' . nl2br(e(self::text($value, $column['type']))) . '</td>';
            }
            return $html . '</tr>';
        };
        $count = 0;
        $span = count($columns);
        if (!empty($report['grouped'])) {
            // FR-E10: Clear Desk rows by session, each with its subtotal, in the order the sessions appear.
            $groups = [];
            $info = [];
            foreach ($report['rows'] as $row) {
                $groups[$row['group']][] = $row['cells'];
                $info[$row['group']] ??= ['title' => $row['group_title'] ?? (string) $row['group'], 'ok' => 0];
                $info[$row['group']]['ok'] += empty($row['ok']) ? 0 : 1;
                $count++;
            }
            $html = $head;
            foreach ($groups as $key => $rows) {
                $html .= '<tr class="group"><td colspan="' . $span . '">' . e($info[$key]['title']) . '</td></tr>';
                foreach ($rows as $cells) {
                    $html .= $line($cells);
                }
                $ok = $info[$key]['ok'];
                $html .= '<tr class="subtotal"><td colspan="' . $span . '">Subtotal: ' . count($rows) . ' workstation diperiksa · Patuh ' . $ok . ' · Temuan ' . (count($rows) - $ok) . '</td></tr>';
            }
            $body[] = $html . '</tbody></table>';
        } else {
            $html = $head;
            foreach ($report['rows'] as $row) {
                $html .= $line($row['cells']);
                $count++;
            }
            $body[] = $html . '</tbody></table>';
        }
        if ($count === 0) {
            $body[count($body) - 1] = '<div class="empty">Tidak ada data untuk filter ini.</div>';
        }
<<<<<<< HEAD
        // Texts set on the recap page ("Tanda tangan"); an empty left name is the exporting account, an empty right
        // name a line to sign on.
        $sign = ($report['signature'] ?? []) + ReportSignature::defaults();
        $head = static fn (string $title, string $role): string => e($title) . ($role !== '' ? '<br>' . e($role) : '');
        $leftName = $sign['left_name'] !== '' ? $sign['left_name'] : ($author !== '' ? $author : 'Admin');
        $body[] = '<table class="sign"><tr>'
            . '<td>' . $head($sign['left_title'], $sign['left_role']) . '<div class="sign-space"></div><div class="sign-name">' . e($leftName) . '</div><div>' . e($date) . '</div></td>'
            . '<td>' . $head($sign['right_title'], $sign['right_role']) . '<div class="sign-space"></div><div class="sign-name">' . ($sign['right_name'] !== '' ? e($sign['right_name']) : '(______________________________)') . '</div></td>'
=======
        $name = $config['head_of_it_name'];
        $body[] = '<table class="sign"><tr>'
            . '<td>Dibuat oleh,<div class="sign-space"></div><div class="sign-name">' . e($author !== '' ? $author : 'Admin') . '</div><div>' . e($date) . '</div></td>'
            . '<td>Diketahui oleh,<br>Kepala Instalasi IT<div class="sign-space"></div><div class="sign-name">' . ($name !== '' ? e($name) : '(______________________________)') . '</div></td>'
>>>>>>> 899d036f59ce2361fefc2c1a8249a7b80cbc5bb5
            . '</tr></table>';
        return ['css' => $css, 'header' => $header, 'footer' => $footer, 'body' => $body, 'rows' => $count];
    }

    /** The filters as one line: "Status: Kritis; Lokasi: Ruang Server Utama", or "Tanpa filter". */
    public static function filterText(array $filters): string
    {
        $parts = array_map(static fn (array $f): string => $f['label'] . ': ' . $f['value'], $filters);
        return $parts === [] ? 'Tanpa filter' : implode('; ', $parts);
    }

    /** A cell value as PDF text: 15/10/2026 10:18, 21,5, … */
    public static function text(mixed $value, string $type): string
    {
        return match (true) {
            $value === null => '',
            $value instanceof DateTimeInterface => $value->format('d/m/Y H:i'),
            $type === 'number1' => Format::decimal((float) $value),
            $type === 'number' => rtrim(rtrim(number_format((float) $value, 6, ',', '.'), '0'), ','),
            default => (string) $value,
        };
    }

    private static function refuse(string $message, int $status): void
    {
        http_response_code($status);
        View::render('errors/design-unavailable', [
            'pageTitle' => 'Ekspor tidak dapat dibuat',
            'errorTitle' => 'Ekspor tidak dapat dibuat',
            'errorDescription' => $message,
        ], 'design');
    }

    private static function tempDir(): string
    {
        $dir = BASE_PATH . '/storage/app/tmp';
        if (!is_dir($dir . '/mpdf')) {
            @mkdir($dir . '/mpdf', 0770, true);
        }
        return $dir;
    }

    private static function wib(DateTimeImmutable $at): DateTimeImmutable
    {
        return $at->setTimezone(new DateTimeZone('Asia/Jakarta'));
    }
}
