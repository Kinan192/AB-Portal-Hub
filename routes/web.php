<?php
declare(strict_types=1);

use App\Controllers\AccountController;
use App\Controllers\AuditController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\DynamicFormController;
use App\Controllers\FileController;
use App\Controllers\FormEditorController;
use App\Controllers\HomeController;
use App\Core\RequireLogin;
use App\Core\VerifyCsrf;

$home = new HomeController();
$dashboard = new DashboardController();
$formEditor = new FormEditorController();
$dynamicForm = new DynamicFormController();
$auth = new AuthController();
$account = new AccountController();
$audit = new AuditController();
$files = new FileController();

// Every POST (public ones included) must carry the session's CSRF token.
$router->middleware(new VerifyCsrf(), always: true);
// Every route below needs a signed-in session unless it is marked public.
$router->middleware(new RequireLogin());

$router->get('/login', [$auth, 'showLogin'], public: true);
$router->post('/login', [$auth, 'login'], public: true);
$router->post('/logout', [$auth, 'logout']);
$router->get('/password', [$account, 'password']);
$router->post('/password', [$account, 'updatePassword']);
// Change log, read only (B9, PRD SEC-12): no route changes audit_logs.
$router->get('/riwayat-aktivitas', [$audit, 'index']);

$router->get('/', [$home, 'index']);
// Dashboard JSON API (public/assets/js/dashboard.js): fetch() POSTs with the X-CSRF-Token header.
$router->post('/dashboard/sections', [$dashboard, 'createSection']);
$router->post('/dashboard/sections/{id}/delete', [$dashboard, 'deleteSection']);
$router->post('/dashboard/forms', [$dashboard, 'createForm']);
$router->post('/dashboard/links', [$dashboard, 'createLink']);
$router->post('/dashboard/cards/{id}', [$dashboard, 'updateCard']);
$router->post('/dashboard/cards/{id}/hide', [$dashboard, 'hide']);
$router->post('/dashboard/cards/{id}/show', [$dashboard, 'show']);
$router->post('/dashboard/cards/{id}/move', [$dashboard, 'move']);
$router->post('/dashboard/cards/{id}/duplicate', [$dashboard, 'duplicate']);
$router->post('/dashboard/cards/{id}/delete', [$dashboard, 'delete']);

// Edit Isi Form: the page, and saving it (JSON; questions of a dynamic form become a new version).
$router->get('/form-editor', [$formEditor, 'edit']);
$router->post('/form-editor', [$formEditor, 'save']);
$router->post('/form-editor/pratinjau', [$formEditor, 'preview']);
// Dynamic forms (B6): fill-in page, reports saved on the server, history (DynamicFormController).
$router->get('/form-dinamis', [$dynamicForm, 'fill']);
$router->post('/form-dinamis/entri', [$dynamicForm, 'store']);
$router->get('/form-dinamis/entri/{id}/ubah', [$dynamicForm, 'edit']);
$router->post('/form-dinamis/entri/{id}', [$dynamicForm, 'update']);
$router->post('/form-dinamis/entri/{id}/hapus', [$dynamicForm, 'destroy']);
$router->get('/form-dinamis/entri/{id}', [$dynamicForm, 'detail']);
$router->get('/form-dinamis/respons', [$dynamicForm, 'responses']);
$router->get('/form-dinamis/respons/data', [$dynamicForm, 'data']);
// Exports of the recap pages (B8, PRD §9): ?format=xlsx|pdf|cetak plus the filters of the page.
$router->get('/form-dinamis/respons/ekspor', [$dynamicForm, 'export']);

// Uploaded files of the reports (only for a signed-in session).
$router->get('/berkas/{id}', [$files, 'show']);
