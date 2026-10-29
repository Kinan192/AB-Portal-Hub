<?php
declare(strict_types=1);

// Values come from the environment (.env locally); never hard-code credentials here.
return [
    'host' => App\Core\Env::get('DB_HOST') ?: '127.0.0.1',
    'port' => App\Core\Env::get('DB_PORT') ?: '3306',
    'database' => App\Core\Env::get('DB_DATABASE') ?: 'abportal_db',
    'username' => App\Core\Env::get('DB_USERNAME') ?: '',
    'password' => App\Core\Env::get('DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
    // Stored times are UTC (PRD §10); pages show them in WIB (config/app.php timezone).
    'timezone' => '+00:00',
];
