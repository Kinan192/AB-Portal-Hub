<?php
declare(strict_types=1);

// Restore a backup set of bin/backup.php (B9, PRD NFR-5). Checks every file against manifest.json first.
// Usage:
//   php bin/restore.php <set> [--database=NAME] [--files-to=FOLDER] [--only=database|files] [--yes]
//   <set>          folder of the set, or its name in BACKUP_DIR (e.g. abportal-20261101-010000)
//   --database     database to restore into (default DB_DATABASE); created when it does not exist
//   --files-to     folder for the uploaded files (default storage/app/private)
//   --only         restore only the database or only the files
//   --yes          skip the typed confirmation (for scripted restore tests only)
// The tables in the backup REPLACE the tables of the same name in the target database. Back up the target first
// (php bin/backup.php) and stop the web server while restoring the live database.

use App\Core\Database;
use App\Services\Backup;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/bootstrap/app.php';

$args = array_slice($argv, 1);
$option = static function (string $name) use ($args): ?string {
    foreach ($args as $arg) {
        if (str_starts_with($arg, '--' . $name . '=')) {
            return substr($arg, strlen($name) + 3);
        }
    }
    return null;
};
$positional = array_values(array_filter($args, static fn (string $a): bool => !str_starts_with($a, '--')));
$fail = static function (string $message): never {
    fwrite(STDERR, 'Restore gagal: ' . $message . PHP_EOL);
    exit(1);
};
$confirm = static function (string $question, string $answer) use ($args): bool {
    if (in_array('--yes', $args, true)) {
        return true;
    }
    echo $question . ' ';
    return trim((string) fgets(STDIN)) === $answer;
};

$config = config('backup');
$db = config('database');
if ($positional === []) {
    $fail('sebutkan set backup, mis. php bin/restore.php abportal-20261101-010000 (daftar ada di ' . $config['dir'] . ').');
}
$set = is_dir($positional[0]) ? $positional[0] : $config['dir'] . DIRECTORY_SEPARATOR . $positional[0];
$manifest = json_decode((string) @file_get_contents($set . '/manifest.json'), true);
if (!is_array($manifest) || !isset($manifest['files'])) {
    $fail('manifest.json tidak ada atau rusak di ' . $set);
}
$only = $option('only');
$doDatabase = $only === null || $only === 'database';
$doFiles = $only === null || $only === 'files';
$target = $option('database') ?? $db['database'];
$filesTo = $option('files-to') ?? $config['files'];
if (!Backup::validDatabase($target)) {
    $fail('nama database tidak valid: ' . $target);
}

// 1. Every stored file must match its checksum.
echo "Set {$manifest['set']} dari {$manifest['created_at']} (database {$manifest['database']}, {$manifest['tables']} tabel, {$manifest['uploads']} berkas"
    . ($manifest['encrypted'] ? ', terenkripsi' : '') . ')' . PHP_EOL;
$stored = [];
foreach ($manifest['files'] as $file) {
    $path = $set . DIRECTORY_SEPARATOR . basename((string) $file['name']);
    if (!is_file($path) || hash_file('sha256', $path) !== $file['sha256']) {
        $fail('checksum tidak cocok untuk ' . $file['name'] . '. Set ini rusak; pakai set lain.');
    }
    $stored[str_starts_with((string) $file['name'], 'database') ? 'database' : 'files'] = $path;
}
echo 'Checksum cocok.' . PHP_EOL;
$key = null;
if ($manifest['encrypted']) {
    if ($config['key'] === '') {
        $fail('set ini terenkripsi; isi BACKUP_ENCRYPTION_KEY di .env dengan kunci saat backup dibuat.');
    }
    try {
        $key = Backup::key($config['key']);
    } catch (RuntimeException $exception) {
        $fail($exception->getMessage());
    }
}
$plain = static function (string $path) use ($key): string {
    if ($key === null) {
        return $path;
    }
    $out = (string) tempnam(sys_get_temp_dir(), 'abr');
    try {
        Backup::decryptFile($path, $out, $key);
    } catch (Throwable $exception) {
        @unlink($out);
        throw $exception;
    }
    return $out;
};
$temps = [];

try {
    // 2. Database.
    if ($doDatabase) {
        if (!$confirm("Database {$target} akan DITIMPA dengan isi backup (tabel yang sama diganti). Ketik nama database untuk melanjutkan:", $target)) {
            $fail('dibatalkan. Tidak ada perubahan.');
        }
        $server = new PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $db['host'], $db['port']), $db['username'], $db['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $server->exec('CREATE DATABASE IF NOT EXISTS `' . $target . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $dump = $temps[] = $plain($stored['database']);
        $options = $temps[] = (string) tempnam(sys_get_temp_dir(), 'abp');
        file_put_contents($options, Backup::optionFile($db));
        @chmod($options, 0600);
        $mysql = $config['mysql_bin'] !== '' ? $config['mysql_bin'] . DIRECTORY_SEPARATOR . 'mysql' : 'mysql';
        $process = @proc_open([$mysql, '--defaults-extra-file=' . $options, $target], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('mysql tidak dapat dijalankan. Isi MYSQL_BIN_DIR di .env.');
        }
        $gz = gzopen($dump, 'rb');
        while (!gzeof($gz)) {
            fwrite($pipes[0], (string) gzread($gz, 1048576));
        }
        gzclose($gz);
        fclose($pipes[0]);
        $errors = trim((string) stream_get_contents($pipes[2]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        if (($code = proc_close($process)) !== 0) {
            throw new RuntimeException('mysql gagal (kode ' . $code . ')' . ($errors !== '' ? ': ' . $errors : ''));
        }
        $check = Database::connect(['database' => $target] + $db);
        $count = (int) $check->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetchColumn();
        echo "Database {$target} dipulihkan: {$count} tabel." . PHP_EOL;
    }

    // 3. Uploaded files (existing files with the same name are replaced, others are kept).
    if ($doFiles) {
        if (!$confirm("Berkas unggahan akan dipulihkan ke {$filesTo} (berkas bernama sama ditimpa). Ketik YA untuk melanjutkan:", 'YA')) {
            $fail('dibatalkan. Berkas tidak diubah.');
        }
        $zip = $temps[] = $plain($stored['files']);
        echo 'Berkas dipulihkan: ' . Backup::unzipFolder($zip, $filesTo) . " berkas ke {$filesTo}." . PHP_EOL;
    }
} catch (Throwable $exception) {
    foreach ($temps as $temp) {
        if ($temp !== $stored['database'] && $temp !== $stored['files']) {
            @unlink($temp);
        }
    }
    $fail($exception->getMessage());
}
foreach ($temps as $temp) {
    if ($temp !== $stored['database'] && $temp !== $stored['files']) {
        @unlink($temp);
    }
}
echo 'Selesai. Periksa: php bin/migrate.php status (dengan DB_DATABASE=' . $target . '), lalu buka portal dan rekap.' . PHP_EOL;
