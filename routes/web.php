<?php
declare(strict_types=1);

use App\Controllers\HomeController;

$router->get('/', [new HomeController(), 'index']);
