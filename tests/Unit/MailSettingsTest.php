<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\MailSettings;
use App\Services\Secret;
use PHPUnit\Framework\TestCase;

final class MailSettingsTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/abportal-secret-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        @unlink($this->dir . '/secret.key');
        @rmdir($this->dir);
    }

    public function testSecretRoundTripsAndNeedsTheSameKey(): void
    {
        $secret = new Secret($this->dir . '/secret.key');
        $stored = $secret->encrypt('abcd efgh ijkl mnop');
        self::assertStringStartsWith('v1:', $stored);
        self::assertStringNotContainsString('abcd', $stored);
        self::assertNotSame($stored, $secret->encrypt('abcd efgh ijkl mnop'));
        self::assertSame('abcd efgh ijkl mnop', $secret->decrypt($stored));
        self::assertFileExists($this->dir . '/secret.key');
        // Another key (e.g. another computer) cannot read it; nor can a missing key or a damaged value.
        $other = new Secret($this->dir . '/other.key');
        self::assertNull($other->decrypt($stored));
        self::assertNull($secret->decrypt('v1:' . base64_encode('rusak')));
    }

    public function testValidateChecksTheFormAndKeepsAnEmptyPasswordEmpty(): void
    {
        [$v, $errors] = MailSettings::validate(['host' => 'smtp.gmail.com', 'port' => '587', 'encryption' => 'tls', 'username' => 'it@gmail.com',
            'password' => '', 'from_address' => '', 'from_name' => 'Portal', 'alert_to' => 'a@rs.id; b@rs.id, a@rs.id'], true);
        self::assertSame([], $errors);
        self::assertSame(587, $v['port']);
        self::assertSame('a@rs.id, b@rs.id', $v['alert_to']);
        self::assertSame('', $v['password']);

        [, $errors] = MailSettings::validate(['host' => 'smtp gmail', 'port' => '99999', 'encryption' => 'x', 'username' => 'admin',
            'from_address' => '', 'alert_to' => 'a@rs.id, bukan']);
        self::assertSame(['host', 'port', 'encryption', 'from_address', 'alert_to', 'password'], array_keys($errors));
        self::assertSame('Bukan alamat email: bukan.', $errors['alert_to']);

        // Gmail: a password is needed (typed or stored), From must be the account itself, App Password spaces dropped.
        $gmail = ['host' => 'smtp.gmail.com', 'port' => '587', 'encryption' => 'tls', 'username' => 'it@gmail.com', 'password' => ''];
        self::assertStringContainsString('App Password', MailSettings::validate($gmail)[1]['password']);
        self::assertSame([], MailSettings::validate($gmail, true)[1]);
        self::assertArrayHasKey('password', MailSettings::validate($gmail + ['clear_password' => '1'], true)[1]);
        self::assertArrayHasKey('from_address', MailSettings::validate(['from_address' => 'lain@gmail.com', 'password' => 'x'] + $gmail)[1]);
        self::assertSame('abcdefghijklmnop', MailSettings::validate(['password' => 'abcd efgh ijkl mnop'] + $gmail)[0]['password']);

        // Everything empty = email switched off, which is allowed.
        [, $errors] = MailSettings::validate(['host' => '', 'port' => '587', 'encryption' => 'tls']);
        self::assertSame([], $errors);
    }
}
