<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Reports marked "Perlu perhatian" (PRD FR-P6), newest first. Deleted reports and reports of deleted forms
 * are left out.
 */
final class AttentionList
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array{source: string, id: int, entry_no: string, at: string, status: string, count: int, slug: ?string, title: ?string}> */
    public function latest(int $perModule = 5): array
    {
        $limit = max(1, $perModule);
        $queries = [
            "SELECT 'form' AS source, e.id, e.entry_no, e.submitted_at AS at, 'perhatian' AS status, 0 AS count, f.slug, v.title
             FROM form_entries e JOIN forms f ON f.id = e.form_id AND f.deleted_at IS NULL LEFT JOIN form_versions v ON v.id = f.current_version_id
             WHERE e.deleted_at IS NULL AND e.attention = 1 ORDER BY e.submitted_at DESC, e.id DESC LIMIT {$limit}",
        ];
        $rows = [];
        foreach ($queries as $sql) {
            foreach ($this->pdo->query($sql)->fetchAll() as $row) {
                $rows[] = ['id' => (int) $row['id'], 'count' => (int) $row['count']] + $row;
            }
        }
        return $rows;
    }
}
