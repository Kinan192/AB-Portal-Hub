<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

/** The single admin account (users table). Passwords are stored only as password_hash() values. */
final class User
{
    private const COLUMNS = 'id, username, name, email, password_hash, is_active, session_version, password_changed_at';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByUsername(string $username): ?array
    {
        $statement = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM users WHERE username = ? LIMIT 1');
        $statement->execute([$username]);
        return $statement->fetch() ?: null;
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM users WHERE id = ? LIMIT 1');
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    public function recordLogin(int $id, ?string $newHash): void
    {
        if ($newHash !== null) {
            $this->pdo->prepare('UPDATE users SET password_hash = ?, last_login_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$newHash, $id]);
            return;
        }
        $this->pdo->prepare('UPDATE users SET last_login_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$id]);
    }

    /** New password; the session version goes up so every other signed-in device is logged out (SEC-5). */
    public function changePassword(int $id, string $hash): int
    {
        $this->pdo->prepare(
            'UPDATE users SET password_hash = ?, password_changed_at = UTC_TIMESTAMP(), session_version = session_version + 1 WHERE id = ?'
        )->execute([$hash, $id]);
        return (int) ($this->find($id)['session_version'] ?? 1);
    }

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
    }

    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_DEFAULT, ['cost' => 12]);
    }
}
