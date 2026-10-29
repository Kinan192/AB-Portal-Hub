<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

/**
<<<<<<< HEAD
 * Reports marked "Perlu perhatian" (PRD FR-P6), newest first, for the header notification (the last days only)
 * and the "Perlu perhatian" page (every report, filtered). Deleted reports and reports of deleted forms are
 * left out. Each row carries the questions of its own version, so the page can name the marked answers, and
 * read_at: when the given account has seen it (attention_reads), or null.
 */
final class AttentionList
{
    private const SELECT = 'SELECT e.id, e.entry_no, e.submitted_at, e.entry_date, e.answers_json, f.slug, cv.title AS form_title, ev.definition_json, r.read_at
        FROM form_entries e
        JOIN forms f ON f.id = e.form_id AND f.deleted_at IS NULL
        LEFT JOIN form_versions cv ON cv.id = f.current_version_id
        LEFT JOIN form_versions ev ON ev.id = e.version_id
        LEFT JOIN attention_reads r ON r.entry_id = e.id AND r.user_id = :user
        WHERE e.deleted_at IS NULL AND e.attention = 1';

=======
 * Reports marked "Perlu perhatian" (PRD FR-P6), newest first. Deleted reports and reports of deleted forms
 * are left out.
 */
final class AttentionList
{
>>>>>>> 899d036f59ce2361fefc2c1a8249a7b80cbc5bb5
    public function __construct(private readonly PDO $pdo)
    {
    }

<<<<<<< HEAD
    /**
     * Reports sent in the last $days days (UTC clock), at most $limit, with how many of them $userId has not seen.
     * @return array{rows: list<array>, total: int, unread: int}
     */
    public function recent(int $userId, int $days, int $limit): array
    {
        $since = ' AND e.submitted_at >= UTC_TIMESTAMP() - INTERVAL ' . max(1, $days) . ' DAY';
        $count = $this->pdo->prepare('SELECT COUNT(*) AS total, COALESCE(SUM(read_at IS NULL), 0) AS unread FROM (' . self::SELECT . $since . ') t');
        $count->execute(['user' => $userId]);
        $counts = $count->fetch();
        $rows = $this->pdo->prepare(self::SELECT . $since . ' ORDER BY e.submitted_at DESC, e.id DESC LIMIT ' . max(1, $limit));
        $rows->execute(['user' => $userId]);
        return ['rows' => $rows->fetchAll(), 'total' => (int) $counts['total'], 'unread' => (int) $counts['unread']];
    }

    /** $userId has seen these reports (only marked reports are stored). @param list<int> $entryIds */
    public function markRead(int $userId, array $entryIds): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $entryIds), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return;
        }
        $marks = implode(', ', array_fill(0, count($ids), '?'));
        $this->pdo->prepare("INSERT IGNORE INTO attention_reads (user_id, entry_id, read_at)
            SELECT ?, e.id, UTC_TIMESTAMP() FROM form_entries e WHERE e.attention = 1 AND e.id IN ({$marks})")
            ->execute([$userId, ...$ids]);
    }

    /** $userId has seen every marked report of the last $days days ("Tandai semua dilihat"). */
    public function markAllRead(int $userId, int $days): void
    {
        $this->pdo->prepare('INSERT IGNORE INTO attention_reads (user_id, entry_id, read_at)
            SELECT ?, e.id, UTC_TIMESTAMP() FROM form_entries e
            WHERE e.deleted_at IS NULL AND e.attention = 1 AND e.submitted_at >= UTC_TIMESTAMP() - INTERVAL ' . max(1, $days) . ' DAY')
            ->execute([$userId]);
    }

    /**
     * One page of every report. $filters: form (slug or ''), from / to ('YYYY-MM-DD' WIB report dates or '').
     * @return array{rows: list<array>, total: int}
     */
    public function page(int $userId, array $filters, int $page, int $perPage): array
    {
        $where = '';
        $params = ['user' => $userId];
        if (($filters['form'] ?? '') !== '') {
            $where .= ' AND f.slug = :form';
            $params['form'] = $filters['form'];
        }
        if (($filters['from'] ?? '') !== '') {
            $where .= ' AND e.entry_date >= :from';
            $params['from'] = $filters['from'];
        }
        if (($filters['to'] ?? '') !== '') {
            $where .= ' AND e.entry_date <= :to';
            $params['to'] = $filters['to'];
        }
        $count = $this->pdo->prepare('SELECT COUNT(*) FROM (' . self::SELECT . $where . ') t');
        $count->execute($params);
        $statement = $this->pdo->prepare(self::SELECT . $where . ' ORDER BY e.submitted_at DESC, e.id DESC LIMIT ' . max(1, $perPage) . ' OFFSET ' . max(0, ($page - 1) * $perPage));
        $statement->execute($params);
        return ['rows' => $statement->fetchAll(), 'total' => (int) $count->fetchColumn()];
    }

    /** Forms that have marked reports, for the page's filter. @return array<string, string> slug => title */
    public function forms(): array
    {
        $rows = $this->pdo->query('SELECT DISTINCT f.slug, cv.title FROM form_entries e JOIN forms f ON f.id = e.form_id AND f.deleted_at IS NULL
            LEFT JOIN form_versions cv ON cv.id = f.current_version_id WHERE e.deleted_at IS NULL AND e.attention = 1 ORDER BY cv.title')->fetchAll();
        return array_column($rows, 'title', 'slug');
=======
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
>>>>>>> 899d036f59ce2361fefc2c1a8249a7b80cbc5bb5
    }
}
