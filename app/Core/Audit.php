<?php
declare(strict_types=1);

namespace App\Core;

use App\Helpers\Format;
use PDO;
use Throwable;

/**
 * Append-only change log (audit_logs, PRD change_log / SEC-12). Every create, edit, delete, export and
 * login event writes one row; the application has no update or delete path for this table.
 * $meta holds e.g. ['changes' => [['field' => …, 'old' => …, 'new' => …]]] or export filters.
 * Passwords and CSRF tokens are never logged.
 */
final class Audit
{
    public static function log(string $action, string $entityType, ?int $entityId = null, array $meta = [], ?string $entryNo = null, ?string $reason = null, ?PDO $pdo = null): void
    {
        $pdo ??= Database::connection();
        $user = Auth::user();
        $userId = isset($user['id']) && is_int($user['id']) ? $user['id'] : null;
        $pdo->prepare(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, entry_no, reason, ip, user_agent, meta_json)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            $entryNo,
            $reason,
            substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
            substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null,
            $meta === [] ? null : json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR),
        ]);
    }

    /** Like log(), but a failing database never breaks the request (login and logout events). */
    public static function tryLog(string $action, string $entityType, ?int $entityId = null, array $meta = []): void
    {
        try {
            self::log($action, $entityType, $entityId, $meta);
        } catch (Throwable $exception) {
            error_log('Audit log gagal: ' . $exception->getMessage());
        }
    }

    /**
     * The change log of an entry for its detail (PRD FR-R7), oldest first: time, action, changed fields with
     * their labels, reason. Matched on the entry number too: it is never reused, while an id can come back
     * after a table was rebuilt.
     * @param array<string, string> $labels field => label
     */
    public static function history(string $entityType, int $id, string $entryNo, array $labels): array
    {
        $statement = Database::connection()->prepare("SELECT action, reason, meta_json, created_at FROM audit_logs WHERE entity_type = ? AND entity_id = ? AND entry_no = ? AND action IN ('create', 'update', 'delete') ORDER BY id");
        $statement->execute([$entityType, $id, $entryNo]);
        $actions = ['create' => 'Dibuat', 'update' => 'Diubah', 'delete' => 'Dihapus'];
        $show = static fn (mixed $value): string => $value === null || $value === '' ? '—' : (string) $value;
        return array_map(static function (array $row) use ($actions, $labels, $show): array {
            $meta = json_decode((string) $row['meta_json'], true) ?: [];
            return [
                'at' => Format::dateTime($row['created_at']),
                'action' => $actions[$row['action']] ?? $row['action'],
                'reason' => $row['reason'],
                'changes' => array_map(static fn (array $c): array => [
                    'field' => $labels[$c['field']] ?? $c['field'],
                    'old' => $show($c['old']),
                    'new' => $show($c['new']),
                ], $meta['changes'] ?? []),
            ];
        }, $statement->fetchAll());
    }

    /** Field changes between two rows, for the 'changes' entry of $meta. */
    public static function changes(array $old, array $new, array $fields): array
    {
        $changes = [];
        foreach ($fields as $field) {
            if (($old[$field] ?? null) != ($new[$field] ?? null)) {
                $changes[] = ['field' => $field, 'old' => $old[$field] ?? null, 'new' => $new[$field] ?? null];
            }
        }
        return $changes;
    }
}
