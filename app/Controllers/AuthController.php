<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Flash;
use App\Models\User;
use App\Services\LoginThrottle;
use Throwable;

/**
 * Login and logout (PRD SEC-1 to SEC-4, SEC-12). The CSRF token is checked by App\Core\VerifyCsrf
 * before these actions run. Unknown username and wrong password give the same message.
 */
final class AuthController extends Controller
{
    private const WRONG = 'Username atau password salah.';

    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/', 302);
            return;
        }
        $flash = Flash::pull();
        $this->renderLogin('', $flash !== null && $flash['variant'] === 'error' ? $flash['description'] : null, $flash !== null && $flash['variant'] !== 'error' ? $flash : null);
    }

    public function login(): void
    {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if ($username === '' || $password === '') {
            http_response_code(422);
            $this->renderLogin($username, 'Username dan password wajib diisi.');
            return;
        }
        if (mb_strlen($username) > 100 || strlen($password) > 1024) {
            http_response_code(422);
            $this->renderLogin(mb_substr($username, 0, 100), self::WRONG);
            return;
        }
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        try {
            $pdo = Database::connection();
            $throttle = new LoginThrottle($pdo);
            $wait = $throttle->lockedFor($username, $ip, time());
            if ($wait > 0) {
                Audit::tryLog('login_locked', 'user', null, ['username' => $username]);
                http_response_code(429);
                header('Retry-After: ' . $wait);
                $this->renderLogin($username, 'Terlalu banyak percobaan. Coba lagi dalam ' . max(1, (int) ceil($wait / 60)) . ' menit.');
                return;
            }
            $users = new User($pdo);
            $account = $users->findByUsername($username);
            // A dummy hash keeps the timing of unknown usernames close to that of real ones.
            $hash = $account['password_hash'] ?? '$2y$12$qOFZUH0LWWA8zl1IkMtSB.rHs7dp./m6ZqaorQk5W1.3v7mMILbBy';
            $valid = password_verify($password, $hash) && $account !== null && (int) $account['is_active'] === 1;
            $throttle->record($username, $ip, $valid);
            if (!$valid) {
                Audit::tryLog('login_failed', 'user', $account !== null ? (int) $account['id'] : null, ['username' => $username]);
                http_response_code(422);
                $this->renderLogin($username, self::WRONG);
                return;
            }
            $users->recordLogin((int) $account['id'], User::needsRehash($account['password_hash']) ? User::hash($password) : null);
        } catch (Throwable $exception) {
            error_log((string) $exception);
            http_response_code(503);
            $this->renderLogin($username, 'Login belum dapat diproses. Periksa koneksi database.');
            return;
        }

        Auth::login([
            'id' => (int) $account['id'],
            'name' => (string) $account['name'],
            'username' => (string) $account['username'],
            'email' => (string) $account['email'],
            'session_version' => (int) $account['session_version'],
        ]);
        Audit::tryLog('login', 'user', (int) $account['id']);
        redirect(Auth::pullIntended('/'), 302);
    }

    public function logout(): void
    {
        $user = Auth::user();
        if ($user !== null) {
            Audit::tryLog('logout', 'user', $user['id']);
        }
        Auth::logout();
        redirect('/login', 302);
    }

    private function renderLogin(string $username = '', ?string $error = null, ?array $notice = null): void
    {
        $this->render('auth/login', [
            'pageTitle' => 'Masuk',
            'username' => $username,
            'error' => $error,
            'notice' => $notice,
        ], 'auth');
    }
}
