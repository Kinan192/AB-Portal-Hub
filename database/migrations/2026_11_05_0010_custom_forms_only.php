<?php
declare(strict_types=1);

// D1: only forms made on the dashboard remain. The six starting forms (Hardware Server, Kesesuaian User, Suhu,
// CCTV, Clear Desk, Antrian Online) are removed with their card, schedule and versions, and every report is
// cleared (form_entries, number_sequences). The Suhu/CCTV/Clear Desk tables are kept as legacy_* so their old
// data can still be read. Uploaded files of the reports are marked deleted (the files stay on disk).
// Undo: not possible with `php bin/migrate.php down`; restore the backup taken before (php bin/restore.php).

$starterSlugs = ['hardware-server', 'kesesuaian-user', 'suhu-server', 'cctv', 'clear-desk', 'antrian-online'];

return [
    'up' => static function (PDO $pdo) use ($starterSlugs): void {
        foreach (['temperature_entries', 'cctv_broken_cameras', 'cctv_entries', 'cleardesk_rows', 'cleardesk_sessions'] as $table) {
            $exists = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
            $exists->execute([$table]);
            if ((int) $exists->fetchColumn() > 0) {
                $pdo->exec("RENAME TABLE {$table} TO legacy_{$table}");
            }
        }

        $pdo->beginTransaction();
        try {
            $pdo->exec("UPDATE attachments SET deleted_at = NOW() WHERE deleted_at IS NULL AND owner_type IN ('form_entry', 'temperature_entry', 'cctv_entry', 'cleardesk_session')");
            $pdo->exec('DELETE FROM form_entries WHERE id > 0');
            $pdo->exec("DELETE FROM number_sequences WHERE prefix <> ''");

            $marks = implode(', ', array_fill(0, count($starterSlugs), '?'));
            $find = $pdo->prepare("SELECT id FROM forms WHERE slug IN ({$marks})");
            $find->execute($starterSlugs);
            $ids = array_map('intval', $find->fetchAll(PDO::FETCH_COLUMN));
            if ($ids !== []) {
                $in = implode(', ', $ids);
                $pdo->exec("UPDATE attachments SET deleted_at = NOW() WHERE deleted_at IS NULL AND owner_type = 'dashboard_card' AND owner_id IN (SELECT id FROM dashboard_cards WHERE form_id IN ({$in}))");
                $pdo->exec("DELETE FROM dashboard_cards WHERE form_id IN ({$in})");
                $pdo->exec("DELETE FROM form_schedule_dates WHERE form_id IN ({$in})");
                $pdo->exec("DELETE FROM form_schedules WHERE form_id IN ({$in})");
                $pdo->exec("UPDATE forms SET current_version_id = NULL WHERE id IN ({$in})");
                $pdo->exec("DELETE FROM form_versions WHERE form_id IN ({$in})");
                $pdo->exec("DELETE FROM forms WHERE id IN ({$in})");
            }
            $pdo->exec("UPDATE forms SET kind = 'dynamic', module_key = NULL WHERE kind <> 'dynamic' OR module_key IS NOT NULL");
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    },
    'down' => static function (PDO $pdo): void {
        throw new RuntimeException('Form bawaan dan laporan yang dihapus tidak bisa dikembalikan dengan migrasi. Pulihkan dari backup (php bin/restore.php).');
    },
];
