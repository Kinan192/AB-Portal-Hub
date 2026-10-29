<?php
declare(strict_types=1);

use App\Core\Migrator;

// B6: answers of the dynamic forms (Hardware Server, Kesesuaian User, Antrian Online and forms made on the
// dashboard). One entry = one submitted form. Each entry keeps the form version it was filled in
// (version_id), so a report sent before the questions changed still shows its own questions. answers_json
// holds the answers by question id; search_text the readable answers for the recap search; attention marks
// an answer that needs a look (a red option, a grid finding, a temperature outside Normal). Uploaded files
// are rows in attachments (owner_type 'form_entry', field = question id). entry_date is the WIB date of
// submitted_at (UTC), used for "done today".
// Data change: none; only a new, empty table. Editor saves add rows to the existing form_versions table.
// Undo (`php bin/migrate.php down`): the table is dropped, so every dynamic form report saved since is lost
// (uploaded files stay in storage/app/private and their attachments rows stay; form versions stay).

return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS form_entries (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                form_id BIGINT UNSIGNED NOT NULL,
                version_id BIGINT UNSIGNED NOT NULL,
                entry_no VARCHAR(24) NOT NULL,
                submitted_at DATETIME NOT NULL,
                entry_date DATE NOT NULL,
                answers_json JSON NOT NULL,
                search_text TEXT NOT NULL,
                attention TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL DEFAULT NULL,
                delete_reason VARCHAR(500) NULL DEFAULT NULL,
                UNIQUE KEY uq_form_entry_no (entry_no),
                KEY idx_form_entries_form (form_id, deleted_at, submitted_at),
                KEY idx_form_entries_date (entry_date),
                CONSTRAINT fk_form_entries_form FOREIGN KEY (form_id) REFERENCES forms (id),
                CONSTRAINT fk_form_entries_version FOREIGN KEY (version_id) REFERENCES form_versions (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    },

    'down' => static function (PDO $pdo): void {
        if (Migrator::hasTable($pdo, 'form_entries')) {
            $pdo->exec('DROP TABLE form_entries');
        }
    },
];
