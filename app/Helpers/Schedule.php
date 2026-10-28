<?php
declare(strict_types=1);

namespace App\Helpers;

use DateTimeImmutable;

/**
 * Schedule rules of the dashboard form cards (same rules as the design's store.js). A schedule is
 * ['type' => daily|weekly|monthly|yearly|dates, 'days' => [0–6, 0 = Minggu], 'month_day' => 1–31|'last',
 *  'year_md' => 'MM-DD', 'dates' => ['YYYY-MM-DD'], 'start' => 'YYYY-MM-DD'|'', 'end' => …, 'deadline' => 'HH:MM'|'', 'reminder' => bool].
 * Dates are WIB calendar dates (the app's time zone).
 */
final class Schedule
{
    private const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    private const DAY_SHORT = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    private const DAY_LONG = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    /**
     * Badge of a form card: Harian · Mingguan - Senin / Sen, Kam · Bulanan - Tgl 1 / Akhir bulan ·
     * Tahunan - 17 Agu · Tanggal tertentu (same text as scheduleLabel() in dashboard-store.js).
     * @return array{text: string, tone: 'teal'|'blue'|'amber'|'purple'|'slate'}
     */
    public static function label(array $schedule): array
    {
        switch ($schedule['type'] ?? 'daily') {
            case 'weekly':
                $days = array_map('intval', $schedule['days'] ?? []);
                usort($days, static fn (int $a, int $b): int => (($a + 6) % 7) <=> (($b + 6) % 7));
                if ($days === []) {
                    return ['text' => 'Mingguan', 'tone' => 'blue'];
                }
                $names = count($days) === 1 ? self::DAY_LONG[$days[0]] : implode(', ', array_map(static fn (int $d): string => self::DAY_SHORT[$d], $days));
                return ['text' => 'Mingguan - ' . $names, 'tone' => 'blue'];
            case 'monthly':
                return ['text' => 'Bulanan - ' . (($schedule['month_day'] ?? 1) === 'last' ? 'Akhir bulan' : 'Tgl ' . (int) ($schedule['month_day'] ?: 1)), 'tone' => 'amber'];
            case 'yearly':
                $parts = explode('-', (string) ($schedule['year_md'] ?? ''));
                $text = count($parts) === 2 ? ' - ' . (int) $parts[1] . ' ' . self::MONTHS[(int) $parts[0] - 1] : '';
                return ['text' => 'Tahunan' . $text, 'tone' => 'purple'];
            case 'dates':
                return ['text' => 'Tanggal tertentu', 'tone' => 'slate'];
            default:
                return ['text' => 'Harian', 'tone' => 'teal'];
        }
    }

    /**
     * A form_schedules row (+ its form_schedule_dates) as the schedule array above. Every key is set,
     * so the browser's schedule editor always gets the full shape.
     * @param list<string> $dates 'YYYY-MM-DD'
     */
    public static function fromRow(array $row, array $dates = []): array
    {
        $type = (string) ($row['type'] ?? 'daily');
        $mask = (int) ($row['weekday_mask'] ?? 0);
        $month = $row['yearly_month'] ?? null;
        $day = $row['yearly_day'] ?? null;
        sort($dates, SORT_STRING);
        return [
            'type' => $type,
            'days' => array_values(array_filter(range(0, 6), static fn (int $d): bool => ($mask & (1 << $d)) !== 0)),
            'month_day' => !empty($row['month_last_day']) ? 'last' : (int) ($row['month_day'] ?? 0 ?: 1),
            'year_md' => $month !== null && $day !== null ? sprintf('%02d-%02d', (int) $month, (int) $day) : '',
            'dates' => $type === 'dates' ? array_values($dates) : [],
            'start' => (string) ($row['start_date'] ?? ''),
            'end' => (string) ($row['end_date'] ?? ''),
            'deadline' => isset($row['deadline_time']) && $row['deadline_time'] !== '' ? substr((string) $row['deadline_time'], 0, 5) : '',
            'reminder' => !empty($row['reminder']),
        ];
    }

    /**
     * The columns of form_schedules for a schedule array, and its dates (type 'dates' only). Only the
     * fields of the chosen type are kept. The schedule is expected to be valid (see problems()).
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    public static function toRow(array $schedule): array
    {
        $type = (string) ($schedule['type'] ?? 'daily');
        $mask = 0;
        if ($type === 'weekly') {
            foreach ($schedule['days'] ?? [] as $d) {
                $mask |= 1 << (int) $d;
            }
        }
        $last = $type === 'monthly' && ($schedule['month_day'] ?? 1) === 'last';
        $md = $type === 'yearly' ? explode('-', (string) ($schedule['year_md'] ?? '')) : [];
        $deadline = (string) ($schedule['deadline'] ?? '');
        $row = [
            'type' => $type,
            'weekday_mask' => $mask,
            'month_day' => $type === 'monthly' && !$last ? (int) ($schedule['month_day'] ?? 1) : null,
            'month_last_day' => $last ? 1 : 0,
            'yearly_month' => count($md) === 2 ? (int) $md[0] : null,
            'yearly_day' => count($md) === 2 ? (int) $md[1] : null,
            'start_date' => ($schedule['start'] ?? '') !== '' ? (string) $schedule['start'] : null,
            'end_date' => ($schedule['end'] ?? '') !== '' ? (string) $schedule['end'] : null,
            'deadline_time' => $deadline !== '' ? $deadline . ':00' : null,
            'reminder' => $deadline !== '' && !empty($schedule['reminder']) ? 1 : 0,
        ];
        $dates = $type === 'dates' ? array_values(array_unique(array_map('strval', $schedule['dates'] ?? []))) : [];
        sort($dates, SORT_STRING);
        return [$row, $dates];
    }

    /**
     * What is wrong with a schedule array from the browser, keyed by field ('' = nothing). Checks the
     * type, weekdays, day of month, MM-DD, dates, start/end and HH:MM deadline.
     * @return array<string, string>
     */
    public static function problems(array $schedule): array
    {
        $errors = [];
        $type = $schedule['type'] ?? null;
        if (!in_array($type, ['daily', 'weekly', 'monthly', 'yearly', 'dates'], true)) {
            return ['type' => 'Pilih jenis jadwal.'];
        }
        $isDate = static function (mixed $value): bool {
            if (!is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
                return false;
            }
            [$y, $m, $d] = array_map('intval', explode('-', $value));
            return checkdate($m, $d, $y);
        };
        if ($type === 'weekly') {
            $days = $schedule['days'] ?? null;
            if (!is_array($days) || $days === [] || array_filter($days, static fn (mixed $d): bool => !is_int($d) || $d < 0 || $d > 6) !== []) {
                $errors['days'] = 'Pilih minimal satu hari.';
            }
        }
        if ($type === 'monthly') {
            $md = $schedule['month_day'] ?? null;
            if ($md !== 'last' && (!is_int($md) || $md < 1 || $md > 31)) {
                $errors['month_day'] = 'Pilih tanggal 1–31 atau akhir bulan.';
            }
        }
        if ($type === 'yearly') {
            $ymd = $schedule['year_md'] ?? null;
            if (!is_string($ymd) || preg_match('/^(\d{2})-(\d{2})$/', $ymd, $m) !== 1 || !checkdate((int) $m[1], (int) $m[2], 2024)) {
                $errors['year_md'] = 'Pilih tanggal dan bulan.';
            }
        }
        if ($type === 'dates') {
            $dates = $schedule['dates'] ?? null;
            if (!is_array($dates) || $dates === [] || count($dates) > 366 || array_filter($dates, static fn (mixed $d): bool => !$isDate($d)) !== []) {
                $errors['dates'] = 'Tambahkan minimal satu tanggal yang benar.';
            }
        }
        foreach (['start', 'end'] as $field) {
            $value = $schedule[$field] ?? '';
            if ($value !== '' && !$isDate($value)) {
                $errors[$field] = 'Tanggal tidak valid.';
            }
        }
        if (!isset($errors['start']) && !isset($errors['end']) &&($schedule['start'] ?? '') !== '' && ($schedule['end'] ?? '') !== '' && $schedule['end'] < $schedule['start']) {
            $errors['end'] = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
        }
        $deadline = $schedule['deadline'] ?? '';
        if ($deadline !== '' && (!is_string($deadline) || preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $deadline) !== 1)) {
            $errors['deadline'] = 'Isi jam dengan format JJ:MM.';
        }
        return $errors;
    }

    public static function isDue(array $schedule, DateTimeImmutable $day): bool
    {
        $key = $day->format('Y-m-d');
        if (($schedule['start'] ?? '') !== '' && $key < $schedule['start']) {
            return false;
        }
        if (($schedule['end'] ?? '') !== '' && $key > $schedule['end']) {
            return false;
        }
        $lastDay = (int) $day->format('t');
        return match ($schedule['type'] ?? 'daily') {
            'weekly' => in_array((int) $day->format('w'), array_map('intval', $schedule['days'] ?? []), true),
            'monthly' => ($schedule['month_day'] ?? 1) === 'last'
                ? (int) $day->format('j') === $lastDay
                : (int) $day->format('j') === min(max(1, (int) ($schedule['month_day'] ?? 1)), $lastDay),
            'yearly' => ($schedule['year_md'] ?? '') !== '' && $day->format('m-d') === $schedule['year_md'],
            'dates' => in_array($key, $schedule['dates'] ?? [], true),
            default => true,
        };
    }

    /** First due day after $from, looking up to about two years ahead. */
    public static function nextDue(array $schedule, DateTimeImmutable $from): ?DateTimeImmutable
    {
        $day = $from->setTime(0, 0)->modify('+1 day');
        for ($i = 0; $i < 800; $i++, $day = $day->modify('+1 day')) {
            if (self::isDue($schedule, $day)) {
                return $day;
            }
        }
        return null;
    }

    /**
     * Footer status of a form card today: Belum ada data · Berikutnya: 3 Nov · Tidak terjadwal · Selesai
     * · Belum Diisi (past the deadline with the reminder on) · Batas 14:00 · Hari ini. With $period
     * (['done' => bool, 'text' => '3/4 lokasi']): Selesai, or the period's text (same as cardStatus() in JS).
     * @return array{text: string, tone: 'muted'|'next'|'ok'|'warn'|'plain', due: bool}
     */
    public static function status(array $schedule, bool $doneToday, bool $hasData, DateTimeImmutable $now, ?array $period = null): array
    {
        $due = self::isDue($schedule, $now);
        // A module counted per period (temperature: this month's locations) shows its own progress.
        if ($period !== null) {
            return !empty($period['done'])
                ? ['text' => 'Selesai', 'tone' => 'ok', 'due' => $due]
                : ['text' => (string) $period['text'], 'tone' => 'plain', 'due' => $due];
        }
        if (!$hasData && !$doneToday) {
            return ['text' => 'Belum ada data', 'tone' => 'muted', 'due' => $due];
        }
        if (!$due) {
            $next = self::nextDue($schedule, $now);
            $text = $next !== null ? 'Berikutnya: ' . (int) $next->format('j') . ' ' . self::MONTHS[(int) $next->format('n') - 1] : 'Tidak terjadwal';
            return ['text' => $text, 'tone' => 'next', 'due' => false];
        }
        if ($doneToday) {
            return ['text' => 'Selesai', 'tone' => 'ok', 'due' => true];
        }
        $deadline = (string) ($schedule['deadline'] ?? '');
        if ($deadline !== '' && !empty($schedule['reminder']) && $now->format('H:i') > $deadline) {
            return ['text' => 'Belum Diisi', 'tone' => 'warn', 'due' => true];
        }
        if ($deadline !== '') {
            return ['text' => 'Batas ' . $deadline, 'tone' => 'plain', 'due' => true];
        }
        return ['text' => 'Hari ini', 'tone' => 'plain', 'due' => true];
    }
}
