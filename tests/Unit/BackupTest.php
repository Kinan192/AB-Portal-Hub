<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Backup;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

/** PRD NFR-4, NFR-5 (decision Q19): backup set names, keeping 30, encryption, safe restore of files. */
final class BackupTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/abp-test-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->dir);
    }

    public function testSetNameIsWibTime(): void
    {
        // 31 Oct 18:00 UTC is 1 Nov 01:00 WIB (the nightly run).
        self::assertSame('abportal-20261101-010000', Backup::setName(new DateTimeImmutable('2026-10-31 18:00:00', new DateTimeZone('UTC'))));
    }

    public function testKeepsTheNewestSetsOnly(): void
    {
        $names = [];
        for ($day = 1; $day <= 31; $day++) {
            $names[] = sprintf('abportal-202610%02d-010000', $day);
        }
        $names[] = 'before-B4.sql';
        $names[] = '.partial-abportal-20261101-010000';
        $names[] = 'abportal-catatan';
        // The 31st night removes the oldest (NFR-4 AC); other names are never touched.
        self::assertSame(['abportal-20261001-010000'], Backup::expiredSets($names, 30));
        self::assertSame([], Backup::expiredSets(array_slice($names, 0, 30), 30));
        self::assertCount(30, Backup::expiredSets($names, 1));
    }

    public function testOptionFileQuotesThePassword(): void
    {
        $file = Backup::optionFile(['username' => 'abportal', 'password' => 'p"a\\ss#;w', 'host' => '127.0.0.1', 'port' => '3306']);
        self::assertStringContainsString("password=\"p\\\"a\\\\ss#;w\"\n", $file);
        self::assertStringStartsWith("[client]\nuser=\"abportal\"\n", $file);
    }

    public function testDatabaseNamesAndZipEntries(): void
    {
        self::assertTrue(Backup::validDatabase('abportal_restore-test'));
        self::assertFalse(Backup::validDatabase('abportal`; DROP DATABASE x'));
        self::assertFalse(Backup::validDatabase(''));
        self::assertTrue(Backup::safeEntry('2026/10/a1b2.pdf'));
        foreach (['../.env', '2026/../../x.php', '/etc/passwd', 'C:/Windows/x.dll', '..\\x', "a\0b"] as $bad) {
            self::assertFalse(Backup::safeEntry($bad), $bad);
        }
    }

    public function testEncryptionRoundTripForSeveralSizes(): void
    {
        $key = Backup::key(Backup::newKey());
        foreach ([0, 10, 1048576, 1048576 * 2 + 7] as $size) {
            $plain = $this->dir . "/p{$size}";
            file_put_contents($plain, $size > 0 ? random_bytes($size) : '');
            Backup::encryptFile($plain, $plain . '.enc', $key);
            self::assertStringStartsWith("ABPENC1\n", (string) file_get_contents($plain . '.enc', false, null, 0, 8));
            Backup::decryptFile($plain . '.enc', $plain . '.out', $key);
            self::assertSame(hash_file('sha256', $plain), hash_file('sha256', $plain . '.out'), "size {$size}");
        }
    }

    public function testWrongKeyChangedByteAndCutFileFail(): void
    {
        $key = Backup::key(Backup::newKey());
        $plain = $this->dir . '/p';
        file_put_contents($plain, random_bytes(1048576 + 100));
        Backup::encryptFile($plain, $plain . '.enc', $key);
        $data = (string) file_get_contents($plain . '.enc');
        $cases = [
            'wrong key' => [$data, Backup::key(Backup::newKey())],
            'changed byte' => [substr_replace($data, chr(ord($data[500]) ^ 1), 500, 1), $key],
            'last chunk cut off' => [substr($data, 0, 8 + 16 + 5 + 1048576 + 16), $key],
        ];
        foreach ($cases as $name => [$bytes, $useKey]) {
            file_put_contents($plain . '.bad', $bytes);
            try {
                Backup::decryptFile($plain . '.bad', $plain . '.out', $useKey);
                self::fail($name . ' was accepted');
            } catch (RuntimeException $exception) {
                self::assertMatchesRegularExpression('/Dekripsi gagal|terpotong/', $exception->getMessage(), $name);
            }
        }
        $this->expectException(RuntimeException::class);
        Backup::key('terlalu-pendek');
    }

    public function testZipRoundTripAndUnsafeArchive(): void
    {
        if (!class_exists(ZipArchive::class)) {
            self::markTestSkipped('ZipArchive tidak aktif di PHP ini.');
        }
        mkdir($this->dir . '/src/2026/10', 0777, true);
        file_put_contents($this->dir . '/src/2026/10/bukti.pdf', '%PDF-1.4 uji');
        file_put_contents($this->dir . '/src/a.png', 'png');
        self::assertSame(2, Backup::zipFolder($this->dir . '/src', $this->dir . '/files.zip'));
        self::assertSame(2, Backup::unzipFolder($this->dir . '/files.zip', $this->dir . '/out'));
        self::assertSame('%PDF-1.4 uji', file_get_contents($this->dir . '/out/2026/10/bukti.pdf'));
        // An empty upload folder still gives a valid archive that restores nothing.
        self::assertSame(0, Backup::zipFolder($this->dir . '/tidak-ada', $this->dir . '/empty.zip'));
        self::assertSame(0, Backup::unzipFolder($this->dir . '/empty.zip', $this->dir . '/out2'));

        $zip = new ZipArchive();
        $zip->open($this->dir . '/evil.zip', ZipArchive::CREATE);
        $zip->addFromString('../../evil.php', '<?php');
        $zip->close();
        $this->expectExceptionMessage('tidak aman');
        Backup::unzipFolder($this->dir . '/evil.zip', $this->dir . '/out3');
    }

    public function testRemoveSetOnlyTouchesBackupFolders(): void
    {
        mkdir($this->dir . '/abportal-20261001-010000');
        file_put_contents($this->dir . '/abportal-20261001-010000/manifest.json', '{}');
        mkdir($this->dir . '/data-penting');
        self::assertTrue(Backup::removeSet($this->dir . '/abportal-20261001-010000'));
        self::assertDirectoryDoesNotExist($this->dir . '/abportal-20261001-010000');
        self::assertFalse(Backup::removeSet($this->dir . '/data-penting'));
        self::assertDirectoryExists($this->dir . '/data-penting');
    }
}
