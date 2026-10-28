<?php
declare(strict_types=1);

namespace App\Services;

use App\Helpers\Format;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Recap of a form, drawn by public/assets/js/rekap.js: filters of the URL, columns (one per
 * question of the form as it is now, the declaration left out), rows (an answer is shown with the question of
 * the entry's own version when it still has it), statistic cards and the detail fields. Query parameters:
 *   periode=YYYY-MM or dari=YYYY-MM-DD&sampai=YYYY-MM-DD (at most 366 days; default: every period)
 *   perhatian=ya|tidak · q=<words> · urut=entry_no|waktu · arah=asc|desc · per=25|50|100 · halaman=<n>
 */
final class FormRecap
{
    public const SORTS = ['entry_no', 'waktu'];

    /** @return array{from: ?string, to: ?string, month: ?string, period: string, range_from: string, range_to: string, attention: string, q: string, sort: string, dir: string, per: int, page: int, error: ?string} */
    public static function filters(array $query): array
    {
        $text = static fn (string $key): string => is_string($query[$key] ?? null) ? trim($query[$key]) : '';
        $error = null;
        $month = null;
        $from = $to = null;
        $rangeFrom = $text('dari');
        $rangeTo = $text('sampai');
        if ($rangeFrom !== '' || $rangeTo !== '') {
            $a = self::date($rangeFrom);
            $b = self::date($rangeTo);
            if ($a === null || $b === null) {
                $error = 'Isi tanggal awal dan akhir rentang.';
            } elseif ($b < $a) {
                $error = 'Tanggal akhir tidak boleh sebelum tanggal awal.';
            } elseif ($a->diff($b)->days > 366) {
                $error = 'Rentang maksimal 366 hari';
            } else {
                $from = $a->format('Y-m-d');
                $to = $b->format('Y-m-d');
            }
            if ($error !== null) {
                $rangeFrom = $rangeTo = '';
            }
        } elseif (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $text('periode')) === 1) {
            $month = $text('periode');
            $from = $month . '-01';
            $to = (new DateTimeImmutable($from))->format('Y-m-t');
        }
        $per = (int) $text('per');
        return [
            'from' => $from,
            'to' => $to,
            'month' => $month,
            'period' => $month ?? '',
            'range_from' => $from !== null && $month === null ? $rangeFrom : '',
            'range_to' => $from !== null && $month === null ? $rangeTo : '',
            'attention' => in_array($text('perhatian'), ['ya', 'tidak'], true) ? $text('perhatian') : '',
            'q' => mb_substr($text('q'), 0, 100),
            'sort' => in_array($text('urut'), self::SORTS, true) ? $text('urut') : 'waktu',
            'dir' => $text('arah') === 'asc' ? 'asc' : 'desc',
            'per' => in_array($per, [25, 50, 100], true) ? $per : 25,
            'page' => max(1, (int) $text('halaman')),
            'error' => $error,
        ];
    }

    /** Columns: entry number, time, then the questions of the current version (no declaration). */
    public static function columns(array $currentItems): array
    {
        $columns = [
            ['key' => 'entry_no', 'label' => 'No. entri', 'sort' => 'entry_no'],
            ['key' => 'waktu', 'label' => 'Waktu kirim', 'sort' => 'waktu'],
        ];
        foreach ($currentItems as $q) {
            if ($q['type'] !== 'confirm') {
                $columns[] = ['key' => 'q:' . $q['id'], 'label' => $q['title']];
            }
        }
        return $columns;
    }

    /** One row; the answers are shown with the question of the entry's version (else the current one). */
    public static function row(array $entry, array $currentItems): array
    {
        $own = [];
        foreach (FormDefinition::answerItems(json_decode((string) $entry['definition_json'], true) ?: []) as $q) {
            $own[$q['id']] = $q;
        }
        $answers = json_decode((string) $entry['answers_json'], true) ?: [];
        $attention = (bool) $entry['attention'];
        $cells = [
            'entry_no' => ['text' => $entry['entry_no']],
            'waktu' => ['text' => Format::dateTime($entry['submitted_at'])] + ($attention ? ['chip' => 'Perlu perhatian', 'tone' => 'warn'] : []),
        ];
        foreach ($currentItems as $q) {
            if ($q['type'] === 'confirm') {
                continue;
            }
            $shown = array_key_exists($q['id'], $answers) ? FormAnswers::display($own[$q['id']] ?? $q, $answers[$q['id']]) : null;
            $cells['q:' . $q['id']] = $shown === null ? null : array_intersect_key($shown, array_flip(['text', 'chip', 'tone'])) + ['clamp' => true];
        }
        return [
            'id' => (int) $entry['id'],
            'cells' => $cells,
            'attention' => $attention,
        ];
    }

    /** Columns of the exports (PRD §9): entry, time, attention, then the questions (numbers as numbers). */
    public static function exportColumns(array $currentItems): array
    {
        $columns = [
            ['label' => 'No. entri', 'type' => 'text'],
            ['label' => 'Waktu kirim', 'type' => 'datetime'],
            ['label' => 'Perlu perhatian', 'type' => 'text'],
        ];
        foreach ($currentItems as $q) {
            if ($q['type'] !== 'confirm') {
                $columns[] = ['label' => $q['title'], 'type' => match ($q['type']) {
                    'number', 'scale', 'rating' => 'number',
                    default => 'text',
                }];
            }
        }
        return $columns;
    }

    /** One export row, typed by exportColumns(); an answer of another type in the entry's version stays text. */
    public static function exportRow(array $entry, array $currentItems): array
    {
        $own = [];
        foreach (FormDefinition::answerItems(json_decode((string) $entry['definition_json'], true) ?: []) as $q) {
            $own[$q['id']] = $q;
        }
        $answers = json_decode((string) $entry['answers_json'], true) ?: [];
        $cells = [$entry['entry_no'], RecapExport::time($entry['submitted_at']), $entry['attention'] ? 'Ya' : 'Tidak'];
        foreach ($currentItems as $q) {
            if ($q['type'] === 'confirm') {
                continue;
            }
            $asked = $own[$q['id']] ?? $q;
            $value = $answers[$q['id']] ?? null;
            if (in_array($q['type'], ['number', 'scale', 'rating'], true) && $asked['type'] === $q['type'] && is_numeric($value)) {
                $cells[] = (float) $value;
                continue;
            }
            $shown = $value !== null ? FormAnswers::display($asked, $value) : null;
            $cells[] = $shown['text'] ?? null;
        }
        return ['cells' => $cells];
    }

    /** The applied filters in words (FR-E3, FR-E6), e.g. "Laporan: Perlu perhatian". */
    public static function filterLabels(array $filters): array
    {
        $labels = [];
        if ($filters['attention'] !== '') {
            $labels[] = ['label' => 'Laporan', 'value' => $filters['attention'] === 'ya' ? 'Perlu perhatian' : 'Tanpa catatan'];
        }
        if ($filters['q'] !== '') {
            $labels[] = ['label' => 'Pencarian', 'value' => '"' . $filters['q'] . '"'];
        }
        return $labels;
    }

    /** The four statistic cards: shown, this month, needing a look, last sent. */
    public static function stats(array $stats): array
    {
        return [
            ['label' => 'Laporan ditampilkan', 'value' => (string) $stats['count'], 'note' => ''],
            ['label' => 'Bulan ini', 'value' => (string) $stats['month'], 'note' => ''],
            ['label' => 'Perlu perhatian', 'value' => $stats['count'] === 0 ? '–' : (string) $stats['attention'], 'note' => '', 'tone' => $stats['attention'] > 0 ? 'warn' : ''],
            ['label' => 'Terakhir dikirim', 'value' => $stats['latest'] !== null ? Format::dateTime($stats['latest']) : '–', 'note' => ''],
        ];
    }

    /**
     * Detail fields of an entry, with its own version's questions: section titles as headings, every question
     * with its answer, and one field per uploaded file (with its link).
     */
    public static function fields(array $definition, array $answers): array
    {
        $fields = [];
        foreach ($definition['questions'] ?? [] as $q) {
            if (!is_array($q)) {
                continue;
            }
            if (($q['type'] ?? '') === 'section') {
                $fields[] = ['heading' => $q['title']];
                continue;
            }
            if (in_array($q['type'] ?? '', ['title', 'image'], true)) {
                continue;
            }
            $shown = array_key_exists($q['id'], $answers) ? FormAnswers::display($q, $answers[$q['id']]) : null;
            $label = $q['type'] === 'confirm' ? 'Pernyataan' : $q['title'];
            if ($shown === null) {
                $fields[] = ['label' => $label, 'text' => '—'];
            } elseif (isset($shown['files'])) {
                foreach ($shown['files'] as $i => $file) {
                    $fields[] = ['label' => count($shown['files']) > 1 ? $label . ' (' . ($i + 1) . ')' : $label, 'text' => (string) $file['name'], 'link' => url('/berkas/' . (int) $file['id'])];
                }
            } else {
                $fields[] = ['label' => $label] + array_intersect_key($shown, array_flip(['text', 'chip', 'tone']));
            }
        }
        return $fields;
    }

    /** Answers as the form page fills them in when changing an entry (option values, "Lainnya" text). */
    public static function prefill(array $items, array $answers): array
    {
        $prefill = [];
        foreach ($items as $q) {
            if (!array_key_exists($q['id'], $answers) || $q['type'] === 'file') {
                continue;
            }
            $value = $answers[$q['id']];
            $prefill[$q['id']] = match ($q['type']) {
                'confirm' => '1',
                'number', 'scale', 'rating' => str_replace('.', ',', (string) $value),
                default => $value,
            };
        }
        return $prefill;
    }

    private static function date(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));
        return $date !== false && $date->format('Y-m-d') === $value ? $date : null;
    }
}
