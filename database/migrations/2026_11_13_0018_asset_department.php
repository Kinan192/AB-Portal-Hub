<?php
declare(strict_types=1);

// D11: the department (Departemen) a device of Aset IT belongs to, asked in Registrasi Perangkat. Separate from
// its location (Penempatan): a device can belong to one department and stand in another room. Only a new
// column; existing devices keep an empty department until they are edited.
// Undo (`php bin/migrate.php down`): the column is dropped with the departments in it.

return [
    'up' => static function (PDO $pdo): void {
        if ($pdo->query("SHOW COLUMNS FROM asset_devices LIKE 'department'")->fetch() === false) {
            $pdo->exec("ALTER TABLE asset_devices ADD COLUMN department VARCHAR(100) NOT NULL DEFAULT '' AFTER name");
        }
    },
    'down' => static function (PDO $pdo): void {
        if ($pdo->query("SHOW COLUMNS FROM asset_devices LIKE 'department'")->fetch() !== false) {
            $pdo->exec('ALTER TABLE asset_devices DROP COLUMN department');
        }
    },
];
