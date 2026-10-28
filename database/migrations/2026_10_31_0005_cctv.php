<?php
declare(strict_types=1);

use App\Core\Migrator;

// B4: CCTV checks (PRD §6.3, §10 cctv_entry, cctv_broken_camera). One entry = one check, normally one per
// shift; several per day are allowed. Broken cameras are rows of cctv_broken_cameras (none when NIHIL).
// inspection_date is the WIB calendar date of inspected_at (UTC), used for "done today" and FR-C3.
// Evidence files are rows in attachments (owner_type 'cctv_entry', field 'bukti', up to 5).
// Data change: none; only two new, empty tables.
// Undo (`php bin/migrate.php down`): both tables are dropped, so every CCTV entry saved since is lost
// (uploaded files stay in storage/app/private and their attachments rows stay).

return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS cctv_entries (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                entry_no VARCHAR(20) NOT NULL,
                inspected_at DATETIME NOT NULL,
                inspection_date DATE NOT NULL,
                shift ENUM('pagi','siang','malam') NOT NULL,
                total_cameras SMALLINT UNSIGNED NOT NULL,
                is_nihil TINYINT(1) NOT NULL DEFAULT 0,
                broken_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                availability_pct DECIMAL(5,1) NOT NULL,
                ntp_status ENUM('sesuai','tidak_sesuai','lainnya') NOT NULL,
                ntp_note VARCHAR(300) NULL DEFAULT NULL,
                nvr_status ENUM('normal','kritis') NOT NULL,
                retention_days SMALLINT UNSIGNED NULL DEFAULT NULL,
                nvr_note VARCHAR(300) NULL DEFAULT NULL,
                handover_note TEXT NULL DEFAULT NULL,
                entry_status ENUM('normal','peringatan','kritis') NOT NULL,
                thresholds_json JSON NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL DEFAULT NULL,
                delete_reason VARCHAR(500) NULL DEFAULT NULL,
                UNIQUE KEY uq_cctv_entry_no (entry_no),
                KEY idx_cctv_date_shift (inspection_date, shift, deleted_at),
                KEY idx_cctv_inspected (inspected_at),
                KEY idx_cctv_status (entry_status),
                CONSTRAINT chk_cctv_total CHECK (total_cameras > 0),
                CONSTRAINT chk_cctv_broken CHECK (broken_count <= total_cameras),
                CONSTRAINT chk_cctv_nihil CHECK (is_nihil = 0 OR broken_count = 0),
                CONSTRAINT chk_cctv_availability CHECK (availability_pct BETWEEN 0 AND 100),
                CONSTRAINT chk_cctv_retention CHECK (retention_days IS NULL OR retention_days <= 365)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS cctv_broken_cameras (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                cctv_entry_id BIGINT UNSIGNED NOT NULL,
                position SMALLINT UNSIGNED NOT NULL,
                camera_name VARCHAR(100) NOT NULL,
                ip_address VARCHAR(15) NOT NULL,
                zone_code VARCHAR(40) NOT NULL,
                issue_type ENUM('offline','buram','bergeser','gagal_rekam','lainnya') NOT NULL,
                note VARCHAR(200) NULL DEFAULT NULL,
                KEY idx_cctv_camera_entry (cctv_entry_id, position),
                KEY idx_cctv_camera_zone (zone_code),
                CONSTRAINT fk_cctv_camera_entry FOREIGN KEY (cctv_entry_id) REFERENCES cctv_entries (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    },

    'down' => static function (PDO $pdo): void {
        if (Migrator::hasTable($pdo, 'cctv_broken_cameras')) {
            $pdo->exec('DROP TABLE cctv_broken_cameras');
        }
        if (Migrator::hasTable($pdo, 'cctv_entries')) {
            $pdo->exec('DROP TABLE cctv_entries');
        }
    },
];
