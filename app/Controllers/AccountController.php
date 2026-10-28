<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Flash;
use App\Models\User;
use App\Services\PasswordPolicy;

/** Account menu: change password (PRD US-02, SEC-5). There is one admin and no user management. */
final class AccountController extends Controller
{
    public function password(): void
    {
        $this->show([], Flash::pull());
    }

    public function updatePassword(): void
    {
        $user = Auth::user();
        $users = new User(Database::connection());
        $account = $users->find((int) ($user['id'] ?? 0));
        if ($account === null) {
            redirect('/login');
            return;
        }
        $errors = PasswordPolicy::formErrors(
            (string) ($_POST['currentPassword'] ?? ''),
            (string) ($_POST['newPassword'] ?? ''),
            (string) ($_POST['confirmPassword'] ?? ''),
            (string) $account['username'],
            (string) $account['password_hash'],
        );
        if ($errors !== []) {
            if (isset($errors['currentPassword']) && $errors['currentPassword'] === 'Password saat ini salah.') {
                Audit::tryLog('password_change_failed', 'user', (int) $account['id'], ['reason' => 'current_password']);
            }
            http_response_code(422);
            $this->show($errors, ['title' => 'Password belum diubah', 'description' => 'Periksa kolom yang ditandai.', 'variant' => 'error']);
            return;
        }
        $version = $users->changePassword((int) $account['id'], User::hash((string) $_POST['newPassword']));
        Auth::refresh($version);
        Audit::log('password_change', 'user', (int) $account['id']);
        Flash::set('Password berhasil diubah', 'Perangkat lain yang masih masuk sudah dikeluarkan.');
        redirect('/password');
    }

    /** @param array<string, string> $errors */
    private function show(array $errors, ?array $flash): void
    {
        $this->render('account/password', [
            'pageTitle' => 'Password',
            'page' => 'password',
            'bodyClass' => 'rs-content-box',
            'crumbbar' => [
                'class' => 'flex items-center',
                'items' => [['label' => 'Portal Monitoring Terpadu', 'href' => url('/')], ['label' => 'Password']],
            ],
            'footer' => ['class' => 'text-center py-6'],
            'profile' => current_profile(),
            'errors' => $errors,
            'flash' => $flash,
        ]);
    }
}
