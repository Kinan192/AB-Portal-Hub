<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Helpers\DashboardData;
use App\Helpers\Schedule;
use App\Models\AttentionList;
use App\Services\Progress;
use DateTimeImmutable;
use Throwable;

final class HomeController extends Controller
{
    public function index(): void
    {
        // Sections, cards and schedules from the database (App\Models\Dashboard); changes go through
        // the JSON API of DashboardController (public/assets/js/dashboard.js).
        $data = $this->dashboardData();
        if ($data === null) {
            return;
        }
        $now = new DateTimeImmutable('now');
        $groups = $this->groups($data['sections'], $data['cards'], $now);
        // Progress (B7, App\Services\Progress): the section counters and the hero ring follow one rule.
        $counts = Progress::sections($data['sections'], $data['cards'], $now);
        $this->render('home/index', [
            'pageTitle' => 'Portal Monitoring IT',
            'groups' => $groups,
            'sectionCounts' => $counts,
            // Card menus (forms and links) and the "Tambah" dialog.
            'editable' => true,
            'showDialogs' => true,
            'linkMenus' => true,
            // State of the dashboard for public/assets/js/dashboard.js (redrawn from each API answer).
            'clientState' => DashboardData::clientState($data),
            'iconNames' => DashboardData::iconNames($data, ['triangle-alert', 'chevron-right']),
            'pageScripts' => ['js/dashboard-store.js', 'js/dashboard.js'],
            'linkSections' => array_values(array_filter($groups, static fn (array $group): bool => $group['kind'] === 'links')),
            'newCardId' => null,
            'cardStyle' => config('dashboard-cards'),
            'progress' => Progress::summary($counts),
            'exceptions' => $this->exceptions($data),
            'profile' => current_profile(),
        ], 'main');
    }

    /** @return list<array<string, mixed>> sections with their cards, in display order */
    private function groups(array $sections, array $cards, DateTimeImmutable $now): array
    {
        $groups = [];
        foreach ($sections as $section) {
            $groups[$section['id']] = $section + ['cards' => []];
        }
        foreach ($cards as $card) {
            if (!isset($groups[$card['section']])) {
                continue;
            }
            $groups[$card['section']]['cards'][] = [
                'id' => $card['id'],
                'section_id' => $card['section'],
                'kind' => $card['kind'],
                'title' => $card['title'],
                'description' => $card['description'],
                'icon' => $card['icon'],
                'color' => $card['color'],
                'image' => $card['image'] ?? null,
                'url' => $card['url'],
                'rekap_url' => $card['rekap'] ?? null,
                'hidden' => !empty($card['hidden']),
                'schedule' => $card['schedule'] ?? null,
                'done_today' => !empty($card['done_today']),
                'has_data' => !empty($card['has_data']),
                // Badge and footer status of Monitoring Harian cards (link cards have neither).
                'label' => isset($card['schedule']) ? Schedule::label($card['schedule']) : null,
                'status' => isset($card['schedule'])
                    ? Schedule::status($card['schedule'], !empty($card['done_today']), !empty($card['has_data']), $now, $card['period'] ?? null)
                    : null,
            ];
        }
        return array_values($groups);
    }

    /** FR-P6: the five newest reports needing a look, for the forms still on the dashboard. */
    private function exceptions(array $data): array
    {
        try {
            $rows = (new AttentionList(Database::connection()))->latest();
        } catch (Throwable $exception) {
            error_log('Perlu perhatian: ' . $exception->getMessage());
            return [];
        }
        $forms = $data['forms'];
        $list = [];
        foreach ($rows as $row) {
            if (!isset($forms[$row['slug']])) {
                continue;
            }
            $list[] = ['module' => (string) ($row['title'] ?? $forms[$row['slug']]['title']), 'status' => 'Perlu perhatian', 'tone' => 'warn',
                'url' => url('/form-dinamis/respons') . '?' . http_build_query(['id' => $row['slug'], 'buka' => $row['id']])] + $row;
        }
        return Progress::exceptions($list);
    }
}
