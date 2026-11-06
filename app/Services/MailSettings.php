<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use PDO;

/**
 * SMTP settings of the Aset IT reminder emails, edited on the "Pengaturan Email" page (side menu) and kept in
 * app_settings ('mail'). The password is stored encrypted (Secret) and never sent back to the page.
 */
final class MailSettings
{
    public const NAME = 'mail';
    public const ENCRYPTIONS = ['tls' => 'TLS / STARTTLS (port 587)', 'ssl' => 'SSL (port 465)', 'none' => 'Tanpa enkripsi'];
    private const DEFAULTS = ['host' => '', 'port' => 587, 'encryption' => 'tls', 'username' => '', 'password' => '',
        'from_address' => '', 'from_name' => 'Portal Monitoring IT', 'alert_to' => ''];

    /** The settings for Mailer (password decrypted), plus whether a password is stored and who saved them last. */
    public static function load(PDO $pdo, ?Secret $secret = null): array
    {
        $row = (new Setting($pdo))->get(self::NAME);
        $v = ($row['value'] ?? []) + self::DEFAULTS;
        $stored = (string) ($v['password_enc'] ?? '');
        $password = $stored !== '' ? ($secret ?? Secret::default())->decrypt($stored) : '';
        return [
            'host' => (string) $v['host'], 'port' => (int) $v['port'], 'encryption' => (string) $v['encryption'],
            'username' => (string) $v['username'], 'password' => (string) $password,
            'from_address' => (string) ($v['from_address'] !== '' ? $v['from_address'] : $v['username']),
            'from_address_set' => (string) $v['from_address'],
            'from_name' => (string) $v['from_name'], 'service_alert_to' => (string) $v['alert_to'],
            'has_password' => $stored !== '', 'password_unreadable' => $stored !== '' && $password === null,
            'updated_at' => $row['updated_at'] ?? null, 'updated_by' => $row['updated_by'] ?? null,
        ];
    }

    /**
     * Checks the posted form. An empty password keeps the stored one.
     * @return array{0: array, 1: array<string, string>} the values to save and the errors per field
     */
    public static function validate(array $in, bool $hasStoredPassword = false): array
    {
        $s = static fn (string $k, int $max): string => mb_substr(trim(is_string($in[$k] ?? null) ? $in[$k] : ''), 0, $max);
        $v = [
            'host' => $s('host', 190), 'port' => $s('port', 5), 'encryption' => $s('encryption', 10), 'username' => $s('username', 190),
            'from_address' => $s('from_address', 190), 'from_name' => $s('from_name', 100), 'alert_to' => $s('alert_to', 1000),
        ];
        $password = is_string($in['password'] ?? null) ? $in['password'] : '';
        $errors = [];
        if ($v['host'] !== '' && preg_match('/^[A-Za-z0-9.-]+$/', $v['host']) !== 1) {
            $errors['host'] = 'Isi alamat server SMTP, mis. smtp.gmail.com.';
        }
        if (!ctype_digit($v['port']) || (int) $v['port'] < 1 || (int) $v['port'] > 65535) {
            $errors['port'] = 'Port 1–65535, biasanya 587 (TLS) atau 465 (SSL).';
        }
        if (!isset(self::ENCRYPTIONS[$v['encryption']])) {
            $errors['encryption'] = 'Pilih jenis keamanan.';
        }
        if ($v['from_address'] !== '' && filter_var($v['from_address'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['from_address'] = 'Alamat email pengirim tidak valid.';
        }
        if ($v['host'] !== '' && $v['from_address'] === '' && filter_var($v['username'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['from_address'] = 'Isi email pengirim (username bukan alamat email).';
        }
        $bad = array_filter(preg_split('/[,;\s]+/', $v['alert_to']) ?: [], static fn (string $a): bool => $a !== '' && filter_var($a, FILTER_VALIDATE_EMAIL) === false);
        if ($bad !== []) {
            $errors['alert_to'] = 'Bukan alamat email: ' . implode(', ', $bad) . '.';
        }
        if (strlen($password) > 200) {
            $errors['password'] = 'Password terlalu panjang.';
        }
        // A server with a username needs a password: typed now, or already stored (and not being removed).
        $keeps = $hasStoredPassword && empty($in['clear_password']);
        if ($v['host'] !== '' && $v['username'] !== '' && $password === '' && !$keeps) {
            $errors['password'] = str_contains(strtolower($v['host']), 'gmail')
                ? 'Isi App Password Gmail (16 huruf) dari akun ' . $v['username'] . '.'
                : 'Isi password akun email pengirim.';
        }
        // Gmail sends only as the signed-in account (or its verified aliases).
        if (!isset($errors['from_address']) && str_contains(strtolower($v['host']), 'gmail') && $v['from_address'] !== ''
            && strcasecmp($v['from_address'], $v['username']) !== 0) {
            $errors['from_address'] = 'Untuk Gmail, kosongkan atau samakan dengan Username (' . $v['username'] . ').';
        }
        // App Passwords are shown in groups of four; the spaces are not part of it.
        if (str_contains(strtolower($v['host']), 'gmail')) {
            $password = str_replace(' ', '', $password);
        }
        $v['port'] = (int) $v['port'];
        // Tidied only when valid, so a refused form shows what was typed.
        if (!isset($errors['alert_to'])) {
            $v['alert_to'] = implode(', ', Mailer::addresses($v['alert_to']));
        }
        $v['password'] = $password;
        return [$v, $errors];
    }

    /**
     * Saves checked values; an empty password keeps the stored one, $clearPassword removes it.
     * @return list<string> the names of the fields that changed (never the password itself)
     */
    public static function save(PDO $pdo, array $v, bool $clearPassword, string $by, ?Secret $secret = null): array
    {
        $settings = new Setting($pdo);
        $old = ($settings->get(self::NAME)['value'] ?? []) + self::DEFAULTS + ['password_enc' => ''];
        $new = ['host' => $v['host'], 'port' => $v['port'], 'encryption' => $v['encryption'], 'username' => $v['username'],
            'from_address' => $v['from_address'], 'from_name' => $v['from_name'], 'alert_to' => $v['alert_to'],
            'password_enc' => $clearPassword ? '' : ($v['password'] !== '' ? ($secret ?? Secret::default())->encrypt($v['password']) : $old['password_enc'])];
        $changed = array_values(array_filter(['host', 'port', 'encryption', 'username', 'from_address', 'from_name', 'alert_to'],
            static fn (string $k): bool => (string) $old[$k] !== (string) $new[$k]));
        if ($clearPassword || $v['password'] !== '') {
            $changed[] = 'password';
        }
        $settings->put(self::NAME, $new, $by);
        return $changed;
    }

    /** Who gets the reminders: the addresses set here, or every active account's email. */
    public static function recipients(PDO $pdo, array $config): array
    {
        $to = Mailer::addresses($config['service_alert_to']);
        if ($to === []) {
            $to = Mailer::addresses(implode(',', $pdo->query("SELECT email FROM users WHERE is_active = 1 AND email <> ''")->fetchAll(PDO::FETCH_COLUMN)));
        }
        return $to;
    }
}
