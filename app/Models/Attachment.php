<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use RuntimeException;

/**
 * Uploaded files (attachments). A file is stored outside the web root under
 * storage/app/private/YYYY/MM/<32 hex>.<ext> with its SHA-256; the original name is kept only in the
 * database. Files are never removed from disk (data is kept indefinitely); an attachment is only marked
 * deleted. FileController serves them to a signed-in session (/berkas/{id}).
 */
final class Attachment
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public static function root(): string
    {
        return BASE_PATH . '/storage/app/private';
    }

    /** Moves an uploaded file into storage and records it. @return int the attachment id */
    public function storeUpload(string $tmpPath, string $originalName, string $ext, string $mime, string $ownerType, int $ownerId, string $field): int
    {
        $relative = gmdate('Y/m') . '/' . bin2hex(random_bytes(16)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
        $target = self::root() . '/' . $relative;
        if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0750, true) && !is_dir(dirname($target))) {
            throw new RuntimeException('Folder penyimpanan berkas tidak dapat dibuat.');
        }
        if (!move_uploaded_file($tmpPath, $target)) {
            throw new RuntimeException('Berkas tidak dapat dipindahkan ke penyimpanan.');
        }
        @chmod($target, 0640);
        $name = trim((string) preg_replace('/[\x00-\x1f\x7f\/\\\\]+/u', '', basename(str_replace('\\', '/', $originalName))));
        $this->pdo->prepare(
            'INSERT INTO attachments (owner_type, owner_id, field, original_name, stored_path, mime_type, size_bytes, sha256)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$ownerType, $ownerId, $field, mb_substr($name !== '' ? $name : 'berkas.' . $ext, 0, 255), $relative, $mime, (int) filesize($target), hash_file('sha256', $target)]);
        return (int) $this->pdo->lastInsertId();
    }

    /** An attachment that is not deleted. */
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, owner_type, owner_id, field, original_name, stored_path, mime_type, size_bytes FROM attachments WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    /** The file of one field of an owner (e.g. the evidence of a temperature entry), or null. */
    public function forOwner(string $ownerType, int $ownerId, string $field): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, original_name, mime_type, size_bytes FROM attachments
             WHERE owner_type = ? AND owner_id = ? AND field = ? AND deleted_at IS NULL ORDER BY id DESC LIMIT 1'
        );
        $statement->execute([$ownerType, $ownerId, $field]);
        return $statement->fetch() ?: null;
    }

    /** Every file of one field of an owner, oldest first (e.g. the up to 5 evidence files of a CCTV entry). */
    public function allForOwner(string $ownerType, int $ownerId, string $field): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, original_name, mime_type, size_bytes FROM attachments
             WHERE owner_type = ? AND owner_id = ? AND field = ? AND deleted_at IS NULL ORDER BY id'
        );
        $statement->execute([$ownerType, $ownerId, $field]);
        return $statement->fetchAll();
    }

    /** Marks some files of an owner deleted; ids of other owners are ignored. The files stay on disk. */
    public function deleteIds(string $ownerType, int $ownerId, array $ids): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0));
        if ($ids === []) {
            return;
        }
        $this->pdo->prepare(
            'UPDATE attachments SET deleted_at = UTC_TIMESTAMP() WHERE owner_type = ? AND owner_id = ? AND deleted_at IS NULL AND id IN ('
            . implode(', ', array_fill(0, count($ids), '?')) . ')'
        )->execute([$ownerType, $ownerId, ...$ids]);
    }

    /** Marks the files of an owner deleted (all fields, or one). The files stay on disk. */
    public function deleteForOwner(string $ownerType, int $ownerId, ?string $field = null): void
    {
        $sql = 'UPDATE attachments SET deleted_at = UTC_TIMESTAMP() WHERE owner_type = ? AND owner_id = ? AND deleted_at IS NULL';
        $params = [$ownerType, $ownerId];
        if ($field !== null) {
            $sql .= ' AND field = ?';
            $params[] = $field;
        }
        $this->pdo->prepare($sql)->execute($params);
    }

    /** Absolute path of a stored file, or null when it is missing or outside the storage folder. */
    public static function path(array $attachment): ?string
    {
        $root = realpath(self::root());
        $path = realpath(self::root() . '/' . $attachment['stored_path']);
        if ($root === false || $path === false || !str_starts_with($path, $root . DIRECTORY_SEPARATOR) || !is_file($path)) {
            return null;
        }
        return $path;
    }
}
