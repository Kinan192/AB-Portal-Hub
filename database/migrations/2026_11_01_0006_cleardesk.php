<?php
declare(strict_types=1);

use App\Core\Migrator;

// B5: Clear Desk & Clear Screen sessions (PRD §6.4, §10 cleardesk_session, cleardesk_row). One session = one
// inspection round with one or more workstation rows (no maximum). inspection_date is the WIB calendar date
// of started_at (UTC), used for "done today". Row photos are rows in attachments (owner_type
// 'cleardesk_session', field 'foto'), referenced by cleardesk_rows.photo_attachment_id.
// Data change: none; only two new, empty tables.
// Undo (`php bin/migrate.php down`): both tables are dropped, so every Clear Desk session saved since is lost
// (uploaded photos stay in storage/app/private and their attachments rows stay).

return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS cleardesk_sessions (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                session_no VARCHAR(20) NOT NULL,
                started_at DATETIME NOT NULL,
                inspection_date DATE NOT NULL,
                shift ENUM('pagi','siang','malam') NOT NULL,
                notes TEXT NULL DEFAULT NULL,
                row_count INT UNSIGNED NOT NULL,
                compliant_count INT UNSIGNED NOT NULL,
                violation_count INT UNSIGNED NOT NULL,
                compliance_pct DECIMAL(5,1) NOT NULL,
                thresholds_json JSON NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL DEFAULT NULL,
                delete_reason VARCHAR(500) NULL DEFAULT NULL,
                UNIQUE KEY uq_cleardesk_session_no (session_no),
                KEY idx_cleardesk_date (inspection_date, deleted_at),
                KEY idx_cleardesk_started (started_at),
                CONSTRAINT chk_cleardesk_rows CHECK (row_count >= 1 AND compliant_count + violation_count = row_count),
                CONSTRAINT chk_cleardesk_pct CHECK (compliance_pct BETWEEN 0 AND 100)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS cleardesk_rows (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                session_id BIGINT UNSIGNED NOT NULL,
                position INT UNSIGNED NOT NULL,
                mac_address CHAR(17) NOT NULL,
                location_unit VARCHAR(150) NOT NULL,
                ip_address VARCHAR(15) NOT NULL,
                os ENUM('windows-11','windows-10','windows-7-8','linux','macos','lainnya') NOT NULL,
                os_other VARCHAR(50) NULL DEFAULT NULL,
                compliance ENUM('patuh','temuan') NOT NULL,
                finding_categories JSON NULL DEFAULT NULL,
                finding_note VARCHAR(500) NULL DEFAULT NULL,
                follow_up ENUM('ditegur','dilaporkan','lainnya') NULL DEFAULT NULL,
                photo_attachment_id BIGINT UNSIGNED NULL DEFAULT NULL,
                UNIQUE KEY uq_cleardesk_row_mac (session_id, mac_address),
                KEY idx_cleardesk_row_position (session_id, position),
                KEY idx_cleardesk_row_mac (mac_address),
                KEY idx_cleardesk_row_location (location_unit),
                CONSTRAINT fk_cleardesk_row_session FOREIGN KEY (session_id) REFERENCES cleardesk_sessions (id) ON DELETE CASCADE,
                CONSTRAINT chk_cleardesk_row_finding CHECK (compliance = 'temuan' OR (finding_categories IS NULL AND finding_note IS NULL))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    },

    'down' => static function (PDO $pdo): void {
        if (Migrator::hasTable($pdo, 'cleardesk_rows')) {
            $pdo->exec('DROP TABLE cleardesk_rows');
        }
        if (Migrator::hasTable($pdo, 'cleardesk_sessions')) {
            $pdo->exec('DROP TABLE cleardesk_sessions');
        }
    },
];
