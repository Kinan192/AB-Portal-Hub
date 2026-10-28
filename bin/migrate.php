<?php
declare(strict_types=1);

// Versioned database changes (database/migrations/, see App\Core\Migrator).
// Usage:
//   php bin/migrate.php            apply every pending migration (one new batch)
//   php bin/migrate.php status     list applied and pending migrations
//   php bin/migrate.php down       undo the last batch (asks to type YA; --yes skips the question)
//   php bin/migrate.php seed       run database/seeds/*.php (each seed skips tables that already hold data)
// Back up the database before a migration that changes data (see README, "Backup sebelum migrasi").

use App\Core\Database;
use App\Core\Migrator;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/bootstrap/app.php';

$command = $argv[1] ?? 'up';
$database = config('database')['database'];
$report = static function (string $line): void {
    echo '  ' . $line . PHP_EOL;
};

try {
    $pdo = Database::connection();
    $migrator = new Migrator($pdo, BASE_PATH . '/database/migrations');
    $migrator->prepare();

    switch ($command) {
        case 'up':
            echo "Migrasi database {$database}" . PHP_EOL;
            $count = $migrator->up($report);
            echo $count === 0 ? 'Tidak ada migrasi baru.' . PHP_EOL : "Selesai: {$count} migrasi diterapkan." . PHP_EOL;
            break;

        case 'status':
            $applied = $migrator->applied();
            $files = $migrator->files();
            echo "Status migrasi database {$database}" . PHP_EOL;
            foreach (array_unique(array_merge(array_keys($applied), $files)) as $name) {
                $state = isset($applied[$name]) ? 'sudah (batch ' . $applied[$name] . ')' : 'belum';
                $missing = in_array($name, $files, true) ? '' : ' (file tidak ada)';
                echo "  [{$state}] {$name}{$missing}" . PHP_EOL;
            }
            break;

        case 'down':
            $last = Migrator::lastBatchOf($migrator->applied());
            if ($last === []) {
                echo 'Tidak ada migrasi untuk dibatalkan.' . PHP_EOL;
                break;
            }
            echo "Akan membatalkan pada database {$database}:" . PHP_EOL . '  ' . implode(PHP_EOL . '  ', $last) . PHP_EOL;
            if (!in_array('--yes', $argv, true)) {
                echo 'Pastikan sudah ada backup. Ketik YA untuk melanjutkan: ';
                if (trim((string) fgets(STDIN)) !== 'YA') {
                    echo 'Dibatalkan. Tidak ada perubahan.' . PHP_EOL;
                    break;
                }
            }
            $count = $migrator->down($report);
            echo "Selesai: {$count} migrasi dibatalkan." . PHP_EOL;
            break;

        case 'seed':
            $seeds = glob(BASE_PATH . '/database/seeds/*.php') ?: [];
            sort($seeds, SORT_STRING);
            if ($seeds === []) {
                echo 'Tidak ada seed.' . PHP_EOL;
                break;
            }
            foreach ($seeds as $seed) {
                echo '  Seed ' . basename($seed) . PHP_EOL;
                (require $seed)($pdo);
            }
            break;

        default:
            fwrite(STDERR, "Perintah tidak dikenal: {$command}. Pakai: up, status, down, seed." . PHP_EOL);
            exit(1);
    }
} catch (Throwable $exception) {
    fwrite(STDERR, 'Migrasi gagal: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
