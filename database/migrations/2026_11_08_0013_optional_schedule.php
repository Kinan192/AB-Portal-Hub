<?php
declare(strict_types=1);

// D6: schedule type "optional" ("Tidak wajib diisi"): the form can be filled any time but is never due, so it is
// not in "Jadwal Hari Ini", the daily progress or "Belum Diisi" (app/Helpers/Schedule.php).
// Undo (`php bin/migrate.php down`): forms with this type become "daily" again, then the type is removed.

return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec("ALTER TABLE form_schedules MODIFY type ENUM('daily','weekly','monthly','yearly','dates','optional') NOT NULL DEFAULT 'daily'");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec("UPDATE form_schedules SET type = 'daily' WHERE type = 'optional'");
        $pdo->exec("ALTER TABLE form_schedules MODIFY type ENUM('daily','weekly','monthly','yearly','dates') NOT NULL DEFAULT 'daily'");
    },
];
