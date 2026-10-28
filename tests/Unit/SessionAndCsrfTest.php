<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Auth;
use App\Core\SecurityHeaders;
use App\Core\VerifyCsrf;
use PHPUnit\Framework\TestCase;

/** PRD SEC-7 (timeouts) and SEC-10 (CSRF on every POST, CSP without inline scripts). */
final class SessionAndCsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = ['csrf_token' => str_repeat('a', 64)];
        $_POST = [];
        unset($_SERVER['HTTP_X_CSRF_TOKEN'], $_SERVER['HTTP_ACCEPT']);
    }

    public function testIdleTimeoutIsThirtyMinutes(): void
    {
        $now = 1_800_000_000;
        self::assertNull(Auth::expired($now, $now - 29 * 60, $now - 3600));
        self::assertNotNull(Auth::expired($now, $now - 31 * 60, $now - 3600));
    }

    public function testAbsoluteTimeoutIsTwelveHours(): void
    {
        $now = 1_800_000_000;
        self::assertNull(Auth::expired($now, $now - 10, $now - 12 * 3600));
        self::assertSame('Sesi lebih dari 12 jam. Silakan masuk kembali.', Auth::expired($now, $now - 10, $now - 12 * 3600 - 1));
    }

    public function testCsrfTokenMustMatchExactly(): void
    {
        self::assertTrue(Auth::verifyCsrf(str_repeat('a', 64)));
        self::assertFalse(Auth::verifyCsrf(str_repeat('b', 64)));
        self::assertFalse(Auth::verifyCsrf(null));
        self::assertFalse(Auth::verifyCsrf(['a']));
        $_SESSION = [];
        self::assertFalse(Auth::verifyCsrf(str_repeat('a', 64)));
    }

    public function testMiddlewareLetsGetRequestsThrough(): void
    {
        self::assertTrue((new VerifyCsrf())('GET', '/'));
    }

    public function testMiddlewareAcceptsFieldOrHeader(): void
    {
        $_POST['_token'] = str_repeat('a', 64);
        self::assertTrue((new VerifyCsrf())('POST', '/password'));
        $_POST = [];
        $_SERVER['HTTP_X_CSRF_TOKEN'] = str_repeat('a', 64);
        self::assertTrue((new VerifyCsrf())('POST', '/dashboard/cards'));
    }

    public function testMiddlewareRefusesAMissingTokenWith419Json(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        ob_start();
        $passed = (new VerifyCsrf())('POST', '/dashboard/cards');
        $body = (string) ob_get_clean();
        self::assertFalse($passed);
        self::assertSame(419, http_response_code());
        self::assertStringContainsString('Sesi formulir sudah kedaluwarsa', $body);
    }

    public function testCspAllowsNoInlineScriptOrStyle(): void
    {
        self::assertStringContainsString("script-src 'self'", SecurityHeaders::CSP);
        self::assertStringContainsString("frame-ancestors 'none'", SecurityHeaders::CSP);
        self::assertStringNotContainsString('unsafe-inline', SecurityHeaders::CSP);
        self::assertStringNotContainsString('unsafe-eval', SecurityHeaders::CSP);
    }
}
