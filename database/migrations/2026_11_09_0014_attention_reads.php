<?php
declare(strict_types=1);

// D7: the header bell marks a "Perlu perhatian" report as seen per account (app/Services/Attention.php), so
// the badge counts only the unseen ones and a seen item is drawn faded. One row per account and report.
// Undo (`php bin/migrate.php down`): the table is dropped; every report counts as unseen again.

return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec('CREATE TABLE IF NOT EXISTS attention_reads (
            user_id BIGINT UNSIGNED NOT NULL,
            entry_id BIGINT UNSIGNED NOT NULL,
            read_at DATETIME NOT NULL,
            PRIMARY KEY (user_id, entry_id),
            KEY attention_reads_entry (entry_id),
            CONSTRAINT attention_reads_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
            CONSTRAINT attention_reads_entry FOREIGN KEY (entry_id) REFERENCES form_entries (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS attention_reads');
    },
];
