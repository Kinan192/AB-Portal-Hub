<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Core\Database;
use App\Models\Dashboard;

/**
 * The dashboard as the browser receives it (#dashboard-state, read by public/assets/js/dashboard-store.js)
 * and the icons the scripts may draw. The data comes from the database (App\Models\Dashboard::load()).
 * Used by the dashboard, its JSON API answers, the form editor and the form pages.
 */
final class DashboardData
{
    /** Sections, cards and forms from the database; $withQuestions adds each form's question list. */
    public static function load(bool $withQuestions = false): array
    {
        return (new Dashboard(Database::connection()))->load($withQuestions);
    }

    /** Sections, cards and form definitions, with internal paths turned into URLs. */
    public static function clientState(array $data): array
    {
        $link = static fn (?string $path): ?string => $path === null ? null : url($path);
        return [
            'sections' => $data['sections'],
            'cards' => array_map(static fn (array $card): array => [
                'id' => $card['id'],
                // Row id for the JSON API (/dashboard/cards/{id}).
                'ref' => $card['ref'],
                'section' => $card['section'],
                'kind' => $card['kind'],
                'title' => $card['title'],
                'description' => $card['description'],
                'icon' => $card['icon'],
                'color' => $card['color'],
                'image' => $card['image'] ?? null,
                'url' => $card['kind'] === 'link' ? $card['url'] : $link($card['url']),
                'rekap' => $link($card['rekap'] ?? null),
                'hidden' => !empty($card['hidden']),
                'schedule' => $card['schedule'] ?? null,
                'done_today' => !empty($card['done_today']),
                'has_data' => !empty($card['has_data']),
                // Status of the period (temperature: "3/4 lokasi"), shown instead of the schedule text.
                'period' => $card['period'] ?? null,
            ], $data['cards']),
            'forms' => (object) $data['forms'],
            // Reports are saved on the server (B3–B6); this stays empty for the scripts that still read it.
            'responses' => (object) [],
        ];
    }

    /** Every icon the browser may draw: the picker icons, the dashboard's icons and the UI icons. */
    public static function iconNames(array $data, array $extra = []): array
    {
        $style = config('dashboard-cards');
        return array_values(array_unique(array_merge(
            array_keys($style['icons']),
            $style['category_icons'],
            array_column($data['sections'], 'icon'),
            array_column($data['cards'], 'icon'),
            ['ellipsis', 'chart-column', 'check', 'eye', 'eye-off', 'plus', 'trash-2', 'link', 'clipboard-list', 'pencil', 'folder-input', 'image', 'triangle-alert', 'copy', 'file-pen-line', 'calendar-days', 'calendar-check', 'list-checks', 'folder-open'],
            $extra,
        )));
    }
}
