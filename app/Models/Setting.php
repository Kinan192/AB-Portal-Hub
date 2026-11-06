<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

/** Settings changed from the portal (table app_settings): one JSON value per name. */
final class Setting
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array{value: array, updated_at: string, updated_by: string}|null */
    public function get(string $name): ?array
    {
        $s = $this->pdo->prepare('SELECT value_json, updated_at, updated_by FROM app_settings WHERE name = ?');
        $s->execute([$name]);
        $row = $s->fetch();
        if ($row === false) {
            return null;
        }
        $value = json_decode((string) $row['value_json'], true);
        return ['value' => is_array($value) ? $value : [], 'updated_at' => (string) $row['updated_at'], 'updated_by' => (string) $row['updated_by']];
    }

    public function put(string $name, array $value, string $by): void
    {
        $this->pdo->prepare('INSERT INTO app_settings (name, value_json, updated_at, updated_by) VALUES (?, ?, UTC_TIMESTAMP(), ?)
            ON DUPLICATE KEY UPDATE value_json = VALUES(value_json), updated_at = VALUES(updated_at), updated_by = VALUES(updated_by)')
            ->execute([$name, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), $by]);
    }
}
