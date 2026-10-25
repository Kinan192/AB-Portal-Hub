<?php
declare(strict_types=1);

use App\Controllers\HomeController;
use App\Controllers\Forms\HardwareServerController;

$router->get('/', [new HomeController(), 'index']);
$router->get('/forms/hardware-server', [new HardwareServerController(), 'index']);
$router->get('/forms/hardware-server/history', [new HardwareServerController(), 'history']);
