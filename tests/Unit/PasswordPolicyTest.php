<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\PasswordPolicy;
use PHPUnit\Framework\TestCase;

/** PRD SEC-5: at least 12 characters, not the username, not the current password. */
final class PasswordPolicyTest extends TestCase
{
    private static string $currentHash;

    public static function setUpBeforeClass(): void
    {
        self::$currentHash = password_hash('Lama-Password-123', PASSWORD_DEFAULT, ['cost' => 4]);
    }

    public function testTenCharactersAreRefused(): void
    {
        self::assertSame('Password baru minimal 12 karakter.', PasswordPolicy::problem('Abcdefgh12', 'admin.it'));
    }

    public function testTwelveCharactersAreAccepted(): void
    {
        self::assertNull(PasswordPolicy::problem('Abcdefgh1234', 'admin.it'));
    }

    public function testUsernameIsRefusedIgnoringCase(): void
    {
        self::assertSame('Password baru tidak boleh sama dengan username.', PasswordPolicy::problem('Admin.IT.Botania', 'admin.it.botania'));
    }

    public function testCurrentPasswordIsRefused(): void
    {
        self::assertSame('Password baru tidak boleh sama dengan password saat ini.', PasswordPolicy::problem('Lama-Password-123', 'admin.it', self::$currentHash));
    }

    public function testLeadingSpaceAndOverlongPasswordsAreRefused(): void
    {
        self::assertNotNull(PasswordPolicy::problem(' Abcdefgh1234', 'admin.it'));
        self::assertSame('Password baru maksimal 72 byte.', PasswordPolicy::problem(str_repeat('a', 73), 'admin.it'));
    }

    public function testFormErrorsPerField(): void
    {
        self::assertSame(
            ['currentPassword' => 'Password saat ini wajib diisi.', 'newPassword' => 'Password baru wajib diisi.', 'confirmPassword' => 'Konfirmasi password wajib diisi.'],
            PasswordPolicy::formErrors('', '', '', 'admin.it', self::$currentHash),
        );
        self::assertSame(
            ['currentPassword' => 'Password saat ini salah.', 'confirmPassword' => 'Konfirmasi password tidak sama dengan password baru.'],
            PasswordPolicy::formErrors('salah-sekali', 'Baru-Password-456', 'Baru-Password-457', 'admin.it', self::$currentHash),
        );
        self::assertSame([], PasswordPolicy::formErrors('Lama-Password-123', 'Baru-Password-456', 'Baru-Password-456', 'admin.it', self::$currentHash));
    }
}
