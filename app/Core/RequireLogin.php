<?php
declare(strict_types=1);

namespace App\Core;

/** Route middleware: visitors who are not signed in are sent to /login (302). */
final class RequireLogin
{
    public function __invoke(string $method, string $path): bool
    {
        if (Auth::check()) {
            return true;
        }
        if ($method === 'GET') {
            Auth::rememberIntended($path . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : ''));
        }
        redirect('/login', 302);
        return false;
    }
}
