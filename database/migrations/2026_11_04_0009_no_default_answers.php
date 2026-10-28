<?php
declare(strict_types=1);

// C2: no answer is preselected when a form is opened. The question lists stored in form_versions still carry
// "default" answers from the prototype (e.g. Hardware Server "RAK SERVER 1" / "BAIK", Kesesuaian User "sesuai",
// Antrian Online "TERKIRIM"). Every form whose current version has such a default gets a new version without it;
// the old version stays (reports sent with it keep showing their own questions). The fill-in page ignores
// defaults as well, so this only cleans the stored lists (and what Duplikat copies).
// Data change: one new row in form_versions per affected form and forms.current_version_id of those forms.
// No report changes. New versions are marked "c2_no_defaults" so the undo finds exactly them.
// Undo (`php bin/migrate.php down`): the forms point at their previous version again and the marked versions are
// removed. It refuses (nothing changed) while a report was sent with a marked version.

$withoutDefaults = static function (array $definition): array {
    $definition['questions'] = array_map(static function ($q) {
        if (is_array($q)) {
            unset($q['default']);
        }
        return $q;
    }, $definition['questions'] ?? []);
    return $definition + ['c2_no_defaults' => true];
};
$hasDefault = static fn (array $definition): bool => array_filter($definition['questions'] ?? [], static fn ($q): bool => is_array($q) && array_key_exists('default', $q)) !== [];

return [
    'up' => static function (PDO $pdo) use ($withoutDefaults, $hasDefault): void {
        $forms = $pdo->query(
            'SELECT f.id, v.title, v.description, v.definition_json FROM forms f JOIN form_versions v ON v.id = f.current_version_id WHERE f.deleted_at IS NULL'
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($forms as $form) {
            $definition = json_decode((string) $form['definition_json'], true) ?: [];
            if (!$hasDefault($definition)) {
                continue;
            }
            $next = (int) $pdo->query('SELECT COALESCE(MAX(version_no), 0) + 1 FROM form_versions WHERE form_id = ' . (int) $form['id'])->fetchColumn();
            $pdo->prepare('INSERT INTO form_versions (form_id, version_no, title, description, definition_json) VALUES (?, ?, ?, ?, ?)')
                ->execute([$form['id'], $next, $form['title'], $form['description'], json_encode($withoutDefaults($definition), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)]);
            $pdo->prepare('UPDATE forms SET current_version_id = ? WHERE id = ?')->execute([(int) $pdo->lastInsertId(), $form['id']]);
        }
    },
    'down' => static function (PDO $pdo): void {
        $marked = $pdo->query("SELECT id, form_id FROM form_versions WHERE JSON_EXTRACT(definition_json, '$.c2_no_defaults') = true")->fetchAll(PDO::FETCH_ASSOC);
        if ($marked === []) {
            return;
        }
        $ids = implode(', ', array_map(static fn (array $v): int => (int) $v['id'], $marked));
        $used = (int) $pdo->query("SELECT COUNT(*) FROM form_entries WHERE version_id IN ({$ids})")->fetchColumn();
        if ($used > 0) {
            throw new RuntimeException("{$used} laporan sudah dikirim dengan versi tanpa jawaban awal; versi itu tidak dihapus. Pulihkan dari backup bila perlu.");
        }
        foreach ($marked as $version) {
            $previous = $pdo->prepare('SELECT id FROM form_versions WHERE form_id = ? AND id <> ? ORDER BY version_no DESC LIMIT 1');
            $previous->execute([$version['form_id'], $version['id']]);
            $pdo->prepare('UPDATE forms SET current_version_id = ? WHERE id = ? AND current_version_id = ?')->execute([$previous->fetchColumn() ?: null, $version['form_id'], $version['id']]);
            $pdo->prepare('DELETE FROM form_versions WHERE id = ?')->execute([$version['id']]);
        }
    },
];
