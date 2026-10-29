<?php
declare(strict_types=1);

// Nightly backup (B9, PRD NFR-4, NFR-5, decision Q19): database dump + uploaded files as one backup set in BACKUP_DIR,
// optional copy to BACKUP_COPY_DIR, the newest BACKUP_KEEP (30) sets kept. Also removes application logs older than
// LOG_KEEP_DAYS (90) and warns when the backup disk is 80 % full. Schedule it at 01:00 (README, "Backup otomatis").
// Usage:
//   php bin/backup.php        make a backup set now
//   php bin/backup.php key    print a new BACKUP_ENCRYPTION_KEY (put it in .env AND keep a copy outside this server)
// Exit code: 0 = done, 1 = failed (nothing replaced), 2 = backup done but the copy to BACKUP_COPY_DIR failed.

use App\Core\Database;
use App\Core\ErrorLog;
use App\Services\Backup;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/bootstrap/app.php';

if (($argv[1] ?? '') === 'key') {
    echo Backup::newKey() . PHP_EOL;
    exit(0);
}

$config = config('backup');
$db = config('database');
$say = static fn (string $line) => print($line . PHP_EOL);
$fail = static function (string $message): never {
    error_log('Backup gagal: ' . $message);
    fwrite(STDERR, 'Backup gagal: ' . $message . PHP_EOL);
    exit(1);
};

if (!class_exists(ZipArchive::class)) {
    $fail('ekstensi PHP zip belum aktif (extension=zip di php.ini).');
}
if ($config['key'] !== '' && !function_exists('openssl_encrypt')) {
    $fail('ekstensi PHP openssl belum aktif, padahal BACKUP_ENCRYPTION_KEY diisi.');
}
try {
    $key = $config['key'] !== '' ? Backup::key($config['key']) : null;
} catch (RuntimeException $exception) {
    $fail($exception->getMessage());
}
if (!is_dir($config['dir']) && !@mkdir($config['dir'], 0770, true)) {
    $fail('folder BACKUP_DIR tidak dapat dibuat: ' . $config['dir']);
}
$lock = fopen($config['dir'] . DIRECTORY_SEPARATOR . '.lock', 'c');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    $fail('backup lain sedang berjalan.');
}

$name = Backup::setName(new DateTimeImmutable('now'));
$partial = $config['dir'] . DIRECTORY_SEPARATOR . '.partial-' . $name;
$final = $config['dir'] . DIRECTORY_SEPARATOR . $name;
$options = tempnam(sys_get_temp_dir(), 'abp');
$started = microtime(true);
try {
    if (is_dir($final)) {
        throw new RuntimeException('set ' . $name . ' sudah ada.');
    }
    if (!@mkdir($partial, 0770, true)) {
        throw new RuntimeException('folder sementara tidak dapat dibuat: ' . $partial);
    }
    file_put_contents($options, Backup::optionFile($db));
    @chmod($options, 0600);

    // 1. Database: mysqldump → gzip, streamed (no copy of the whole dump in memory).
    $say("Backup {$name}: database {$db['database']}…");
    $dump = $config['mysql_bin'] !== '' ? $config['mysql_bin'] . DIRECTORY_SEPARATOR . 'mysqldump' : 'mysqldump';
    $process = @proc_open(
        [$dump, '--defaults-extra-file=' . $options, '--single-transaction', '--routines', '--triggers', '--hex-blob', '--no-tablespaces', $db['database']],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes
    );
    if (!is_resource($process)) {
        throw new RuntimeException('mysqldump tidak dapat dijalankan. Isi MYSQL_BIN_DIR di .env (mis. C:\xampp\mysql\bin).');
    }
    $gz = gzopen($partial . '/database.sql.gz', 'wb6');
    $tail = '';
    while (!feof($pipes[1])) {
        $chunk = (string) fread($pipes[1], 1048576);
        gzwrite($gz, $chunk);
        $tail = substr($tail . $chunk, -200);
    }
    gzclose($gz);
    $errors = trim((string) stream_get_contents($pipes[2]));
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($process);
    if ($code !== 0 || !str_contains($tail, 'Dump completed')) {
        throw new RuntimeException('mysqldump gagal (kode ' . $code . ')' . ($errors !== '' ? ': ' . $errors : '') . '. Periksa MYSQL_BIN_DIR dan akun database.');
    }
    $tables = (int) Database::connection()->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetchColumn();

    // 2. Uploaded files.
    $say('Berkas unggahan…');
    $uploads = Backup::zipFolder($config['files'], $partial . '/files.zip');

    // 3. Encryption (optional), then the manifest with a SHA-256 of every stored file.
    $stored = ['database.sql.gz', 'files.zip'];
    if ($key !== null) {
        foreach ($stored as $i => $file) {
            Backup::encryptFile($partial . '/' . $file, $partial . '/' . $file . '.enc', $key);
            unlink($partial . '/' . $file);
            $stored[$i] = $file . '.enc';
        }
    }
    $manifest = [
        'app' => 'AB Portal Hub',
        'set' => $name,
        'created_at' => (new DateTimeImmutable('now'))->format(DATE_ATOM),
        'database' => $db['database'],
        'tables' => $tables,
        'uploads' => $uploads,
        'encrypted' => $key !== null,
        'files' => array_map(static fn (string $file): array => ['name' => $file, 'bytes' => filesize($partial . '/' . $file), 'sha256' => hash_file('sha256', $partial . '/' . $file)], $stored),
    ];
    file_put_contents($partial . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    if (!rename($partial, $final)) {
        throw new RuntimeException('set tidak dapat diberi nama akhir: ' . $final);
    }
} catch (Throwable $exception) {
    Backup::removeSet($partial);
    @unlink($options);
    $fail($exception->getMessage());
} finally {
    @unlink($options);
}

$size = array_sum(array_column($manifest['files'], 'bytes'));
$say(sprintf('Selesai: %s (%d tabel, %d berkas unggahan, %.1f MB%s) dalam %.1f detik.', $final, $tables, $uploads, $size / 1048576, $key !== null ? ', terenkripsi' : '', microtime(true) - $started));

// 4. Copy to the second location, 5. keep the newest sets, 6. old logs, 7. disk space.
$exit = 0;
if ($config['copy_dir'] !== '') {
    try {
        $say('Salinan: ' . Backup::copySet($final, $config['copy_dir']));
        foreach (Backup::expiredSets(array_map('basename', glob($config['copy_dir'] . '/abportal-*', GLOB_ONLYDIR) ?: []), $config['keep']) as $old) {
            Backup::removeSet($config['copy_dir'] . DIRECTORY_SEPARATOR . $old);
        }
    } catch (Throwable $exception) {
        error_log('Backup: salinan ke BACKUP_COPY_DIR gagal: ' . $exception->getMessage());
        fwrite(STDERR, 'PERINGATAN: salinan ke BACKUP_COPY_DIR gagal: ' . $exception->getMessage() . PHP_EOL);
        $exit = 2;
    }
} else {
    $say('PERINGATAN: BACKUP_COPY_DIR kosong; backup hanya ada di mesin ini (PRD NFR-5 meminta salinan di mesin lain).');
}
$removed = 0;
foreach (Backup::expiredSets(array_map('basename', glob($config['dir'] . '/abportal-*', GLOB_ONLYDIR) ?: []), $config['keep']) as $old) {
    $removed += Backup::removeSet($config['dir'] . DIRECTORY_SEPARATOR . $old) ? 1 : 0;
}
$say("Disimpan {$config['keep']} set terbaru; {$removed} set lama dihapus.");
$logs = ErrorLog::prune($config['log_keep_days']);
if ($logs > 0) {
    $say("{$logs} file log lebih dari {$config['log_keep_days']} hari dihapus.");
}
$used = Backup::diskUsedPercent($config['dir']);
if ($used !== null && $used >= $config['disk_warn_percent']) {
    error_log("PERINGATAN: disk backup terpakai {$used} %.");
    $say("PERINGATAN: disk backup terpakai {$used} %. Kosongkan ruang atau pindahkan backup lama.");
}
flock($lock, LOCK_UN);
exit($exit);
