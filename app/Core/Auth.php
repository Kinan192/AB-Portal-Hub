<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Session-backed sign-in state, CSRF token and the page to return to after login (PRD SEC-1, SEC-7).
 * Idle timeout 30 minutes, absolute timeout 12 hours; the session id changes at login.
 */
final class Auth
{
    private const USER = 'auth.user';
    private const LAST_SEEN = 'auth.last_seen';
    private const INTENDED = 'auth.intended';
    private const CSRF = 'csrf_token';
    public const IDLE_SECONDS = 1800;
    public const ABSOLUTE_SECONDS = 43200;

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        // Sessions must outlive the absolute timeout; PHP's default (24 minutes) is shorter than the idle limit.
        ini_set('session.gc_maxlifetime', (string) self::ABSOLUTE_SECONDS);
        session_save_path(BASE_PATH . '/storage/sessions');
        session_name('abportal_session');
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => SecurityHeaders::https(), 'httponly' => true, 'samesite' => 'Lax']);
        session_start();
        if (isset($_SESSION[self::USER])) {
            $reason = self::expired(time(), (int) ($_SESSION[self::LAST_SEEN] ?? 0), (int) ($_SESSION[self::USER]['logged_in_at'] ?? 0));
            if ($reason !== null) {
                unset($_SESSION[self::USER]);
                Flash::set('Sesi berakhir', $reason, 'info');
            } else {
                $_SESSION[self::LAST_SEEN] = time();
            }
        }
    }

    /** Why a session is no longer valid, or null while it is (pure, see tests). */
    public static function expired(int $now, int $lastSeen, int $loggedInAt): ?string
    {
        if ($now - $loggedInAt > self::ABSOLUTE_SECONDS) {
            return 'Sesi lebih dari 12 jam. Silakan masuk kembali.';
        }
        if ($now - $lastSeen > self::IDLE_SECONDS) {
            return 'Tidak ada aktivitas selama 30 menit. Silakan masuk kembali.';
        }
        return null;
    }

    public static function check(): bool
    {
        return isset($_SESSION[self::USER]);
    }

    /** @return array{id: ?int, name: string, username: string, email: string, session_version?: int, logged_in_at: int}|null */
    public static function user(): ?array
    {
        $user = $_SESSION[self::USER] ?? null;
        return is_array($user) ? $user : null;
    }

    /** @param array{id: ?int, name: string, username: string, email: string, session_version?: int} $user */
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION[self::USER] = $user + ['logged_in_at' => time()];
        $_SESSION[self::LAST_SEEN] = time();
        unset($_SESSION[self::CSRF]);
    }

    /** After the password changed here: this session stays valid with the new version. */
    public static function refresh(int $sessionVersion): void
    {
        session_regenerate_id(true);
        if (isset($_SESSION[self::USER])) {
            $_SESSION[self::USER]['session_version'] = $sessionVersion;
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600] + array_intersect_key($params, array_flip(['path', 'domain', 'secure', 'httponly', 'samesite'])));
        session_destroy();
    }

    /** Ends this sign-in but keeps the session for a message on the login page. */
    public static function forget(string $title, string $description): void
    {
        unset($_SESSION[self::USER]);
        session_regenerate_id(true);
        Flash::set($title, $description, 'info');
    }

    public static function rememberIntended(string $path): void
    {
        $_SESSION[self::INTENDED] = $path;
    }

    /** The page the visitor asked for before being sent to /login (local paths only). */
    public static function pullIntended(string $default = '/'): string
    {
        $path = (string) ($_SESSION[self::INTENDED] ?? '');
        unset($_SESSION[self::INTENDED]);
        return str_starts_with($path, '/') && !str_starts_with($path, '//') ? $path : $default;
    }

    public static function csrfToken(): string
    {
        return $_SESSION[self::CSRF] ??= bin2hex(random_bytes(32));
    }

    public static function verifyCsrf(mixed $token): bool
    {
        return is_string($token) && isset($_SESSION[self::CSRF]) && hash_equals($_SESSION[self::CSRF], $token);
    }
}
