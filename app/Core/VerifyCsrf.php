<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Middleware for every POST, public routes included (PRD SEC-10). The token comes from the hidden
 * `_token` field (csrf_field()) or, for fetch(), the X-CSRF-Token header (<meta name="csrf-token">).
 * A missing or wrong token stops the request: JSON callers get 419, pages go back with a message.
 */
final class VerifyCsrf
{
    public const MESSAGE = 'Sesi formulir sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.';

    public function __invoke(string $method, string $path): bool
    {
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return true;
        }
        $token = $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (Auth::verifyCsrf($token)) {
            return true;
        }
        if (wants_json()) {
            json_response(['message' => self::MESSAGE], 419);
            return false;
        }
        Flash::set('Permintaan ditolak', self::MESSAGE, 'error');
        redirect(self::back($path), 303);
        return false;
    }

    /** The page the form came from (same site only), else the login page or the dashboard. */
    private static function back(string $path): string
    {
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $parts = parse_url($referer);
        if ($referer !== '' && is_array($parts) && ($parts['host'] ?? '') !== '' && $host !== ''
            && strcasecmp($parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : ''), $host) === 0) {
            // redirect() adds the portal folder again, so it is taken off the referring address first.
            $back = Router::withoutBase($parts['path'] ?? '/', url_base()) . (isset($parts['query']) ? '?' . $parts['query'] : '');
            if (str_starts_with($back, '/') && !str_starts_with($back, '//')) {
                return $back;
            }
        }
        return $path === '/login' || !Auth::check() ? '/login' : '/';
    }
}
