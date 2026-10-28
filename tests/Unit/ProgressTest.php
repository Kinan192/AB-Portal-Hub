<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Progress;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/** B7: dashboard progress from the reports; the hero ring always equals the section counters (PRD §7). */
final class ProgressTest extends TestCase
{
    private const SECTIONS = [['id' => 'harian', 'kind' => 'forms'], ['id' => 'lain', 'kind' => 'forms'], ['id' => 'laporan', 'kind' => 'links']];

    private static function card(string $section, array $schedule, bool $done = false, bool $hidden = false): array
    {
        return ['section' => $section, 'hidden' => $hidden, 'done_today' => $done, 'schedule' => $schedule + ['type' => 'daily']];
    }

    public function testSectionCountersAndHeroAreTheSame(): void
    {
        $now = new DateTimeImmutable('2026-10-27 09:00'); // a Tuesday
        $cards = [
            self::card('harian', ['type' => 'daily'], true),                    // due, done
            self::card('harian', ['type' => 'daily']),                          // due
            self::card('harian', ['type' => 'weekly', 'days' => [1]], true),    // not due today (done does not count)
            self::card('harian', ['type' => 'daily'], true, true),              // hidden: left out everywhere
            self::card('lain', ['type' => 'dates', 'dates' => ['2026-10-27']]), // due in another forms section
            ['section' => 'laporan', 'hidden' => false, 'done_today' => false],  // a link card
        ];
        $sections = Progress::sections(self::SECTIONS, $cards, $now);
        self::assertSame(['harian' => ['visible' => 3, 'due' => 2, 'done' => 1], 'lain' => ['visible' => 1, 'due' => 1, 'done' => 0]], $sections);
        $hero = Progress::summary($sections);
        self::assertSame([1, 3, 2, 33], [$hero['completed'], $hero['target'], $hero['remaining'], $hero['percentage']]);
        self::assertSame(array_sum(array_column($sections, 'due')), $hero['target']);
        self::assertSame(array_sum(array_column($sections, 'done')), $hero['completed']);
        self::assertSame(['completed' => 0, 'target' => 0, 'remaining' => 0, 'percentage' => 0, 'ratio' => 0.0], Progress::summary([]));
    }

    public function testExceptionsNewestFirstFrP6(): void
    {
        $row = static fn (string $no, string $at, string $tone = 'warn'): array => ['module' => 'M', 'entry_no' => $no, 'at' => $at, 'status' => 'Peringatan', 'tone' => $tone, 'url' => '/x'];
        $list = Progress::exceptions([
            $row('TMP-1', '2026-10-01 01:00:00'), $row('CCTV-1', '2026-10-27 02:00:00', 'bad'), $row('CDS-1', '2026-10-26 03:00:00'),
            $row('HWS-1', '2026-10-27 01:00:00'), $row('TMP-2', '2026-10-20 01:00:00'), $row('F7-1', '2026-10-25 01:00:00'),
        ]);
        self::assertSame(['CCTV-1', 'HWS-1', 'CDS-1', 'F7-1', 'TMP-2'], array_column($list, 'entry_no'));
        self::assertSame('27 Okt 2026, 09:00 WIB', $list[0]['when']);
        self::assertSame([], Progress::exceptions([]));
    }
}
