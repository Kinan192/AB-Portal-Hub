<?php
declare(strict_types=1);

use App\Controllers\HomeController;
use App\Controllers\Forms\HardwareServerController;
use App\Controllers\Forms\KesesuaianUserController;
use App\Controllers\Forms\AntrianOnlineController;

$router->get('/', [new HomeController(), 'index']);
$router->get('/forms/hardware-server', [new HardwareServerController(), 'index']);
$router->get('/reports/hardware-server', [new HardwareServerController(), 'history']);
$router->get('/forms/kesesuaian-user', [new KesesuaianUserController(), 'index']);
$router->get('/reports/kesesuaian-user', [new KesesuaianUserController(), 'history']);
$router->get('/forms/antrian-online', [new AntrianOnlineController(), 'index']);
$router->get('/reports/antrian-online', [new AntrianOnlineController(), 'history']);
