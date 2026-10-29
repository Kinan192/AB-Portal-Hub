<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

/**
 * Versioned schema changes (bin/migrate.php). Every file in database/migrations/ is named
 * YYYY_MM_DD_NNNN_name.php and returns ['up' => fn (PDO $pdo): void, 'down' => fn (PDO $pdo): void].
 * Applied files are recorded in schema_migrations with a batch number; `down` undoes the last batch in
 * reverse order. MariaDB commits every DDL statement at once, so each migration checks what already
 * exists (hasTable / hasColumn / hasIndex) and can be run again after a failure.
 */
final class Migrator
{
    public function __construct(private readonly PDO $pdo, private readonly string $directory)
    {
    }

    /** Creates schema_migrations, or adds the batch column to the table made by the first backend draft. */
    public function prepare(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                migration VARCHAR(190) NOT NULL PRIMARY KEY,
                batch INT UNSIGNED NOT NULL DEFAULT 1,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        if (!self::hasColumn($this->pdo, 'schema_migrations', 'batch')) {
            $this->pdo->exec('ALTER TABLE schema_migrations ADD COLUMN batch INT UNSIGNED NOT NULL DEFAULT 1 AFTER migration');
        }
    }

    /** @return list<string> migration file names in run order */
    public function files(): array
    {
        $files = array_map('basename', glob($this->directory . '/*.php') ?: []);
        sort($files, SORT_STRING);
        return $files;
    }

    /** @return array<string, int> applied migration => batch */
    public function applied(): array
    {
        $rows = $this->pdo->query('SELECT migration, batch FROM schema_migrations ORDER BY migration')->fetchAll(PDO::FETCH_KEY_PAIR);
        return array_map('intval', $rows);
    }

    /** @return list<string> */
    public function pending(): array
    {
        return self::pendingOf($this->files(), array_keys($this->applied()));
    }

    /** Pure rule (see tests): files not yet applied, in name order. @param list<string> $files @param list<string> $applied */
    public static function pendingOf(array $files, array $applied): array
    {
        $pending = array_values(array_diff($files, $applied));
        sort($pending, SORT_STRING);
        return $pending;
    }

    /** Pure rule (see tests): the migrations of the highest batch, newest first. @param array<string, int> $applied */
    public static function lastBatchOf(array $applied): array
    {
        if ($applied === []) {
            return [];
        }
        $batch = max($applied);
        $names = array_keys(array_filter($applied, static fn (int $value): bool => $value === $batch));
        rsort($names, SORT_STRING);
        return $names;
    }

    /** Applies every pending migration as one new batch. @param callable(string): void $report */
    public function up(callable $report): int
    {
        $pending = $this->pending();
        if ($pending === []) {
            return 0;
        }
        $applied = $this->applied();
        $batch = ($applied === [] ? 0 : max($applied)) + 1;
        $record = $this->pdo->prepare('INSERT INTO schema_migrations (migration, batch, applied_at) VALUES (?, ?, UTC_TIMESTAMP())');
        foreach ($pending as $name) {
            $report("Menerapkan {$name}");
            ($this->load($name)['up'])($this->pdo);
            $record->execute([$name, $batch]);
        }
        return count($pending);
    }

    /** Undoes the last batch. @param callable(string): void $report */
    public function down(callable $report): int
    {
        $names = self::lastBatchOf($this->applied());
        $forget = $this->pdo->prepare('DELETE FROM schema_migrations WHERE migration = ?');
        foreach ($names as $name) {
            if (!is_file($this->directory . '/' . $name)) {
                throw new RuntimeException("File migrasi {$name} tidak ada, tidak dapat dibatalkan.");
            }
            $report("Membatalkan {$name}");
            ($this->load($name)['down'])($this->pdo);
            $forget->execute([$name]);
        }
        return count($names);
    }

    /** @return array{up: callable(PDO): void, down: callable(PDO): void} */
    private function load(string $name): array
    {
        $migration = require $this->directory . '/' . $name;
        if (!is_array($migration) || !is_callable($migration['up'] ?? null) || !is_callable($migration['down'] ?? null)) {
            throw new RuntimeException("Migrasi {$name} harus mengembalikan ['up' => fn, 'down' => fn].");
        }
        return $migration;
    }

    /** Statements of an SQL file: "--" comment lines dropped, split on a semicolon that ends a line. @return list<string> */
    public static function statements(string $sql): array
    {
        $sql = (string) preg_replace('/^\s*--.*$/m', '', $sql);
        return array_values(array_filter(
            array_map('trim', preg_split('/;\s*$/m', $sql) ?: []),
            static fn (string $statement): bool => $statement !== ''
        ));
    }

    public static function hasTable(PDO $pdo, string $table): bool
    {
        $statement = $pdo->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $statement->execute([$table]);
        return $statement->fetchColumn() !== false;
    }

    public static function hasColumn(PDO $pdo, string $table, string $column): bool
    {
        $statement = $pdo->prepare('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $statement->execute([$table, $column]);
        return $statement->fetchColumn() !== false;
    }

    public static function hasIndex(PDO $pdo, string $table, string $index): bool
    {
        $statement = $pdo->prepare('SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
        $statement->execute([$table, $index]);
        return $statement->fetchColumn() !== false;
    }
}
