<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\FormRecap;
use App\Services\RecapExport;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/** PRD §9 (B8): limits, file names, report IDs, typed export rows, the Excel workbook and the PDF parts. */
final class RecapExportTest extends TestCase
{
    private static function at(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-01 03:15:00', new DateTimeZone('UTC'));
    }

    private static function report(array $changes = []): array
    {
        return $changes + [
            'module' => 'rekap-suhu', 'title' => 'Rekap Suhu Server Bulanan', 'name' => 'Suhu Server', 'from' => '2026-10-01', 'to' => '2026-10-31',
            'period' => 'Oktober 2026', 'filters' => [['label' => 'Status suhu', 'value' => 'Kritis']],
            'stats' => [['label' => 'Rata-rata suhu', 'value' => '27,5 °C', 'note' => '1 entri']],
            'columns' => [
                ['label' => 'No. entri', 'type' => 'text'], ['label' => 'Periode', 'type' => 'text'], ['label' => 'Waktu pengambilan', 'type' => 'datetime'],
                ['label' => 'Lokasi', 'type' => 'text'], ['label' => 'Suhu (°C)', 'type' => 'number1'], ['label' => 'Status suhu', 'type' => 'text'],
                ['label' => 'Kelembapan (% RH)', 'type' => 'number1'], ['label' => 'Status kelembapan', 'type' => 'text'], ['label' => 'Bukti', 'type' => 'text'], ['label' => 'Catatan', 'type' => 'text'],
            ],
            'total' => 1,
            'rows' => [['cells' => ['TMP-202610-0001', 'Oktober 2026', RecapExport::time('2026-10-15 03:18:00'), 'Ruang Server Utama', 27.5, 'Kritis', null, null, null, 'AC <mati>']]],
        ];
    }

    public function testLimitsPerFormat(): void
    {
        self::assertNull(RecapExport::tooMany('pdf', 2000));
        self::assertSame('Maksimal 2.000 baris untuk PDF. Persempit filter.', RecapExport::tooMany('pdf', 2001));
        self::assertSame('Maksimal 2.000 baris untuk PDF. Persempit filter.', RecapExport::tooMany('cetak', 2001));
        self::assertNull(RecapExport::tooMany('xlsx', 50000));
        self::assertSame('Maksimal 50.000 baris untuk Excel. Persempit filter.', RecapExport::tooMany('xlsx', 50001));
    }

    public function testFileNameUsesPeriodAndWibTime(): void
    {
        self::assertSame('rekap-suhu_20261001-20261031_202611011015.xlsx', RecapExport::fileName('rekap-suhu', '2026-10-01', '2026-10-31', self::at(), 'xlsx'));
        // 31 Oct 2026 20:00 UTC is already 1 Nov in WIB.
        self::assertSame('rekap-cctv_20261001-20261031_202611010300.pdf', RecapExport::fileName('rekap-cctv', '2026-10-01', '2026-10-31', new DateTimeImmutable('2026-10-31 20:00:00', new DateTimeZone('UTC')), 'pdf'));
    }

    public function testReportIdDependsOnFiltersAndTime(): void
    {
        $id = RecapExport::reportId(['periode' => '2026-10', 'status' => 'kritis'], self::at());
        self::assertMatchesRegularExpression('/^RPT-[0-9A-F]{10}$/', $id);
        self::assertSame($id, RecapExport::reportId(['status' => 'kritis', 'periode' => '2026-10'], self::at()), 'order of the filters does not matter');
        self::assertNotSame($id, RecapExport::reportId(['periode' => '2026-10'], self::at()));
        self::assertNotSame($id, RecapExport::reportId(['periode' => '2026-10', 'status' => 'kritis'], self::at()->modify('+1 second')));
    }

    public function testPeriodText(): void
    {
        self::assertSame('Oktober 2026', RecapExport::period('2026-10', '2026-10-01', '2026-10-31'));
        self::assertSame('3 Okt 2026 – 9 Nov 2026', RecapExport::period(null, '2026-10-03', '2026-11-09'));
        self::assertSame('Semua periode', RecapExport::period(null, null, null));
        self::assertSame('Tanpa filter', RecapExport::filterText([]));
        self::assertSame('Status: Kritis; Zona: UGD & Triase', RecapExport::filterText([['label' => 'Status', 'value' => 'Kritis'], ['label' => 'Zona', 'value' => 'UGD & Triase']]));
    }

    public function testRowsAreReadInChunksUntilAShortPage(): void
    {
        $pages = [];
        $source = static function (int $page, int $per) use (&$pages): array {
            $pages[] = $page;
            return $page < 3 ? array_fill(0, $per, ['n' => $page]) : [['n' => 3]];
        };
        $rows = iterator_to_array(RecapExport::rows($source, static fn (array $r): array => ['cells' => [$r['n']]]), false);
        self::assertCount(1001, $rows);
        self::assertSame([1, 2, 3], $pages);
    }

    public function testDynamicFormNumbersStayNumbers(): void
    {
        $items = [
            ['id' => 'suhu', 'type' => 'number', 'title' => 'Suhu rak'],
            ['id' => 'jumlah', 'type' => 'number', 'title' => 'Jumlah server'],
            ['id' => 'kondisi', 'type' => 'radio', 'title' => 'Kondisi', 'options' => [['value' => 'baik', 'label' => 'Baik']]],
            ['id' => 'ok', 'type' => 'confirm', 'title' => 'Pernyataan'],
        ];
        self::assertSame(['text', 'datetime', 'text', 'number', 'number', 'text'], array_column(FormRecap::exportColumns($items), 'type'));
        $cells = FormRecap::exportRow([
            'entry_no' => 'HWS-202610-0001', 'submitted_at' => '2026-10-15 01:00:00', 'attention' => 1,
            'definition_json' => json_encode(['questions' => $items]), 'answers_json' => json_encode(['suhu' => 26.5, 'jumlah' => '4', 'kondisi' => 'baik', 'ok' => true]),
        ], $items)['cells'];
        self::assertSame(['HWS-202610-0001', 'Ya', 26.5, 4.0, 'Baik'], [$cells[0], $cells[2], $cells[3], $cells[4], $cells[5]]);
        self::assertSame([['label' => 'Laporan', 'value' => 'Perlu perhatian']], FormRecap::filterLabels(FormRecap::filters(['perhatian' => 'ya'])));
    }

    public function testPdfPartsHaveHeaderFooterSignatureAndEscapedText(): void
    {
        $parts = RecapExport::pdfParts(self::report(), self::at(), 'RPT-0123456789', 'Admin IT');
        self::assertStringContainsString('RS Awal Bros Botania — Instalasi Teknologi Informasi', $parts['header']);
        self::assertStringContainsString('Periode: Oktober 2026 · Filter: Status suhu: Kritis', $parts['header']);
        self::assertStringContainsString('Halaman {PAGENO} dari {nbpg}', $parts['footer']);
        self::assertStringContainsString('Dicetak 1 Nov 2026, 10:15 WIB', $parts['footer']);
        self::assertStringContainsString('RPT-0123456789', $parts['footer']);
        $body = implode('', $parts['body']);
        self::assertStringContainsString('<thead>', $body);
        self::assertStringContainsString('15/10/2026 10:18', $body);
        self::assertStringContainsString('27,5', $body);
        self::assertStringContainsString('AC &lt;mati&gt;', $body);
        self::assertStringContainsString('Diketahui oleh,<br>Kepala Instalasi IT', $body);
        self::assertStringContainsString('Admin IT', $body);
        self::assertSame(1, $parts['rows']);
    }

    public function testClearDeskPdfGroupsBySessionWithSubtotals(): void
    {
        $row = static fn (string $session, bool $ok): array => ['cells' => [$session], 'group' => $session, 'group_title' => 'Sesi ' . $session, 'ok' => $ok];
        $parts = RecapExport::pdfParts(self::report([
            'columns' => [['label' => 'No. sesi', 'type' => 'text']], 'grouped' => true, 'total' => 3,
            'rows' => [$row('CDS-2', true), $row('CDS-1', false), $row('CDS-2', false)],
        ]), self::at(), 'RPT-X', 'Admin');
        $body = implode('', $parts['body']);
        self::assertStringContainsString('Subtotal: 2 workstation diperiksa · Patuh 1 · Temuan 1', $body);
        self::assertStringContainsString('Subtotal: 1 workstation diperiksa · Patuh 0 · Temuan 1', $body);
        self::assertLessThan(strpos($body, 'Sesi CDS-1'), strpos($body, 'Sesi CDS-2'), 'sessions in the order they appear');
        self::assertSame(3, $parts['rows']);
    }

    public function testEmptyPdfSaysSo(): void
    {
        $parts = RecapExport::pdfParts(self::report(['rows' => [], 'total' => 0]), self::at(), 'RPT-X', '');
        self::assertStringContainsString('Tidak ada data untuk filter ini.', implode('', $parts['body']));
        self::assertSame(0, $parts['rows']);
    }

    public function testWorkbookHasDataAndSummarySheets(): void
    {
        if (!class_exists(ZipArchive::class)) {
            self::markTestSkipped('ZipArchive tidak aktif di PHP ini.');
        }
        $path = tempnam(sys_get_temp_dir(), 'rx') . '.xlsx';
        self::assertSame(1, RecapExport::xlsx(self::report(), $path, self::at()));
        $zip = new ZipArchive();
        $zip->open($path);
        $workbook = (string) $zip->getFromName('xl/workbook.xml');
        $data = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $summary = (string) $zip->getFromName('xl/worksheets/sheet2.xml');
        $styles = (string) $zip->getFromName('xl/styles.xml');
        $zip->close();
        @unlink($path);
        self::assertMatchesRegularExpression('/<sheet name="Data".*<sheet name="Ringkasan"/', $workbook);
        self::assertStringContainsString('state="frozen"', $data);
        self::assertStringContainsString('<autoFilter ref="A1:J2"/>', $data);
        self::assertStringContainsString('formatCode="dd/mm/yyyy hh:mm"', $styles);
        self::assertStringContainsString('formatCode="0.0"', $styles);
        self::assertMatchesRegularExpression('#<c r="E2" s="\d+"><v>27.5</v></c>#', $data, 'temperature is a number');
        self::assertMatchesRegularExpression('#<c r="C2" s="\d+"><v>46310\.4291#', $data, 'time is a date serial (15 Oct 2026 10:18)');
        self::assertStringContainsString('Status suhu: Kritis', $summary);
        self::assertStringContainsString('RS Awal Bros Botania', $summary);
        self::assertStringContainsString('Rata-rata suhu', $summary);
    }
}
