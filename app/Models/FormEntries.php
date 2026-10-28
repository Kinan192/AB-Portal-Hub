<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Reports of the dynamic forms (form_entries, B6). Every query leaves out deleted entries. Each entry keeps the
 * form version it was filled in; the recap joins that version for its questions. Filters come from
 * App\Controllers\FormEntryController (checked there).
 */
final class FormEntries
{
    private const COLUMNS = 'e.id, e.form_id, e.version_id, e.entry_no, e.submitted_at, e.entry_date, e.answers_json, e.attention, e.created_at, e.updated_at';
    private const SORTS = ['entry_no' => 'e.entry_no', 'waktu' => 'e.submitted_at', 'perhatian' => 'e.attention'];

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** An entry with its form (slug, kind, module) and its version (number, title, definition). */
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . ', f.slug, f.kind, f.module_key, f.deleted_at AS form_deleted_at,
                    v.version_no, v.title AS version_title, v.description AS version_description, v.definition_json
             FROM form_entries e JOIN forms f ON f.id = e.form_id JOIN form_versions v ON v.id = e.version_id
             WHERE e.id = ? AND e.deleted_at IS NULL'
        );
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    /** @param array<string, mixed> $values columns of form_entries */
    public function create(array $values): int
    {
        $columns = array_keys($values);
        $this->pdo->prepare('INSERT INTO form_entries (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')')
            ->execute(array_values($values));
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $values): void
    {
        $allowed = array_intersect_key($values, array_flip(['answers_json', 'search_text', 'attention']));
        if ($allowed === []) {
            return;
        }
        $this->pdo->prepare('UPDATE form_entries SET ' . implode(', ', array_map(static fn (string $c): string => "{$c} = ?", array_keys($allowed))) . ' WHERE id = ? AND deleted_at IS NULL')
            ->execute([...array_values($allowed), $id]);
    }

    public function delete(int $id, string $reason): void
    {
        $this->pdo->prepare('UPDATE form_entries SET deleted_at = UTC_TIMESTAMP(), delete_reason = ? WHERE id = ? AND deleted_at IS NULL')->execute([$reason, $id]);
    }

    /**
     * One page of a form's entries, each with the definition of its version.
     * @return array{rows: list<array>, total: int}
     */
    public function page(int $formId, array $filters, int $page, int $perPage): array
    {
        [$where, $params] = $this->where($formId, $filters);
        $count = $this->pdo->prepare("SELECT COUNT(*) FROM form_entries e WHERE {$where}");
        $count->execute($params);
        $order = (self::SORTS[$filters['sort']] ?? 'e.submitted_at') . ($filters['dir'] === 'asc' ? ' ASC' : ' DESC');
        $statement = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . ', v.version_no, v.definition_json FROM form_entries e JOIN form_versions v ON v.id = e.version_id
             WHERE ' . $where . ' ORDER BY ' . $order . ', e.submitted_at DESC, e.id DESC LIMIT ' . max(1, $perPage) . ' OFFSET ' . max(0, ($page - 1) * $perPage)
        );
        $statement->execute($params);
        return ['rows' => $statement->fetchAll(), 'total' => (int) $count->fetchColumn()];
    }

    /**
     * Figures of the filtered entries: shown, needing a look, and the latest; plus this month's count (WIB).
     * The first and last entry dates (WIB) name an export of every period (FR-E4).
     * @return array{count: int, attention: int, latest: ?string, month: int, first_date: ?string, last_date: ?string}
     */
    public function stats(int $formId, array $filters, string $monthStart, string $monthEnd): array
    {
        [$where, $params] = $this->where($formId, $filters);
        $statement = $this->pdo->prepare("SELECT COUNT(*) AS n, COALESCE(SUM(e.attention), 0) AS attention, MAX(e.submitted_at) AS latest, MIN(e.entry_date) AS first_date, MAX(e.entry_date) AS last_date FROM form_entries e WHERE {$where}");
        $statement->execute($params);
        $totals = $statement->fetch();
        $month = $this->pdo->prepare('SELECT COUNT(*) FROM form_entries WHERE form_id = ? AND deleted_at IS NULL AND entry_date BETWEEN ? AND ?');
        $month->execute([$formId, $monthStart, $monthEnd]);
        return ['count' => (int) $totals['n'], 'attention' => (int) $totals['attention'], 'latest' => $totals['latest'], 'month' => (int) $month->fetchColumn(), 'first_date' => $totals['first_date'], 'last_date' => $totals['last_date']];
    }

    /**
     * Per form: whether it has an entry dated $today (WIB) and whether it has any (dashboard cards).
     * @return array<int, array{done_today: bool, has_data: bool}>
     */
    public function cardStatus(string $today): array
    {
        $statement = $this->pdo->prepare('SELECT form_id, MAX(entry_date = ?) AS today FROM form_entries WHERE deleted_at IS NULL GROUP BY form_id');
        $statement->execute([$today]);
        $status = [];
        foreach ($statement->fetchAll() as $row) {
            $status[(int) $row['form_id']] = ['done_today' => (bool) $row['today'], 'has_data' => true];
        }
        return $status;
    }

    /** @return array{0: string, 1: list<mixed>} */
    private function where(int $formId, array $filters): array
    {
        $where = ['e.form_id = ?', 'e.deleted_at IS NULL'];
        $params = [$formId];
        if ($filters['from'] !== null) {
            $where[] = 'e.entry_date BETWEEN ? AND ?';
            array_push($params, $filters['from'], $filters['to']);
        }
        if ($filters['attention'] !== '') {
            $where[] = 'e.attention = ?';
            $params[] = $filters['attention'] === 'ya' ? 1 : 0;
        }
        if ($filters['q'] !== '') {
            $like = '%' . addcslashes($filters['q'], '%_\\') . '%';
            $where[] = '(e.entry_no LIKE ? OR e.search_text LIKE ?)';
            array_push($params, $like, $like);
        }
        return [implode(' AND ', $where), $params];
    }
}
