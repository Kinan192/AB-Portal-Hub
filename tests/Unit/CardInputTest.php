<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Dashboard;
use App\Services\CardInput;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CardInputTest extends TestCase
{
    private const ICONS = ['link', 'server', 'globe', 'folder-open'];
    private const COLORS = ['teal', 'amber'];

    public static function urls(): array
    {
        return [
            'https' => ['https://his.rs.local/login', false, true],
            'http internal host' => ['http://his/x?a=1&b=2', false, true],
            'upper-case scheme' => ['HTTPS://Example.test', false, true],
            'javascript' => ['javascript:alert(1)', false, false],
            'javascript mixed case' => ['JaVaScRiPt:alert(1)//http://x', false, false],
            'javascript with leading space' => [' javascript:alert(1)', false, false],
            'data' => ['data:text/html,<script>alert(1)</script>', false, false],
            'file' => ['file:///C:/Windows/win.ini', false, false],
            'protocol-relative' => ['//evil.test/x', false, false],
            'relative path' => ['/form/suhu', false, false],
            'no host' => ['http://', false, false],
            'space inside' => ['http://ok.test/a b', false, false],
            'tab inside' => ["http://ok.test/\ta", false, false],
            'backslash' => ['https:\\\\evil.test', false, false],
            'too long' => ['https://ok.test/' . str_repeat('a', 490), false, false],
            'empty' => ['', false, false],
            'image over https' => ['https://img.test/logo.png', true, true],
            'image over http' => ['http://img.test/logo.png', true, false],
        ];
    }

    #[DataProvider('urls')]
    public function testOnlyHttpAndHttpsAddressesWithAHostAreAccepted(string $url, bool $httpsOnly, bool $ok): void
    {
        self::assertSame($ok, CardInput::safeUrl($url, $httpsOnly));
    }

    public function testNonStringUrlIsRefused(): void
    {
        self::assertFalse(CardInput::safeUrl(['https://ok.test']));
        self::assertFalse(CardInput::safeUrl(null));
    }

    public function testLinkCheckCleansTextAndReportsEveryBadField(): void
    {
<<<<<<< HEAD
        $ok = CardInput::link(['title' => "  HIS \n Baru ", 'description' => ' Sistem ', 'url' => ' https://his.test ', 'icon' => 'server', 'icon_source' => 'list', 'icon_url' => 'https://x.test/a.png'], self::ICONS, self::COLORS);
        self::assertSame([], $ok['errors']);
        self::assertSame(['title' => 'HIS Baru', 'description' => 'Sistem', 'url' => 'https://his.test', 'icon' => 'server', 'image_url' => null, 'color' => 'teal'], $ok['values']);
        self::assertSame('amber', CardInput::link(['title' => 'HIS', 'url' => 'https://his.test', 'icon' => 'server', 'color' => 'amber'], self::ICONS, self::COLORS)['values']['color']);

        $bad = CardInput::link(['title' => str_repeat('a', 41), 'description' => str_repeat('b', 61), 'url' => 'javascript:alert(1)', 'icon' => 'nope', 'icon_source' => 'url', 'icon_url' => 'http://x.test/a.png', 'color' => 'neon'], self::ICONS, self::COLORS);
        self::assertSame(['color', 'title', 'description', 'url', 'icon', 'icon_url'], array_keys($bad['errors']));
        self::assertSame(CardInput::IMAGE_MESSAGE, $bad['errors']['icon_url']);
    }

    public function testLinkUploadedIconIsAPortalFilePath(): void
    {
        $base = ['title' => 'HIS', 'url' => 'https://his.test', 'icon' => 'link', 'icon_source' => 'upload'];
        self::assertSame('/berkas/12', CardInput::link($base + ['icon_url' => ' /berkas/12 '], self::ICONS, self::COLORS)['values']['image_url']);
        foreach (['', 'https://x.test/a.png', '/berkas/0', '/berkas/12/../1', '//evil.test/berkas/1'] as $bad) {
            self::assertSame(CardInput::UPLOAD_MESSAGE, CardInput::link($base + ['icon_url' => $bad], self::ICONS, self::COLORS)['errors']['icon_upload'] ?? null, $bad);
        }
    }

    public function testLinkTitleLimitCountsCharactersNotBytes(): void
    {
        $check = CardInput::link(['title' => str_repeat('é', 40), 'url' => 'https://ok.test', 'icon' => 'link'], self::ICONS, self::COLORS);
=======
        $ok = CardInput::link(['title' => "  HIS \n Baru ", 'description' => ' Sistem ', 'url' => ' https://his.test ', 'icon' => 'server', 'icon_source' => 'list', 'icon_url' => 'https://x.test/a.png'], self::ICONS);
        self::assertSame([], $ok['errors']);
        self::assertSame(['title' => 'HIS Baru', 'description' => 'Sistem', 'url' => 'https://his.test', 'icon' => 'server', 'image_url' => null], $ok['values']);

        $bad = CardInput::link(['title' => str_repeat('a', 41), 'description' => str_repeat('b', 61), 'url' => 'javascript:alert(1)', 'icon' => 'nope', 'icon_source' => 'url', 'icon_url' => 'http://x.test/a.png'], self::ICONS);
        self::assertSame(['title', 'description', 'url', 'icon', 'icon_url'], array_keys($bad['errors']));
        self::assertSame(CardInput::IMAGE_MESSAGE, $bad['errors']['icon_url']);
    }

    public function testLinkTitleLimitCountsCharactersNotBytes(): void
    {
        $check = CardInput::link(['title' => str_repeat('é', 40), 'url' => 'https://ok.test', 'icon' => 'link'], self::ICONS);
>>>>>>> 899d036f59ce2361fefc2c1a8249a7b80cbc5bb5
        self::assertArrayNotHasKey('title', $check['errors']);
    }

    public function testFormCardCheck(): void
    {
        $ok = CardInput::formCard(['title' => 'Cek  UPS', 'description' => '', 'keterangan' => "Baris 1\r\nBaris 2\x07", 'icon' => 'server', 'color' => 'amber'], self::ICONS, self::COLORS);
        self::assertSame([], $ok['errors']);
        self::assertSame('Cek UPS', $ok['values']['title']);
        self::assertSame("Baris 1\nBaris 2", $ok['values']['keterangan']);

        $bad = CardInput::formCard(['title' => ' ', 'keterangan' => str_repeat('x', 501), 'icon' => '../x', 'color' => 'pink'], self::ICONS, self::COLORS);
        self::assertSame(['title', 'keterangan', 'icon', 'color'], array_keys($bad['errors']));
        self::assertSame('Judul Form wajib diisi.', $bad['errors']['title']);
        self::assertSame('Judul Form maksimal 60 karakter.', CardInput::formCard(['title' => str_repeat('a', 61), 'icon' => 'link', 'color' => 'teal'], self::ICONS, self::COLORS)['errors']['title']);
    }

    public function testCategoryNameIsUpperCaseAndUniqueIgnoringCase(): void
    {
        $ok = CardInput::category(['title' => ' jaringan  & server ', 'icon' => 'globe'], self::ICONS, ['Sistem']);
        self::assertSame([], $ok['errors']);
        self::assertSame('JARINGAN & SERVER', $ok['values']['title']);
        self::assertSame('Nama kategori sudah dipakai.', CardInput::category(['title' => 'sistem', 'icon' => 'globe'], self::ICONS, ['Sistem'])['errors']['category_title']);
        self::assertSame(['category_title', 'category_icon'], array_keys(CardInput::category(['title' => '', 'icon' => 'x'], self::ICONS, [])['errors']));
    }

    public function testSlugs(): void
    {
        self::assertSame('cek-ups-ruang-server', Dashboard::slug('Cek  UPS / Ruang Server!', 'form'));
        self::assertSame('form', Dashboard::slug('###', 'form'));
        self::assertSame('form-link-5', Dashboard::slug('Link 5', 'form'));
        self::assertLessThanOrEqual(56, strlen(Dashboard::slug(str_repeat('abc ', 40), 'form')));
        self::assertMatchesRegularExpression('/^[a-z0-9-]+$/', Dashboard::slug('Ruang Sérvér Lt. 2', 'form'));
    }
}
