<?php
declare(strict_types=1);

use App\Controllers\HomeController;
use App\Controllers\Forms\HardwareServerController;
use App\Controllers\Forms\KesesuaianUserController;

$router->get('/', [new HomeController(), 'index']);
$router->get('/forms/hardware-server', [new HardwareServerController(), 'index']);
$router->get('/forms/hardware-server/history', [new HardwareServerController(), 'history']);
$router->get('/forms/kesesuaian-user', [new KesesuaianUserController(), 'index']);
$router->get('/forms/kesesuaian-user/history', [new KesesuaianUserController(), 'history']);
