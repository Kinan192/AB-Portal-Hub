<?php
declare(strict_types=1);

use App\Models\Dashboard;
use App\Services\FormDefinition;

// D4: the six monitoring forms (Hardware Server, Kesesuaian User, Suhu, CCTV, Clear Desk, Antrian Online) come
// back as ordinary dynamic forms (database/forms/monitoring.php), first in the forms section. A slug that is
// already taken is skipped. A new database without sections gets them from the seed instead.
// Undo: `php bin/migrate.php down` removes these forms while none of them has a report.

$slugs = ['hardware-server', 'kesesuaian-user', 'suhu-server', 'cctv', 'clear-desk', 'antrian-online'];

return [
    'up' => static function (PDO $pdo): void {
        $dashboard = new Dashboard($pdo);
        $section = $dashboard->formsSection();
        if ($section === null) {
            echo '    (belum ada bagian form, dilewati; seed akan membuatnya)' . PHP_EOL;
            return;
        }
        $taken = $pdo->prepare('SELECT 1 FROM forms WHERE slug = ?');
        $items = array_values(array_filter(require BASE_PATH . '/database/forms/monitoring.php', static function (array $item) use ($taken): bool {
            $taken->execute([$item['slug']]);
            return $taken->fetchColumn() === false;
        }));
        if ($items === []) {
            return;
        }

        $pdo->beginTransaction();
        try {
            // Room at the top of the section.
            $pdo->prepare('UPDATE dashboard_cards SET sort_order = sort_order + ? WHERE section_id = ?')->execute([count($items) * 10, (int) $section['id']]);
            foreach ($items as $i => $item) {
                $checked = FormDefinition::check($item['form']);
                if ($checked['errors'] !== []) {
                    throw new RuntimeException($item['slug'] . ': ' . implode(' ', $checked['errors']));
                }
                $form = $checked['definition'];
                $dashboard->createForm((int) $section['id'], $item['card'], $form['title'], $form['description'], $form, $item['schedule'], $item['slug'], ($i + 1) * 10);
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
        echo '    ' . count($items) . ' form monitoring' . PHP_EOL;
    },
    'down' => static function (PDO $pdo) use ($slugs): void {
        $marks = implode(', ', array_fill(0, count($slugs), '?'));
        $find = $pdo->prepare("SELECT id FROM forms WHERE slug IN ({$marks})");
        $find->execute($slugs);
        $ids = array_map('intval', $find->fetchAll(PDO::FETCH_COLUMN));
        if ($ids === []) {
            return;
        }
        $in = implode(', ', $ids);
        if ((int) $pdo->query("SELECT COUNT(*) FROM form_entries WHERE form_id IN ({$in})")->fetchColumn() > 0) {
            throw new RuntimeException('Form monitoring sudah punya laporan. Hapus kartunya di dashboard atau pulihkan dari backup (php bin/restore.php).');
        }
        $pdo->beginTransaction();
        try {
            $pdo->exec("UPDATE attachments SET deleted_at = NOW() WHERE deleted_at IS NULL AND owner_type = 'dashboard_card' AND owner_id IN (SELECT id FROM dashboard_cards WHERE form_id IN ({$in}))");
            $pdo->exec("DELETE FROM dashboard_cards WHERE form_id IN ({$in})");
            $pdo->exec("DELETE FROM form_schedule_dates WHERE form_id IN ({$in})");
            $pdo->exec("DELETE FROM form_schedules WHERE form_id IN ({$in})");
            $pdo->exec("UPDATE forms SET current_version_id = NULL WHERE id IN ({$in})");
            $pdo->exec("DELETE FROM form_versions WHERE form_id IN ({$in})");
            $pdo->exec("DELETE FROM forms WHERE id IN ({$in})");
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    },
];
