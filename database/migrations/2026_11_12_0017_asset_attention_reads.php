<?php
declare(strict_types=1);

// D10: Aset IT services waiting for spare parts ("Menunggu Suku Cadang") are shown in the "Perlu perhatian" bell
// (app/Services/Attention.php). A seen one is kept per account, service and the moment it started waiting
// ("since"), so a service that waits again later is new again. Only a new table; no data changes.
// Undo (`php bin/migrate.php down`): the table is dropped; every waiting service counts as unseen again.

return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec('CREATE TABLE IF NOT EXISTS asset_attention_reads (
            user_id BIGINT UNSIGNED NOT NULL,
            service_id VARCHAR(20) NOT NULL,
            since VARCHAR(30) NOT NULL,
            read_at DATETIME NOT NULL,
            PRIMARY KEY (user_id, service_id, since),
            CONSTRAINT asset_attention_reads_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS asset_attention_reads');
    },
];
