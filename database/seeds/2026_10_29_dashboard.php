<?php
declare(strict_types=1);

use App\Models\Dashboard;
use App\Services\FormDefinition;

// Starting dashboard: the three sections, the six monitoring forms (database/forms/monitoring.php) and the nine
// link cards without an address yet. Runs only while dashboard_sections and forms are both empty, so running it again changes nothing.

return static function (PDO $pdo): void {
    $sections = (int) $pdo->query('SELECT COUNT(*) FROM dashboard_sections')->fetchColumn();
    $forms = (int) $pdo->query('SELECT COUNT(*) FROM forms')->fetchColumn();
    if ($sections > 0 || $forms > 0) {
        echo '    (dashboard sudah berisi data, seed dilewati)' . PHP_EOL;
        return;
    }

    $dashboard = new Dashboard($pdo);

    $pdo->beginTransaction();
    try {
        $sectionIds = [];
        $insertSection = $pdo->prepare('INSERT INTO dashboard_sections (slug, title, icon, kind, is_system, sort_order) VALUES (?, ?, ?, ?, 1, ?)');
        foreach ([
            ['harian', 'Monitoring Harian', 'calendar-days', 'forms'],
            ['laporan', 'Monitoring & Laporan', 'chart-pie', 'links'],
            ['sistem', 'Sistem', 'monitor', 'links'],
        ] as $i => [$slug, $title, $icon, $kind]) {
            $insertSection->execute([$slug, $title, $icon, $kind, ($i + 1) * 10]);
            $sectionIds[$slug] = (int) $pdo->lastInsertId();
        }

        $links = [
            ['laporan', 'IT Online', 'Status konektivitas & server IT online', 'globe', 'teal'],
            ['laporan', 'Dashboard', 'Visualisasi & analitik sistem terpusat', 'layout-dashboard', 'cyan'],
            ['laporan', 'HIS', 'Hospital Information System', 'building-2', 'emerald'],
            ['sistem', 'ABE Office', 'IM & SPPD', 'file-text', 'rose'],
            ['sistem', 'Dashboard KPI', 'Akses menggunakan jaringan RS / VPN', 'mail', 'purple'],
            ['sistem', 'Monitoring Setting', 'Perbandingan sys_setting antar RS', 'target', 'amber'],
            ['sistem', 'Chatbot AI', 'Akses layanan asisten AI', 'bot', 'orange'],
            ['sistem', 'Dashboard', 'Visualisasi & analitik sistem terpusat', 'layout-dashboard', 'cyan'],
            ['sistem', 'HIS', 'Hospital Information System', 'building-2', 'emerald'],
        ];
        $sort = [];
        foreach ($links as [$section, $title, $description, $icon, $color]) {
            $sort[$section] = ($sort[$section] ?? 0) + 10;
            $dashboard->insertCard($sectionIds[$section], 'link', null, ['title' => $title, 'description' => $description, 'icon' => $icon, 'color' => $color, 'url' => null], $sort[$section]);
        }
        foreach (require BASE_PATH . '/database/forms/monitoring.php' as $item) {
            $checked = FormDefinition::check($item['form']);
            if ($checked['errors'] !== []) {
                throw new RuntimeException($item['slug'] . ': ' . implode(' ', $checked['errors']));
            }
            $form = $checked['definition'];
            $dashboard->createForm($sectionIds['harian'], $item['card'], $form['title'], $form['description'], $form, $item['schedule'], $item['slug']);
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
    echo '    3 bagian, 6 form monitoring, 9 kartu tautan' . PHP_EOL;
};
