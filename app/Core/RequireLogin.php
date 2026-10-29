<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/**
 * Route middleware: visitors who are not signed in are sent to /login (302), fetch() calls get 401.
 * A real account is re-checked on every request: a password change elsewhere (session_version) or a
 * disabled account ends this session (PRD SEC-5).
 */
final class RequireLogin
{
    public function __invoke(string $method, string $path): bool
    {
        if (Auth::check() && $this->stillValid()) {
            return true;
        }
        if (wants_json()) {
            json_response(['message' => 'Sesi berakhir. Muat ulang halaman lalu masuk kembali.'], 401);
            return false;
        }
        if ($method === 'GET') {
            Auth::rememberIntended($path . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : ''));
        }
        redirect('/login', 302);
        return false;
    }

    private function stillValid(): bool
    {
        $user = Auth::user();
        // A database failure here is a server error (public/index.php shows the error page).
        $account = (new User(Database::connection()))->find((int) ($user['id'] ?? 0));
        if ($account === null || (int) $account['is_active'] !== 1) {
            Auth::forget('Sesi berakhir', 'Akun tidak aktif. Hubungi IT.');
            return false;
        }
        if ((int) $account['session_version'] !== (int) ($user['session_version'] ?? 0)) {
            Auth::forget('Sesi berakhir', 'Password diubah di perangkat lain. Silakan masuk kembali.');
            return false;
        }
        return true;
    }
}
