<?php
declare(strict_types=1);

use App\Core\Migrator;

// B1: single admin login by username (PRD admin_user), session version, login throttle, change log columns.
// Data change: users.username is filled from users.email; users.role is removed (one admin, no roles).
// Undo (`php bin/migrate.php down`): role comes back as 'admin' for every user, username and the new
// columns and table are removed. Login attempts recorded since are lost.

return [
    'up' => static function (PDO $pdo): void {
        if (!Migrator::hasColumn($pdo, 'users', 'username')) {
            $pdo->exec('ALTER TABLE users ADD COLUMN username VARCHAR(100) NULL AFTER id');
        }
        $pdo->exec('UPDATE users SET username = LEFT(email, 100) WHERE username IS NULL OR username = \'\'');
        $pdo->exec('ALTER TABLE users MODIFY username VARCHAR(100) NOT NULL');
        if (!Migrator::hasIndex($pdo, 'users', 'uq_users_username')) {
            $pdo->exec('ALTER TABLE users ADD UNIQUE KEY uq_users_username (username)');
        }
        if (!Migrator::hasColumn($pdo, 'users', 'password_changed_at')) {
            $pdo->exec('ALTER TABLE users ADD COLUMN password_changed_at DATETIME NULL DEFAULT NULL AFTER password_hash');
        }
        if (!Migrator::hasColumn($pdo, 'users', 'session_version')) {
            // Raised on every password change so other signed-in devices are logged out (SEC-5).
            $pdo->exec('ALTER TABLE users ADD COLUMN session_version INT UNSIGNED NOT NULL DEFAULT 1 AFTER password_changed_at');
        }
        if (Migrator::hasColumn($pdo, 'users', 'role')) {
            $pdo->exec('ALTER TABLE users DROP COLUMN role');
        }

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS login_attempts (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) NOT NULL,
                ip VARCHAR(45) NOT NULL,
                succeeded TINYINT(1) NOT NULL DEFAULT 0,
                attempted_at DATETIME(3) NOT NULL,
                KEY idx_login_attempts_user (username, ip, attempted_at),
                KEY idx_login_attempts_ip (ip, attempted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        if (!Migrator::hasColumn($pdo, 'audit_logs', 'entry_no')) {
            $pdo->exec('ALTER TABLE audit_logs ADD COLUMN entry_no VARCHAR(30) NULL DEFAULT NULL AFTER entity_id');
        }
        if (!Migrator::hasColumn($pdo, 'audit_logs', 'reason')) {
            $pdo->exec('ALTER TABLE audit_logs ADD COLUMN reason VARCHAR(500) NULL DEFAULT NULL AFTER entry_no');
        }
        if (!Migrator::hasIndex($pdo, 'audit_logs', 'idx_audit_logs_action')) {
            $pdo->exec('ALTER TABLE audit_logs ADD KEY idx_audit_logs_action (action, created_at)');
        }
    },

    'down' => static function (PDO $pdo): void {
        if (Migrator::hasIndex($pdo, 'audit_logs', 'idx_audit_logs_action')) {
            $pdo->exec('ALTER TABLE audit_logs DROP INDEX idx_audit_logs_action');
        }
        foreach (['reason', 'entry_no'] as $column) {
            if (Migrator::hasColumn($pdo, 'audit_logs', $column)) {
                $pdo->exec("ALTER TABLE audit_logs DROP COLUMN {$column}");
            }
        }
        $pdo->exec('DROP TABLE IF EXISTS login_attempts');

        if (!Migrator::hasColumn($pdo, 'users', 'role')) {
            $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('admin', 'it_staff', 'supervisor') NOT NULL DEFAULT 'it_staff' AFTER password_hash");
            $pdo->exec("UPDATE users SET role = 'admin'");
        }
        if (Migrator::hasIndex($pdo, 'users', 'uq_users_username')) {
            $pdo->exec('ALTER TABLE users DROP INDEX uq_users_username');
        }
        foreach (['session_version', 'password_changed_at', 'username'] as $column) {
            if (Migrator::hasColumn($pdo, 'users', $column)) {
                $pdo->exec("ALTER TABLE users DROP COLUMN {$column}");
            }
        }
    },
];
