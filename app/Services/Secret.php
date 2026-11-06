<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Encrypts small secrets kept in the database (the SMTP password) with AES-256-GCM. The key is a random file,
 * storage/keys/secret.key, made on first use; it is never in the database, so a database dump alone does not
 * reveal the secret. Without the key file (e.g. after restoring on another computer) the secret must be entered again.
 */
final class Secret
{
    private const PREFIX = 'v1:';

    public function __construct(private readonly string $keyFile)
    {
    }

    public static function default(): self
    {
        return new self(BASE_PATH . '/storage/keys/secret.key');
    }

    public function encrypt(string $plain): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $data = openssl_encrypt($plain, 'aes-256-gcm', $this->key(true), OPENSSL_RAW_DATA, $iv, $tag);
        if ($data === false) {
            throw new RuntimeException('Enkripsi gagal.');
        }
        return self::PREFIX . base64_encode($iv . $tag . $data);
    }

    /** The secret, or null when it cannot be read (no key file, another key, damaged value). */
    public function decrypt(string $stored): ?string
    {
        $key = $this->key(false);
        if ($key === null || !str_starts_with($stored, self::PREFIX)) {
            return null;
        }
        $raw = base64_decode(substr($stored, strlen(self::PREFIX)), true);
        if ($raw === false || strlen($raw) < 28) {
            return null;
        }
        $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
        return $plain === false ? null : $plain;
    }

    private function key(bool $create): ?string
    {
        if (is_file($this->keyFile)) {
            $key = base64_decode(trim((string) file_get_contents($this->keyFile)), true);
            if ($key !== false && strlen($key) === 32) {
                return $key;
            }
            throw new RuntimeException('Kunci enkripsi ' . $this->keyFile . ' rusak.');
        }
        if (!$create) {
            return null;
        }
        $dir = dirname($this->keyFile);
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException('Folder kunci enkripsi tidak dapat dibuat.');
        }
        $key = random_bytes(32);
        if (file_put_contents($this->keyFile, base64_encode($key), LOCK_EX) === false) {
            throw new RuntimeException('Kunci enkripsi tidak dapat disimpan.');
        }
        @chmod($this->keyFile, 0600);
        return $key;
    }
}
