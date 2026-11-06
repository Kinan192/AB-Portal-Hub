<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\Schedule;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** B7: when a form is due (every schedule type) and the card's footer status (PRD §7). */
final class ScheduleTest extends TestCase
{
    private static function day(string $ymd, string $hm = '10:00'): DateTimeImmutable
    {
        return new DateTimeImmutable($ymd . ' ' . $hm);
    }

    private static function schedule(array $fields): array
    {
        return $fields + ['type' => 'daily', 'days' => [], 'month_day' => 1, 'year_md' => '', 'dates' => [], 'start' => '', 'end' => '', 'deadline' => '', 'reminder' => false];
    }

    public static function dueDays(): array
    {
        return [
            'daily' => [['type' => 'daily'], ['2026-10-27' => true, '2026-02-28' => true]],
            'weekly Mon + Thu' => [['type' => 'weekly', 'days' => [1, 4]], ['2026-10-26' => true, '2026-10-27' => false, '2026-10-29' => true, '2026-11-01' => false]],
            'weekly Sunday (0)' => [['type' => 'weekly', 'days' => [0]], ['2026-11-01' => true, '2026-10-31' => false]],
            'monthly day 1' => [['type' => 'monthly', 'month_day' => 1], ['2026-10-01' => true, '2026-10-02' => false]],
            'monthly day 31 in February → 28' => [['type' => 'monthly', 'month_day' => 31], ['2026-02-28' => true, '2026-02-27' => false, '2026-03-31' => true, '2026-04-30' => true, '2026-04-29' => false]],
            'monthly day 30 in a leap February → 29' => [['type' => 'monthly', 'month_day' => 30], ['2028-02-29' => true, '2028-02-28' => false]],
            'monthly last day' => [['type' => 'monthly', 'month_day' => 'last'], ['2026-02-28' => true, '2028-02-29' => true, '2028-02-28' => false, '2026-10-31' => true, '2026-10-30' => false]],
            'yearly 17 Aug' => [['type' => 'yearly', 'year_md' => '08-17'], ['2026-08-17' => true, '2026-08-18' => false]],
            'yearly 29 Feb (leap years only)' => [['type' => 'yearly', 'year_md' => '02-29'], ['2028-02-29' => true, '2026-02-28' => false, '2026-03-01' => false]],
            'dates list' => [['type' => 'dates', 'dates' => ['2026-10-27', '2026-12-01']], ['2026-10-27' => true, '2026-12-01' => true, '2026-10-28' => false]],
            'start date' => [['type' => 'daily', 'start' => '2026-10-27'], ['2026-10-26' => false, '2026-10-27' => true]],
            'end date (inclusive)' => [['type' => 'daily', 'end' => '2026-10-27'], ['2026-10-27' => true, '2026-10-28' => false]],
            'optional is never due' => [['type' => 'optional'], ['2026-10-27' => false, '2026-02-28' => false]],
            'weekly inside start..end' => [['type' => 'weekly', 'days' => [2], 'start' => '2026-10-20', 'end' => '2026-11-03'], ['2026-10-13' => false, '2026-10-20' => true, '2026-11-03' => true, '2026-11-10' => false]],
        ];
    }

    /** @param array<string, bool> $days */
    #[DataProvider('dueDays')]
    public function testIsDue(array $fields, array $days): void
    {
        foreach ($days as $ymd => $due) {
            self::assertSame($due, Schedule::isDue(self::schedule($fields), self::day($ymd)), $ymd);
        }
    }

    public function testNextDue(): void
    {
        self::assertSame('2026-11-02', Schedule::nextDue(self::schedule(['type' => 'weekly', 'days' => [1]]), self::day('2026-10-27'))?->format('Y-m-d'));
        self::assertSame('2026-11-30', Schedule::nextDue(self::schedule(['type' => 'monthly', 'month_day' => 'last']), self::day('2026-10-31'))?->format('Y-m-d'));
        self::assertSame('2028-02-29', Schedule::nextDue(self::schedule(['type' => 'yearly', 'year_md' => '02-29']), self::day('2026-10-27'))?->format('Y-m-d'));
        self::assertNull(Schedule::nextDue(self::schedule(['type' => 'daily', 'end' => '2026-10-27']), self::day('2026-10-27')));
        self::assertNull(Schedule::nextDue(self::schedule(['type' => 'dates', 'dates' => ['2026-10-01']]), self::day('2026-10-27')));
    }

    public function testCardStatus(): void
    {
        $daily = self::schedule(['type' => 'daily', 'deadline' => '14:00', 'reminder' => true]);
        $status = static fn (array $s, bool $done, bool $data, string $at, ?array $period = null): array => Schedule::status($s, $done, $data, new DateTimeImmutable($at), $period);
        self::assertSame(['text' => 'Belum ada data', 'tone' => 'muted', 'due' => true], $status($daily, false, false, '2026-10-27 09:00'));
        self::assertSame(['text' => 'Batas 14:00', 'tone' => 'plain', 'due' => true], $status($daily, false, true, '2026-10-27 13:59'));
        self::assertSame(['text' => 'Belum Diisi', 'tone' => 'warn', 'due' => true], $status($daily, false, true, '2026-10-27 14:01'));
        self::assertSame(['text' => 'Selesai', 'tone' => 'ok', 'due' => true], $status($daily, true, true, '2026-10-27 15:00'));
        self::assertSame('Batas 14:00', $status(['reminder' => false] + $daily, false, true, '2026-10-27 15:00')['text']); // no reminder: no warning
        self::assertSame('Hari ini', $status(self::schedule([]), false, true, '2026-10-27 15:00')['text']);
        self::assertSame(['text' => 'Berikutnya: 2 Nov', 'tone' => 'next', 'due' => false], $status(self::schedule(['type' => 'weekly', 'days' => [1]]), false, true, '2026-10-27 09:00'));
        self::assertSame('Tidak terjadwal', $status(self::schedule(['end' => '2026-10-26']), false, true, '2026-10-27 09:00')['text']);
        // Suhu (decision Q12): the month's locations instead of today.
        self::assertSame(['text' => '3/4 lokasi', 'tone' => 'plain', 'due' => false], $status(self::schedule(['type' => 'monthly', 'month_day' => 1]), false, true, '2026-10-27 09:00', ['done' => false, 'text' => '3/4 lokasi']));
        self::assertSame('Selesai', $status(self::schedule(['type' => 'monthly', 'month_day' => 1]), true, true, '2026-10-27 09:00', ['done' => true, 'text' => '4/4 lokasi'])['text']);
        // Tidak wajib diisi: no reminder, open any time.
        $optional = self::schedule(['type' => 'optional', 'deadline' => '14:00', 'reminder' => true]);
        self::assertSame(['text' => 'Kapan saja', 'tone' => 'next', 'due' => false], $status($optional, false, false, '2026-10-27 15:00'));
        self::assertSame(['text' => 'Selesai', 'tone' => 'ok', 'due' => false], $status($optional, true, true, '2026-10-27 15:00'));
    }

    public function testOptionalScheduleIsValidAndKeepsNoDeadline(): void
    {
        self::assertSame([], Schedule::problems(self::schedule(['type' => 'optional'])));
        self::assertSame(['text' => 'Tidak wajib', 'tone' => 'slate'], Schedule::label(self::schedule(['type' => 'optional'])));
        [$row] = Schedule::toRow(self::schedule(['type' => 'optional', 'deadline' => '14:00', 'reminder' => true]));
        self::assertSame(['optional', null, 0], [$row['type'], $row['deadline_time'], $row['reminder']]);
        self::assertNull(Schedule::nextDue(self::schedule(['type' => 'optional']), self::day('2026-10-27')));
    }
}
