<?php
declare(strict_types=1);

use App\Controllers\AccountController;
use App\Controllers\AuthController;
use App\Controllers\DynamicFormController;
use App\Controllers\FormEditorController;
use App\Controllers\HomeController;
use App\Core\RequireLogin;

$home = new HomeController();
$formEditor = new FormEditorController();
$dynamicForm = new DynamicFormController();
$auth = new AuthController();
$account = new AccountController();

// Every route below needs a signed-in session unless it is marked public.
$router->middleware(new RequireLogin());

$router->get('/login', [$auth, 'showLogin'], public: true);
$router->post('/login', [$auth, 'login'], public: true);
$router->post('/logout', [$auth, 'logout']);

$router->get('/', [$home, 'index']);
$router->get('/form-editor', [$formEditor, 'edit']);
// Dynamic forms (frontend only, dummy data in the browser): fill-in page and history.
$router->get('/form-dinamis', [$dynamicForm, 'fill']);
$router->get('/form-dinamis/respons', [$dynamicForm, 'responses']);
$router->get('/password', [$account, 'password']);
$router->get('/users', [$account, 'users']);

// Built-in module forms and their history are drawn from the question list
// (database/dummy/builtin-forms.php), keyed by card id; the history also names its breadcrumb.
$router->get('/form/suhu', static fn () => $dynamicForm->builtin('suhu-server'));
$router->get('/form/temperature', static fn () => redirect('/form/suhu', 302));
$router->get('/rekap/suhu', static fn () => $dynamicForm->history('suhu-server', 'suhu'));

$router->get('/form/cctv', static fn () => $dynamicForm->builtin('cctv'));
$router->get('/rekap/cctv', static fn () => $dynamicForm->history('cctv', 'cctv'));

$router->get('/form/cleardesk', static fn () => $dynamicForm->builtin('clear-desk'));
$router->get('/rekap/cleardesk', static fn () => $dynamicForm->history('clear-desk', 'cleardesk'));

$router->get('/forms/hardware-server', static fn () => $dynamicForm->builtin('hardware-server'));
$router->get('/reports/hardware-server', static fn () => $dynamicForm->history('hardware-server', 'hardware-server'));
$router->get('/forms/kesesuaian-user', static fn () => $dynamicForm->builtin('kesesuaian-user'));
$router->get('/reports/kesesuaian-user', static fn () => $dynamicForm->history('kesesuaian-user', 'kesesuaian-user'));
$router->get('/forms/antrian-online', static fn () => $dynamicForm->builtin('antrian-online'));
$router->get('/reports/antrian-online', static fn () => $dynamicForm->history('antrian-online', 'antrian-online'));
