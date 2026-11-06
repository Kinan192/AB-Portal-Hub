<?php
declare(strict_types=1);

// D11: Aset IT services can have a vendor deadline ("Tenggat Vendor", kept in asset_services.data_json). The day
// before it (H-1), php bin/service-alerts.php emails a reminder. This table records each reminder sent, per service
// and deadline, so the same reminder is never sent twice and a moved deadline gets a new one.
// Only a new table; no data changes.
// Undo (`php bin/migrate.php down`): the table is dropped; reminders for open deadlines may be sent again.

return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec('CREATE TABLE IF NOT EXISTS asset_service_alerts (
            service_id VARCHAR(20) NOT NULL,
            due_date VARCHAR(10) NOT NULL,
            sent_at DATETIME NOT NULL,
            recipients VARCHAR(1000) NOT NULL DEFAULT \'\',
            PRIMARY KEY (service_id, due_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS asset_service_alerts');
    },
];
