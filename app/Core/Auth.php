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
        // HTTPS and plain HTTP (the same host can be opened both ways on the LAN) use different cookie names: a
        // browser never lets an http:// page replace a Secure cookie, so a shared name would make every login over
        // http:// fail once the device had signed in over https:// (the session cookie could not be set).
        $secure = SecurityHeaders::https();
        if ($secure && isset($_COOKIE[self::cookieName(false)])) {
            // A Secure cookie under the plain name, left by the former shared name, still blocks http:// logins;
            // only an https:// response can remove it.
            setcookie(self::cookieName(false), '', ['expires' => time() - 3600, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
        }
        session_name(self::cookieName($secure));
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
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

    /** Session cookie name: "__Secure-" prefixed over HTTPS (the browser only accepts it there as a Secure cookie). */
    public static function cookieName(bool $https): string
    {
        return $https ? '__Secure-abportal_session' : 'abportal_session';
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

    public static function csrfToken(): string
    {
        return $_SESSION[self::CSRF] ??= bin2hex(random_bytes(32));
    }

    public static function verifyCsrf(mixed $token): bool
    {
        return is_string($token) && isset($_SESSION[self::CSRF]) && hash_equals($_SESSION[self::CSRF], $token);
    }
}
