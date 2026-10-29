<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Reading the change log (audit_logs) for the Riwayat aktivitas page (PRD SEC-12). Read only: the application
 * has no update or delete path for this table. Filters come from App\Services\ActivityLog::filters().
 */
final class AuditLogs
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array{rows: list<array>, total: int} newest first */
    public function page(array $filters, int $page, int $perPage): array
    {
        $where = ['1 = 1'];
        $params = [];
        if ($filters['from_utc'] !== null) {
            $where[] = 'created_at >= ?';
            $params[] = $filters['from_utc'];
        }
        if ($filters['to_utc'] !== null) {
            $where[] = 'created_at < ?';
            $params[] = $filters['to_utc'];
        }
        if ($filters['actions'] !== []) {
            $where[] = 'action IN (' . implode(', ', array_fill(0, count($filters['actions']), '?')) . ')';
            array_push($params, ...$filters['actions']);
        }
        if ($filters['entity'] !== '') {
            $where[] = 'entity_type = ?';
            $params[] = $filters['entity'];
        }
        if ($filters['q'] !== '') {
            // Entry number, reason, or any text in the details (export ID, card title, username).
            $like = '%' . addcslashes($filters['q'], '%_\\') . '%';
            $where[] = '(entry_no LIKE ? OR reason LIKE ? OR meta_json LIKE ?)';
            array_push($params, $like, $like, $like);
        }
        $sql = implode(' AND ', $where);
        $count = $this->pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE {$sql}");
        $count->execute($params);
        $statement = $this->pdo->prepare(
            "SELECT id, action, entity_type, entity_id, entry_no, reason, ip, user_agent, meta_json, created_at FROM audit_logs
             WHERE {$sql} ORDER BY id DESC LIMIT " . max(1, $perPage) . ' OFFSET ' . max(0, ($page - 1) * $perPage)
        );
        $statement->execute($params);
        return ['rows' => $statement->fetchAll(), 'total' => (int) $count->fetchColumn()];
    }
}
