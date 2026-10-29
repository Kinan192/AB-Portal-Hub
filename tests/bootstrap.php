<?php
declare(strict_types=1);

// Unit tests need the autoloader and BASE_PATH only; they never touch the database or .env.
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';
date_default_timezone_set('Asia/Jakarta');
