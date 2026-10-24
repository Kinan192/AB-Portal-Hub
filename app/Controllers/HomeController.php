<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;

final class HomeController
{
    public function index(): void
    {
        // Production access requires authentication integration before this guard is removed.
        if (config('app')['environment'] !== 'local') {
            http_response_code(503);
            View::render('errors/unavailable', ['pageTitle' => 'Portal belum tersedia']);
            return;
        }
        $portal = config('portal');
        $cards = array_values(array_filter($portal['cards'], static fn (array $card): bool => $card['enabled']));
        usort($cards, static fn (array $a, array $b): int => $a['sort_order'] <=> $b['sort_order']);
        $groups = [];
        foreach ($portal['categories'] as $key => $category) {
            $groups[$key] = $category + ['cards' => array_values(array_filter($cards, static fn (array $card): bool => $card['category'] === $key))];
        }
        $dashboard = config('dashboard');
        $initialProgress = $dashboard['daily_progress'];
        $target = max(0, (int) $initialProgress['target']);
        $completed = min($target, max(0, (int) $initialProgress['completed']));
        $progress = [
            'completed' => $completed,
            'target' => $target,
            'remaining' => $target - $completed,
            'percentage' => $target > 0 ? (int) round($completed / $target * 100) : 0,
            'ratio' => $target > 0 ? $completed / $target : 0,
        ];
        View::render('home/index', [
            'pageTitle' => 'Portal Monitoring IT',
            'groups' => $groups,
            'progress' => $progress,
            'profile' => $dashboard['profile'],
        ]);
    }
}
