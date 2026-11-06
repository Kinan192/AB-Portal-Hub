<?php
declare(strict_types=1);

namespace App\Services;

use App\Helpers\Schedule;
use DateTimeImmutable;

/**
 * Dashboard progress from the saved reports (B7, PRD §7). One rule for every figure: a form counts when its
 * card is in a "forms" section, not hidden, and due today by its schedule (App\Helpers\Schedule::isDue); it
 * is done when a report is dated today (done_today).
 * The hero ring is the sum of the section counters, so both always show the same numbers. The browser
 * recomputes the same in public/assets/js/dashboard.js after a card changes.
 */
final class Progress
{
    /**
     * Counter of each forms section: visible cards, due today, done of those.
     * @param list<array{id: string, kind: string}> $sections
     * @param list<array> $cards cards with section, hidden, schedule, done_today
     * @return array<string, array{visible: int, due: int, done: int}>
     */
    public static function sections(array $sections, array $cards, DateTimeImmutable $now): array
    {
        $counts = [];
        foreach ($sections as $section) {
            if ($section['kind'] !== 'forms') {
                continue;
            }
            $visible = array_filter($cards, static fn (array $c): bool => $c['section'] === $section['id'] && empty($c['hidden']));
            $due = array_filter($visible, static fn (array $c): bool => isset($c['schedule']) && Schedule::isDue($c['schedule'], $now));
            $counts[$section['id']] = [
                'visible' => count($visible),
                'due' => count($due),
                'done' => count(array_filter($due, static fn (array $c): bool => !empty($c['done_today']))),
            ];
        }
        return $counts;
    }

    /**
     * The hero ring: the section counters added up.
     * @param array<string, array{due: int, done: int}> $sections
     * @return array{completed: int, target: int, remaining: int, percentage: int, ratio: float}
     */
    public static function summary(array $sections): array
    {
        $target = array_sum(array_column($sections, 'due'));
        $completed = array_sum(array_column($sections, 'done'));
        return [
            'completed' => $completed,
            'target' => $target,
            'remaining' => $target - $completed,
            'percentage' => $target > 0 ? (int) round($completed / $target * 100) : 0,
            'ratio' => $target > 0 ? $completed / $target : 0.0,
        ];
    }
}
