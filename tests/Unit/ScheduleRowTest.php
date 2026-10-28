<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\Schedule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Schedules as stored in form_schedules (+ form_schedule_dates) and as the browser uses them. */
final class ScheduleRowTest extends TestCase
{
    private const EMPTY = ['type' => 'daily', 'days' => [], 'month_day' => 1, 'year_md' => '', 'dates' => [], 'start' => '', 'end' => '', 'deadline' => '', 'reminder' => false];

    public static function schedules(): array
    {
        return [
            'daily with deadline and reminder' => [array_replace(self::EMPTY, ['deadline' => '14:00', 'reminder' => true])],
            'weekly Senin + Kamis' => [array_replace(self::EMPTY, ['type' => 'weekly', 'days' => [1, 4]])],
            'weekly Minggu + Sabtu' => [array_replace(self::EMPTY, ['type' => 'weekly', 'days' => [0, 6]])],
            'monthly day 31' => [array_replace(self::EMPTY, ['type' => 'monthly', 'month_day' => 31])],
            'monthly last day' => [array_replace(self::EMPTY, ['type' => 'monthly', 'month_day' => 'last'])],
            'yearly 17 Agu' => [array_replace(self::EMPTY, ['type' => 'yearly', 'year_md' => '08-17'])],
            'dates' => [array_replace(self::EMPTY, ['type' => 'dates', 'dates' => ['2026-11-01', '2026-12-24']])],
            'range' => [array_replace(self::EMPTY, ['start' => '2026-10-27', 'end' => '2026-12-31'])],
        ];
    }

    #[DataProvider('schedules')]
    public function testRowRoundTrip(array $schedule): void
    {
        [$row, $dates] = Schedule::toRow($schedule);
        self::assertSame($schedule, Schedule::fromRow($row, $dates));
    }

    public function testRowKeepsOnlyTheFieldsOfItsType(): void
    {
        [$row, $dates] = Schedule::toRow(['type' => 'daily', 'days' => [1], 'month_day' => 5, 'year_md' => '01-02', 'dates' => ['2026-01-01'], 'deadline' => '', 'reminder' => true]);
        self::assertSame(['type' => 'daily', 'weekday_mask' => 0, 'month_day' => null, 'month_last_day' => 0, 'yearly_month' => null, 'yearly_day' => null,
            'start_date' => null, 'end_date' => null, 'deadline_time' => null, 'reminder' => 0], $row);
        self::assertSame([], $dates);
        self::assertSame(0b0010010, Schedule::toRow(['type' => 'weekly', 'days' => [1, 4]])[0]['weekday_mask']);
        self::assertSame('14:00:00', Schedule::toRow(['type' => 'daily', 'deadline' => '14:00'])[0]['deadline_time']);
    }

    public function testRowFromTheDatabaseReadsTimeAndDates(): void
    {
        $schedule = Schedule::fromRow(['type' => 'dates', 'weekday_mask' => '0', 'month_day' => null, 'month_last_day' => '0', 'deadline_time' => '16:30:00', 'reminder' => '1', 'start_date' => null, 'end_date' => null], ['2026-12-24', '2026-11-01']);
        self::assertSame(['2026-11-01', '2026-12-24'], $schedule['dates']);
        self::assertSame('16:30', $schedule['deadline']);
        self::assertTrue($schedule['reminder']);
        self::assertSame(self::EMPTY, Schedule::fromRow(['type' => 'daily']));
    }

    public function testProblems(): void
    {
        self::assertSame([], Schedule::problems(self::EMPTY));
        self::assertSame(['type'], array_keys(Schedule::problems(['type' => 'hourly'])));
        self::assertSame(['days'], array_keys(Schedule::problems(['type' => 'weekly', 'days' => []])));
        self::assertSame(['days'], array_keys(Schedule::problems(['type' => 'weekly', 'days' => [7]])));
        self::assertSame(['month_day'], array_keys(Schedule::problems(['type' => 'monthly', 'month_day' => 32])));
        self::assertSame([], Schedule::problems(['type' => 'monthly', 'month_day' => 'last']));
        self::assertSame(['year_md'], array_keys(Schedule::problems(['type' => 'yearly', 'year_md' => '02-30'])));
        self::assertSame([], Schedule::problems(['type' => 'yearly', 'year_md' => '02-29']));
        self::assertSame(['dates'], array_keys(Schedule::problems(['type' => 'dates', 'dates' => ['2026-02-30']])));
        self::assertSame(['end'], array_keys(Schedule::problems(['type' => 'daily', 'start' => '2026-10-27', 'end' => '2026-10-01'])));
        self::assertSame(['start'], array_keys(Schedule::problems(['type' => 'daily', 'start' => '27-10-2026'])));
        self::assertSame(['deadline'], array_keys(Schedule::problems(['type' => 'daily', 'deadline' => '24:00'])));
    }
}
