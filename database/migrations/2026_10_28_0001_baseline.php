<?php
declare(strict_types=1);

use App\Core\Migrator;

// Baseline: the tables of database/schema.sql (users, forms, form_submissions, audit_logs). Every
// statement is CREATE TABLE IF NOT EXISTS, so on an existing database nothing changes.
// Undoing the baseline would remove every table, so `down` leaves them in place.

return [
    'up' => static function (PDO $pdo): void {
        $sql = (string) file_get_contents(BASE_PATH . '/database/schema.sql');
        foreach (Migrator::statements($sql) as $statement) {
            $pdo->exec($statement);
        }
    },
    'down' => static function (PDO $pdo): void {
        echo '    (baseline: tabel dasar tidak dihapus)' . PHP_EOL;
    },
];
