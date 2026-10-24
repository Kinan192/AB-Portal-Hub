<?php
declare(strict_types=1);

return [
    'name' => 'Portal Monitoring IT',
    'hospital' => 'RS Awal Bros Botania',
    'logo' => 'images/brand/rs-awal-bros.svg',
    'timezone' => 'Asia/Jakarta',
    'environment' => getenv('APP_ENV') ?: 'local',
    'url' => rtrim(getenv('APP_URL') ?: '', '/'),
];
