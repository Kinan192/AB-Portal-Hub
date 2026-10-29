<?php
declare(strict_types=1);

namespace App\Services;

/** Password rules of PRD SEC-5, shared by the password page and bin/create-admin.php. */
final class PasswordPolicy
{
    public const MIN_LENGTH = 12;
    public const FLOOR = 8;
    public const MAX_BYTES = 72; // bcrypt ignores anything longer

    /** PASSWORD_MIN_LENGTH in .env (never below 8), otherwise 12. */
    public static function minLength(): int
    {
<<<<<<< HEAD
        $value = \App\Core\Env::get('PASSWORD_MIN_LENGTH');
=======
        $value = getenv('PASSWORD_MIN_LENGTH');
>>>>>>> 899d036f59ce2361fefc2c1a8249a7b80cbc5bb5
        return $value !== false && ctype_digit(trim($value)) ? max(self::FLOOR, (int) $value) : self::MIN_LENGTH;
    }

    /** The first problem with a new password, or null when it is acceptable. */
    public static function problem(string $password, string $username, ?string $currentHash = null): ?string
    {
        $min = self::minLength();
        return match (true) {
            mb_strlen($password) < $min => 'Password baru minimal ' . $min . ' karakter.',
            strlen($password) > self::MAX_BYTES => 'Password baru maksimal ' . self::MAX_BYTES . ' byte.',
            trim($password) !== $password => 'Password baru tidak boleh diawali atau diakhiri spasi.',
            $username !== '' && mb_strtolower($password) === mb_strtolower($username) => 'Password baru tidak boleh sama dengan username.',
            $currentHash !== null && password_verify($password, $currentHash) => 'Password baru tidak boleh sama dengan password saat ini.',
            default => null,
        };
    }

    /**
     * Errors of the password form per field (current, new, confirm), in Indonesian.
     * @return array<string, string>
     */
    public static function formErrors(string $current, string $new, string $confirm, string $username, string $currentHash): array
    {
        $errors = [];
        if ($current === '') {
            $errors['currentPassword'] = 'Password saat ini wajib diisi.';
        } elseif (!password_verify($current, $currentHash)) {
            $errors['currentPassword'] = 'Password saat ini salah.';
        }
        if ($new === '') {
            $errors['newPassword'] = 'Password baru wajib diisi.';
        } elseif (($problem = self::problem($new, $username, $currentHash)) !== null) {
            $errors['newPassword'] = $problem;
        }
        if ($confirm === '') {
            $errors['confirmPassword'] = 'Konfirmasi password wajib diisi.';
        } elseif (!hash_equals($new, $confirm)) {
            $errors['confirmPassword'] = 'Konfirmasi password tidak sama dengan password baru.';
        }
        return $errors;
    }
}
