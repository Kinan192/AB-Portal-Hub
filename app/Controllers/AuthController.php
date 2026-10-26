<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use Throwable;

final class AuthController extends Controller
{
    private const DEV_BYPASS_LOG = '/storage/logs/auth-dev-bypass.log';

    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/', 302);
            return;
        }
        $this->renderLogin();
    }

    public function login(): void
    {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if (!Auth::verifyCsrf($_POST['_token'] ?? null)) {
            http_response_code(419);
            $this->renderLogin($username, 'Sesi formulir sudah kedaluwarsa. Silakan coba lagi.');
            return;
        }
        if ($username === '' || $password === '') {
            http_response_code(422);
            $this->renderLogin($username, 'Username dan password wajib diisi.');
            return;
        }
        if (Auth::devBypassEnabled()) {
            $user = $this->devBypassUser($username);
        } else {
            try {
                $user = $this->verifyAdmin($username, $password);
            } catch (Throwable $exception) {
                error_log((string) $exception);
                http_response_code(503);
                $this->renderLogin($username, 'Login belum dapat diproses. Periksa koneksi database.');
                return;
            }
        }
        if ($user === null) {
            http_response_code(422);
            $this->renderLogin($username, 'Username atau password salah.');
            return;
        }
        Auth::login($user);
        redirect(Auth::pullIntended('/'), 302);
    }

    public function logout(): void
    {
        if (Auth::verifyCsrf($_POST['_token'] ?? null)) {
            Auth::logout();
        }
        redirect('/login', 302);
    }

    /**
     * TODO(auth): HAPUS/NONAKTIFKAN sebelum dipakai di server RS.
     * Development mode only (see Auth::devBypassEnabled()): any non-empty username and
     * password signs in as a dummy admin. Each use is logged without the password.
     */
    private function devBypassUser(string $username): array
    {
        $entry = [
            'time' => date('c'),
            'event' => 'dev_bypass_login',
            'username' => mb_strcut($username, 0, 200),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => mb_strcut((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ];
        $line = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        file_put_contents(BASE_PATH . self::DEV_BYPASS_LOG, $line . PHP_EOL, FILE_APPEND | LOCK_EX);

        return ['id' => null, 'name' => 'Admin (mode pengembangan)', 'email' => $username, 'dev_bypass' => true];
    }

    /**
     * The real check: the Username field is matched against users.email (the schema has no
     * username column). Unknown email and wrong password give the same result.
     * TODO(auth): limit failed attempts per username and IP (PRD SEC-3) and log
     * login / login_failed to audit_logs (SEC-12).
     */
    private function verifyAdmin(string $email, string $password): ?array
    {
        $pdo = Database::connection();
        $statement = $pdo->prepare('SELECT id, name, email, password_hash FROM users WHERE email = ? AND role = ? AND is_active = 1 LIMIT 1');
        $statement->execute([$email, 'admin']);
        $user = $statement->fetch();
        if ($user === false || !password_verify($password, $user['password_hash'])) {
            return null;
        }
        $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);

        return ['id' => (int) $user['id'], 'name' => $user['name'], 'email' => $user['email']];
    }

    private function renderLogin(string $username = '', ?string $error = null): void
    {
        $this->render('auth/login', [
            'pageTitle' => 'Sign In',
            'username' => $username,
            'error' => $error,
            'devBypass' => Auth::devBypassEnabled(),
        ], 'auth');
    }
}
