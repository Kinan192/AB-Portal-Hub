<?php
declare(strict_types=1);

use App\Core\Migrator;

// B3: server room temperature entries (PRD §6.2, §10 temperature_entry). One entry = one sensor location
// for one month; a location can hold only one entry per month that is not deleted (FR-T1, active_key).
// The evidence file is a row in attachments (owner_type 'temperature_entry', field 'berkas').
// Data change: none; only a new, empty table.
// Undo (`php bin/migrate.php down`): the table is dropped, so every temperature entry saved since is lost
// (uploaded files stay in storage/app/private and their attachments rows stay).

return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS temperature_entries (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                entry_no VARCHAR(20) NOT NULL,
                period_month DATE NOT NULL,
                captured_at DATETIME NOT NULL,
                location_code VARCHAR(20) NOT NULL,
                avg_temp DECIMAL(4,1) NOT NULL,
                temp_status ENUM('normal','peringatan','kritis') NOT NULL,
                humidity DECIMAL(4,1) NULL DEFAULT NULL,
                humidity_status ENUM('normal','peringatan') NULL DEFAULT NULL,
                thresholds_json JSON NOT NULL,
                ac_notes TEXT NULL DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL DEFAULT NULL,
                delete_reason VARCHAR(500) NULL DEFAULT NULL,
                active_key VARCHAR(40) AS (IF(deleted_at IS NULL, CONCAT(location_code, '|', period_month), NULL)) STORED,
                UNIQUE KEY uq_temperature_entry_no (entry_no),
                UNIQUE KEY uq_temperature_location_month (active_key),
                KEY idx_temperature_period (period_month, deleted_at),
                KEY idx_temperature_status (temp_status),
                CONSTRAINT chk_temperature_period CHECK (DAYOFMONTH(period_month) = 1),
                CONSTRAINT chk_temperature_range CHECK (avg_temp BETWEEN 0 AND 60),
                CONSTRAINT chk_temperature_humidity CHECK (humidity IS NULL OR humidity BETWEEN 0 AND 100),
                CONSTRAINT chk_temperature_humidity_status CHECK ((humidity IS NULL) = (humidity_status IS NULL))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    },

    'down' => static function (PDO $pdo): void {
        if (Migrator::hasTable($pdo, 'temperature_entries')) {
            $pdo->exec('DROP TABLE temperature_entries');
        }
    },
];
