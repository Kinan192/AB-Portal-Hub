<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Migrator;
use PHPUnit\Framework\TestCase;

final class MigratorTest extends TestCase
{
    public function testSqlFileIsSplitOnLineEndingSemicolons(): void
    {
        $sql = "-- comment\nCREATE TABLE a (id INT);\n\n-- another\nCREATE TABLE b (\n  id INT\n) ENGINE=InnoDB;\n";
        self::assertSame(['CREATE TABLE a (id INT)', "CREATE TABLE b (\n  id INT\n) ENGINE=InnoDB"], Migrator::statements($sql));
    }

    public function testBaselineSchemaHasFourTables(): void
    {
        $statements = Migrator::statements((string) file_get_contents(BASE_PATH . '/database/schema.sql'));
        self::assertCount(4, $statements);
        foreach ($statements as $statement) {
            self::assertStringStartsWith('CREATE TABLE IF NOT EXISTS', $statement);
        }
    }

    public function testPendingMigrationsAreTheUnappliedFilesInNameOrder(): void
    {
        $files = ['2026_10_28_0002_auth.php', '2026_10_28_0001_baseline.php', '2026_11_01_0003_dashboard.php'];
        self::assertSame(
            ['2026_10_28_0002_auth.php', '2026_11_01_0003_dashboard.php'],
            Migrator::pendingOf($files, ['2026_10_28_0001_baseline.php', '2026_10_27_0001_create_dashboard_tables.sql']),
        );
    }

    public function testDownUndoesOnlyTheLastBatchNewestFirst(): void
    {
        $applied = ['2026_10_27_0001_create_dashboard_tables.sql' => 1, '2026_10_28_0001_baseline.php' => 2, '2026_10_28_0002_auth.php' => 2];
        self::assertSame(['2026_10_28_0002_auth.php', '2026_10_28_0001_baseline.php'], Migrator::lastBatchOf($applied));
        self::assertSame([], Migrator::lastBatchOf([]));
    }

    public function testEveryMigrationFileHasUpAndDown(): void
    {
        foreach (glob(BASE_PATH . '/database/migrations/*.php') ?: [] as $file) {
            $migration = require $file;
            self::assertIsCallable($migration['up'] ?? null, basename($file));
            self::assertIsCallable($migration['down'] ?? null, basename($file));
        }
    }
}
