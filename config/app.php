<?php
declare(strict_types=1);

return [
    'name' => 'Portal Monitoring IT',
    'hospital' => 'RS Awal Bros Botania',
    'logo' => 'images/brand/rs-awal-bros.svg',
    'timezone' => 'Asia/Jakarta',
    'environment' => App\Core\Env::get('APP_ENV') ?: 'local',
    // Start of every link. Empty APP_URL = the folder the portal is opened from ("" at a site root,
    // "/abportal" for http://localhost/abportal in XAMPP htdocs).
    'url' => rtrim(App\Core\Env::get('APP_URL') ?: App\Core\Router::basePath($_SERVER), '/'),
];
