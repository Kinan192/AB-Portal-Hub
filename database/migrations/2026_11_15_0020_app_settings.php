<?php
declare(strict_types=1);

// D12: settings changed from the portal instead of .env. First use: the SMTP email settings of the Aset IT
// vendor-deadline reminders (side menu "Pengaturan Email", App\Services\MailSettings). The SMTP password is stored
// encrypted (App\Services\Secret, key file storage/keys/secret.key). Only a new table; no data changes.
// Undo (`php bin/migrate.php down`): the table is dropped; the email settings must be entered again.

return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec('CREATE TABLE IF NOT EXISTS app_settings (
            name VARCHAR(64) NOT NULL PRIMARY KEY,
            value_json TEXT NOT NULL,
            updated_at DATETIME NOT NULL,
            updated_by VARCHAR(150) NOT NULL DEFAULT \'\'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS app_settings');
    },
];
