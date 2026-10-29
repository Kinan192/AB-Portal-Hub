<?php
declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use ZipArchive;

/**
 * Backup sets of bin/backup.php and bin/restore.php (B9, PRD NFR-4, NFR-5). One set is a folder
 * abportal-YYYYMMDD-HHMMSS (WIB) with:
 *   database.sql.gz   mysqldump of the database (all tables, audit_logs included: NFR-11)
 *   files.zip         storage/app/private (uploaded evidence files)
 *   manifest.json     time, database, size and SHA-256 of each file, encryption on/off
 * With BACKUP_ENCRYPTION_KEY both data files are encrypted (".enc", see encryptFile()). A set is written to a
 * ".partial-…" folder and renamed when complete, so a failed run never replaces a good backup.
 */
final class Backup
{
    public const SET_PATTERN = '/^abportal-\d{8}-\d{6}$/';
    private const MAGIC = "ABPENC1\n";
    private const CHUNK = 1048576;

    public static function setName(DateTimeImmutable $at): string
    {
        return 'abportal-' . $at->setTimezone(new DateTimeZone('Asia/Jakarta'))->format('Ymd-His');
    }

    /**
     * The sets to remove so that the newest $keep remain; other folder names are never touched.
     * @param list<string> $names
     * @return list<string> oldest first
     */
    public static function expiredSets(array $names, int $keep): array
    {
        $sets = array_values(array_filter($names, static fn (string $n): bool => preg_match(self::SET_PATTERN, $n) === 1));
        sort($sets);
        return array_slice($sets, 0, max(0, count($sets) - max(1, $keep)));
    }

    /** Option file for mysqldump/mysql so the password never appears on a command line or in the process list. */
    public static function optionFile(array $db): string
    {
        $quote = static fn (string $v): string => '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $v) . '"';
        return "[client]\nuser=" . $quote((string) $db['username']) . "\npassword=" . $quote((string) $db['password'])
            . "\nhost=" . $quote((string) $db['host']) . "\nport=" . (int) $db['port'] . "\ndefault-character-set=utf8mb4\n";
    }

    /** A database name the scripts accept (letters, digits, _ and -), so it can be used in SQL safely. */
    public static function validDatabase(string $name): bool
    {
        return preg_match('/^[A-Za-z0-9_-]{1,64}$/', $name) === 1;
    }

    /** True for a zip entry that stays inside the target folder (no "..", no absolute or drive path). */
    public static function safeEntry(string $name): bool
    {
        $name = str_replace('\\', '/', $name);
        return $name !== '' && !str_starts_with($name, '/') && preg_match('#^[A-Za-z]:#', $name) !== 1
            && !in_array('..', explode('/', $name), true) && !str_contains($name, "\0");
    }

    /** A new random key for BACKUP_ENCRYPTION_KEY. */
    public static function newKey(): string
    {
        return base64_encode(random_bytes(32));
    }

    /** The 32-byte key of BACKUP_ENCRYPTION_KEY, or an error. */
    public static function key(string $base64): string
    {
        $key = base64_decode($base64, true);
        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException('BACKUP_ENCRYPTION_KEY harus base64 dari 32 byte. Buat dengan: php bin/backup.php key');
        }
        return $key;
    }

    /**
     * AES-256-GCM in 1 MB chunks: "ABPENC1\n", a 16-byte random file id, then per chunk 1 byte "last" flag,
     * 4 bytes length, ciphertext and 16-byte tag. Each file gets its own key (HKDF of the key and the file id);
     * the chunk number and the last flag are authenticated, so a reordered or cut file fails to decrypt.
     */
    public static function encryptFile(string $from, string $to, string $key): void
    {
        $in = fopen($from, 'rb');
        $out = fopen($to, 'wb');
        if ($in === false || $out === false) {
            throw new RuntimeException('File backup tidak dapat dibuka untuk enkripsi.');
        }
        $id = random_bytes(16);
        $fileKey = hash_hkdf('sha256', $key, 32, 'abportal-backup', $id);
        fwrite($out, self::MAGIC . $id);
        $n = 0;
        $chunk = (string) fread($in, self::CHUNK);
        do {
            $next = feof($in) ? '' : (string) fread($in, self::CHUNK);
            $last = $next === '' && feof($in);
            $tag = '';
            $cipher = openssl_encrypt($chunk, 'aes-256-gcm', $fileKey, OPENSSL_RAW_DATA, self::nonce($n), $tag, self::aad($n, $last));
            if ($cipher === false) {
                throw new RuntimeException('Enkripsi backup gagal.');
            }
            fwrite($out, ($last ? "\1" : "\0") . pack('N', strlen($cipher)) . $cipher . $tag);
            $chunk = $next;
            $n++;
        } while (!$last);
        fclose($in);
        fclose($out);
    }

    public static function decryptFile(string $from, string $to, string $key): void
    {
        $in = fopen($from, 'rb');
        $out = fopen($to, 'wb');
        if ($in === false || $out === false) {
            throw new RuntimeException('File backup tidak dapat dibuka untuk dekripsi.');
        }
        try {
            if (fread($in, strlen(self::MAGIC)) !== self::MAGIC) {
                throw new RuntimeException('File ini bukan backup terenkripsi AB Portal.');
            }
            $fileKey = hash_hkdf('sha256', $key, 32, 'abportal-backup', (string) fread($in, 16));
            for ($n = 0; ; $n++) {
                $head = (string) fread($in, 5);
                if (strlen($head) !== 5) {
                    throw new RuntimeException('File backup terpotong (bagian terakhir tidak ada).');
                }
                $last = $head[0] === "\1";
                $length = unpack('N', substr($head, 1))[1];
                $cipher = $length > 0 ? (string) fread($in, $length) : '';
                $tag = (string) fread($in, 16);
                $plain = openssl_decrypt($cipher, 'aes-256-gcm', $fileKey, OPENSSL_RAW_DATA, self::nonce($n), $tag, self::aad($n, $last));
                if ($plain === false || strlen($cipher) !== $length) {
                    throw new RuntimeException('Dekripsi gagal: kunci salah atau file rusak.');
                }
                fwrite($out, $plain);
                if ($last) {
                    break;
                }
            }
        } finally {
            fclose($in);
            fclose($out);
        }
    }

    /** Zips a folder (relative paths, forward slashes); returns the number of files. */
    public static function zipFolder(string $folder, string $zipPath): int
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Arsip berkas tidak dapat dibuat: ' . $zipPath);
        }
        $count = 0;
        if (is_dir($folder)) {
            $base = strlen(rtrim(str_replace('\\', '/', (string) realpath($folder)), '/')) + 1;
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder, \FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $zip->addFile($file->getPathname(), substr(str_replace('\\', '/', (string) $file->getRealPath()), $base));
                    $count++;
                }
            }
        }
        if ($count === 0) {
            $zip->addFromString('.kosong', 'Tidak ada berkas unggahan saat backup dibuat.');
        }
        $zip->close();
        return $count;
    }

    /** Extracts a zip into $target after checking every entry name (no path outside $target); returns the file count. */
    public static function unzipFolder(string $zipPath, string $target): int
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Arsip berkas tidak dapat dibuka: ' . $zipPath);
        }
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (!self::safeEntry($name)) {
                $zip->close();
                throw new RuntimeException('Arsip berisi nama berkas yang tidak aman: ' . $name);
            }
            if ($name !== '.kosong' && !str_ends_with($name, '/')) {
                $names[] = $name;
            }
        }
        if (!is_dir($target) && !mkdir($target, 0770, true)) {
            throw new RuntimeException('Folder tujuan tidak dapat dibuat: ' . $target);
        }
        if ($names !== [] && !$zip->extractTo($target, $names)) {
            $zip->close();
            throw new RuntimeException('Berkas tidak dapat diekstrak ke ' . $target);
        }
        $zip->close();
        return count($names);
    }

    /** Removes one backup set folder: only the files directly inside it, then the folder. */
    public static function removeSet(string $dir): bool
    {
        if (preg_match(self::SET_PATTERN, basename($dir)) !== 1 && !str_starts_with(basename($dir), '.partial-')) {
            return false;
        }
        foreach (glob($dir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        return @rmdir($dir);
    }

    /** Copies a set folder (files only) to another location; returns the copy's path. */
    public static function copySet(string $set, string $targetDir): string
    {
        $copy = $targetDir . DIRECTORY_SEPARATOR . basename($set);
        if (!is_dir($copy) && !@mkdir($copy, 0770, true)) {
            throw new RuntimeException('Folder salinan tidak dapat dibuat: ' . $copy);
        }
        foreach (glob($set . '/*') ?: [] as $file) {
            if (is_file($file) && !copy($file, $copy . DIRECTORY_SEPARATOR . basename($file))) {
                throw new RuntimeException('Gagal menyalin ' . basename($file) . ' ke ' . $copy);
            }
        }
        return $copy;
    }

    /** Percentage of the disk holding $path that is in use, or null when unknown. */
    public static function diskUsedPercent(string $path): ?float
    {
        $total = @disk_total_space($path);
        $free = @disk_free_space($path);
        return $total > 0 && $free !== false ? round(($total - $free) / $total * 100, 1) : null;
    }

    private static function nonce(int $n): string
    {
        return "\0\0\0\0" . pack('J', $n);
    }

    private static function aad(int $n, bool $last): string
    {
        return pack('J', $n) . ($last ? "\1" : "\0");
    }
}
