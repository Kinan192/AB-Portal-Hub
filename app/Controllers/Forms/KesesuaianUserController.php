<?php
declare(strict_types=1);

namespace App\Controllers\Forms;

use App\Core\View;

final class KesesuaianUserController
{
    public function index(): void
    {
        if (config('app')['environment'] !== 'local') {
            http_response_code(503);
            View::render('errors/unavailable', ['pageTitle' => 'Portal belum tersedia']);
            return;
        }

        View::render('forms/kesesuaian-user', [
            'pageTitle' => 'Form Kesesuaian User (HIS)',
            'profile' => config('dashboard')['profile'],
            'verification' => config('kesesuaian-user'),
            'pageScripts' => ['js/kesesuaian-user.js'],
            'loginDate' => date('Y-m-d'),
            'loginTime' => date('H:i'),
        ]);
    }
}
