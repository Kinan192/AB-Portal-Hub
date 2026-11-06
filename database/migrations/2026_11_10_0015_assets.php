<?php
declare(strict_types=1);

// D8: IT Asset Management (Aset IT): devices identified by their barcode, location moves, service tickets,
// disposal requests and a per-device activity log (app/Services/AssetLedger.php). Nothing is ever hard-deleted:
// a disposed device stays with status "disposed". Only new tables; no existing data changes.
// Undo (`php bin/migrate.php down`): the five tables are dropped with every asset record in them.

return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS asset_devices (
            id VARCHAR(64) NOT NULL PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            category VARCHAR(20) NOT NULL,
            brand VARCHAR(100) NOT NULL DEFAULT '',
            model VARCHAR(150) NOT NULL DEFAULT '',
            serial VARCHAR(100) NOT NULL DEFAULT '',
            ip VARCHAR(45) NOT NULL DEFAULT '',
            mac VARCHAR(17) NOT NULL DEFAULT '',
            location_json TEXT NOT NULL,
            pic VARCHAR(150) NOT NULL DEFAULT '',
            status ENUM('aktif','cadangan','rusak','servis','disposal','disposed') NOT NULL DEFAULT 'aktif',
            purchase_date VARCHAR(10) NOT NULL DEFAULT '',
            warranty_end VARCHAR(10) NOT NULL DEFAULT '',
            notes TEXT NOT NULL,
            spec_json TEXT NOT NULL,
            disposed_at VARCHAR(10) NOT NULL DEFAULT '',
            disposal_id VARCHAR(20) NOT NULL DEFAULT '',
            created_at VARCHAR(30) NOT NULL,
            created_by VARCHAR(150) NOT NULL DEFAULT '',
            updated_at VARCHAR(30) NOT NULL,
            updated_by VARCHAR(150) NOT NULL DEFAULT '',
            KEY asset_devices_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS asset_moves (
            id VARCHAR(20) NOT NULL PRIMARY KEY,
            device_id VARCHAR(64) NOT NULL,
            data_json TEXT NOT NULL,
            at VARCHAR(30) NOT NULL,
            KEY asset_moves_device (device_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS asset_services (
            id VARCHAR(20) NOT NULL PRIMARY KEY,
            device_id VARCHAR(64) NOT NULL,
            status VARCHAR(20) NOT NULL,
            data_json MEDIUMTEXT NOT NULL,
            created_at VARCHAR(30) NOT NULL,
            KEY asset_services_device (device_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS asset_disposals (
            id VARCHAR(20) NOT NULL PRIMARY KEY,
            device_id VARCHAR(64) NOT NULL,
            status VARCHAR(20) NOT NULL,
            data_json MEDIUMTEXT NOT NULL,
            requested_at VARCHAR(30) NOT NULL,
            KEY asset_disposals_device (device_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS asset_activity (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            device_id VARCHAR(64) NOT NULL,
            type VARCHAR(20) NOT NULL,
            text VARCHAR(500) NOT NULL,
            at VARCHAR(30) NOT NULL,
            by_name VARCHAR(150) NOT NULL DEFAULT '',
            KEY asset_activity_device (device_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    },
    'down' => static function (PDO $pdo): void {
        foreach (['asset_activity', 'asset_disposals', 'asset_services', 'asset_moves', 'asset_devices'] as $table) {
            $pdo->exec("DROP TABLE IF EXISTS {$table}");
        }
    },
];
