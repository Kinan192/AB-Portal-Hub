<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Flash;
use App\Services\Mailer;
use App\Services\MailSettings;
use Throwable;

/**
 * "Pengaturan Email" (side menu): the SMTP account that sends the Aset IT vendor-deadline reminders
 * (bin/service-alerts.php), and who receives them. Changes are logged without the password.
 */
final class MailSettingsController extends Controller
{
    public function show(): void
    {
        $this->page(MailSettings::load(Database::connection()), [], Flash::pull());
    }

    public function save(): void
    {
        $pdo = Database::connection();
        $stored = MailSettings::load($pdo)['has_password'];
        [$values, $errors] = MailSettings::validate($_POST, $stored);
        if ($errors !== []) {
            http_response_code(422);
            $this->page(['password' => '', 'has_password' => $stored, 'from_address_set' => $values['from_address'],
                'service_alert_to' => $values['alert_to'], 'password_unreadable' => false, 'updated_at' => null] + $values,
                $errors, ['title' => 'Pengaturan belum disimpan', 'description' => 'Periksa kolom yang ditandai.', 'variant' => 'error']);
            return;
        }
        $changed = MailSettings::save($pdo, $values, isset($_POST['clear_password']), (string) (Auth::user()['name'] ?? ''));
        if ($changed !== []) {
            Audit::log('update', 'mail_settings', null, ['fields' => $changed]);
        }
        Flash::set('Pengaturan email disimpan', $changed === [] ? 'Tidak ada perubahan.' : 'Klik "Kirim Email Tes" untuk memastikan email bisa terkirim.');
        redirect('/pengaturan-email');
    }

    /** Sends a test email with the saved settings to the reminder recipients. */
    public function test(): void
    {
        $pdo = Database::connection();
        $config = MailSettings::load($pdo);
        try {
            $to = MailSettings::recipients($pdo, $config);
            $at = (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Jakarta')))->format('d/m/Y H:i');
            (new Mailer($config))->send($to, 'Tes email Portal Monitoring IT',
                '<p>Pengaturan email Portal Monitoring IT sudah benar. Pengingat tenggat vendor Aset IT akan dikirim ke alamat ini.</p><p style="color:#637377">Dikirim ' . e($at) . ' WIB.</p>',
                "Pengaturan email Portal Monitoring IT sudah benar. Pengingat tenggat vendor Aset IT akan dikirim ke alamat ini.\nDikirim {$at} WIB.");
            Audit::tryLog('mail_test', 'mail_settings', null, ['to' => implode(', ', $to)]);
            Flash::set('Email tes terkirim', 'Cek kotak masuk (dan folder spam): ' . implode(', ', $to) . '.');
        } catch (Throwable $e) {
            Audit::tryLog('mail_test_failed', 'mail_settings', null, ['reason' => mb_substr($e->getMessage(), 0, 300)]);
            Flash::set('Email tes gagal', $e->getMessage(), 'error');
        }
        redirect('/pengaturan-email');
    }

    private function page(array $settings, array $errors, ?array $flash): void
    {
        $this->render('account/mail', [
            'pageTitle' => 'Pengaturan Email',
            'page' => 'mail',
            'bodyClass' => 'rs-content-box',
            'backToDashboard' => true,
            'footer' => ['class' => 'text-center py-6'],
            'settings' => $settings,
            'errors' => $errors,
            'flash' => $flash,
        ]);
    }
}
