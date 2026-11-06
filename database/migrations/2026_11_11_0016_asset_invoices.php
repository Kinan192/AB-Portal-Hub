<?php
declare(strict_types=1);

// D9: purchase invoices of an Aset IT device (Registrasi Perangkat): a JSON list of uploaded files
// [{id, name, type, size}], the files themselves are attachments (owner_type "asset", /berkas/{id}).
// Service and disposal documents live in their data_json already. Only a new column; no data changes.
// Undo (`php bin/migrate.php down`): the column is dropped with the invoice lists in it (the files stay).

return [
    'up' => static function (PDO $pdo): void {
        $has = $pdo->query("SHOW COLUMNS FROM asset_devices LIKE 'invoices_json'")->fetch();
        if ($has === false) {
            $pdo->exec('ALTER TABLE asset_devices ADD COLUMN invoices_json TEXT NULL AFTER spec_json');
        }
    },
    'down' => static function (PDO $pdo): void {
        $has = $pdo->query("SHOW COLUMNS FROM asset_devices LIKE 'invoices_json'")->fetch();
        if ($has !== false) {
            $pdo->exec('ALTER TABLE asset_devices DROP COLUMN invoices_json');
        }
    },
];
