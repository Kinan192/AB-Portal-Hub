<?php
declare(strict_types=1);

use App\Core\Migrator;

// C1: the hospital works one shift, so CCTV checks and Clear Desk sessions no longer have a shift.
// 1. The shift column stays (old entries keep their value, nothing is deleted) but is no longer required, and
//    new entries leave it empty. The CCTV "same date and shift" index becomes a date index ("CCTV hari ini
//    sudah diisi").
// 2. The stored question lists of the CCTV and Clear Desk cards (form_versions, used by Duplikat) get a new
//    version without the "Shift Operasional" question and the "serah terima shift" wording. The old version
//    stays as history. The new version is marked with "c1_no_shift" so the undo finds exactly it.
// Data change: two new rows in form_versions and forms.current_version_id of those two forms. No entry changes.
// Undo (`php bin/migrate.php down`): those two forms point at their previous version again and the two new
// versions are removed; empty shifts are filled from the inspection time (WIB: 07:00–13:59 pagi, 14:00–20:59
// siang, otherwise malam, the former FR-F6 rule), the column is required again and the old index returns.
// Old shift values are never changed.

$fill = static fn (string $time): string => "CASE WHEN HOUR(DATE_ADD({$time}, INTERVAL 7 HOUR)) BETWEEN 7 AND 13 THEN 'pagi'"
    . " WHEN HOUR(DATE_ADD({$time}, INTERVAL 7 HOUR)) BETWEEN 14 AND 20 THEN 'siang' ELSE 'malam' END";

// The question list without shift: no question asking for one, no "serah terima shift" text.
$withoutShift = static function (array $definition): array {
    $questions = [];
    foreach ($definition['questions'] ?? [] as $q) {
        if (!is_array($q) || ($q['id'] ?? '') === 'shift' || preg_match('/^shift\b/i', (string) ($q['title'] ?? '')) === 1) {
            continue;
        }
        foreach (['title', 'placeholder', 'help'] as $key) {
            if (isset($q[$key]) && is_string($q[$key])) {
                $q[$key] = str_replace([' / SERAH TERIMA SHIFT', 'untuk serah terima shift atau ', 'serah terima shift'], ['', 'atau ', 'serah terima'], $q[$key]);
            }
        }
        $questions[] = $q;
    }
    return ['questions' => $questions] + $definition + ['c1_no_shift' => true];
};

return [
    'up' => static function (PDO $pdo) use ($withoutShift): void {
        $pdo->exec("ALTER TABLE cctv_entries MODIFY shift ENUM('pagi','siang','malam') NULL DEFAULT NULL");
        if (!Migrator::hasIndex($pdo, 'cctv_entries', 'idx_cctv_date')) {
            $pdo->exec('ALTER TABLE cctv_entries ADD KEY idx_cctv_date (inspection_date, deleted_at)');
        }
        if (Migrator::hasIndex($pdo, 'cctv_entries', 'idx_cctv_date_shift')) {
            $pdo->exec('ALTER TABLE cctv_entries DROP INDEX idx_cctv_date_shift');
        }
        $pdo->exec("ALTER TABLE cleardesk_sessions MODIFY shift ENUM('pagi','siang','malam') NULL DEFAULT NULL");

        $forms = $pdo->query(
            "SELECT f.id, v.version_no, v.title, v.description, v.definition_json FROM forms f
             JOIN form_versions v ON v.id = f.current_version_id WHERE f.module_key IN ('cctv', 'cleardesk')"
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($forms as $form) {
            $definition = json_decode((string) $form['definition_json'], true) ?: [];
            if (!empty($definition['c1_no_shift'])) {
                continue;
            }
            $next = (int) $pdo->query('SELECT COALESCE(MAX(version_no), 0) + 1 FROM form_versions WHERE form_id = ' . (int) $form['id'])->fetchColumn();
            $pdo->prepare('INSERT INTO form_versions (form_id, version_no, title, description, definition_json) VALUES (?, ?, ?, ?, ?)')
                ->execute([$form['id'], $next, $form['title'], $form['description'], json_encode($withoutShift($definition), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)]);
            $pdo->prepare('UPDATE forms SET current_version_id = ? WHERE id = ?')->execute([(int) $pdo->lastInsertId(), $form['id']]);
        }
    },
    'down' => static function (PDO $pdo) use ($fill): void {
        $marked = $pdo->query("SELECT id, form_id FROM form_versions WHERE JSON_EXTRACT(definition_json, '$.c1_no_shift') = true")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($marked as $version) {
            $previous = $pdo->prepare('SELECT id FROM form_versions WHERE form_id = ? AND id <> ? ORDER BY version_no DESC LIMIT 1');
            $previous->execute([$version['form_id'], $version['id']]);
            $pdo->prepare('UPDATE forms SET current_version_id = ? WHERE id = ? AND current_version_id = ?')->execute([$previous->fetchColumn() ?: null, $version['form_id'], $version['id']]);
            $pdo->prepare('DELETE FROM form_versions WHERE id = ?')->execute([$version['id']]);
        }
        $pdo->exec('UPDATE cctv_entries SET shift = ' . $fill('inspected_at') . ' WHERE shift IS NULL');
        $pdo->exec("ALTER TABLE cctv_entries MODIFY shift ENUM('pagi','siang','malam') NOT NULL");
        if (!Migrator::hasIndex($pdo, 'cctv_entries', 'idx_cctv_date_shift')) {
            $pdo->exec('ALTER TABLE cctv_entries ADD KEY idx_cctv_date_shift (inspection_date, shift, deleted_at)');
        }
        if (Migrator::hasIndex($pdo, 'cctv_entries', 'idx_cctv_date')) {
            $pdo->exec('ALTER TABLE cctv_entries DROP INDEX idx_cctv_date');
        }
        $pdo->exec('UPDATE cleardesk_sessions SET shift = ' . $fill('started_at') . ' WHERE shift IS NULL');
        $pdo->exec("ALTER TABLE cleardesk_sessions MODIFY shift ENUM('pagi','siang','malam') NOT NULL");
    },
];
