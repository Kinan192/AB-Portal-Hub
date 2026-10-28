<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->router->get('/dashboard', static fn () => 'exact');
        $this->router->post('/dashboard/cards/{id}', static fn () => 'edit');
        $this->router->post('/dashboard/cards/{id}/hide', static fn () => 'hide');
    }

    public function testIdPatternMatchesPositiveNumbersOnly(): void
    {
        [$route, $params] = $this->router->match('POST', '/dashboard/cards/42/hide');
        self::assertSame('hide', ($route['handler'])());
        self::assertSame(['id' => '42'], $params);
        self::assertSame('edit', ($this->router->match('POST', '/dashboard/cards/7')[0]['handler'])());

        foreach (['/dashboard/cards/0/hide', '/dashboard/cards/042/hide', '/dashboard/cards/-1/hide', '/dashboard/cards/abc/hide',
            '/dashboard/cards/1/hide/x', '/dashboard/cards/1.5', '/dashboard/cards/' . str_repeat('9', 19), '/dashboard/cards/'] as $path) {
            self::assertNull($this->router->match('POST', $path)[0], $path);
        }
    }

    public function testMethodsAndExactPathsAreKeptApart(): void
    {
        self::assertNull($this->router->match('GET', '/dashboard/cards/42/hide')[0]);
        self::assertSame(['exact', []], [($this->router->match('GET', '/dashboard')[0]['handler'])(), $this->router->match('GET', '/dashboard')[1]]);
    }

    public function testFolderOfThePortalIsFoundFromTheFrontController(): void
    {
        $base = static fn (string $script, string $uri): string => Router::basePath(['SCRIPT_NAME' => $script, 'REQUEST_URI' => $uri]);
        // Web root = public/ (server, php -S): no folder.
        self::assertSame('', $base('/index.php', '/rekap/suhu?periode=2026-10'));
        // XAMPP htdocs\abportal through the project .htaccess, and opened through /public directly.
        self::assertSame('/abportal', $base('/abportal/public/index.php', '/abportal/rekap/suhu'));
        self::assertSame('/abportal', $base('/abportal/public/index.php', '/abportal/'));
        self::assertSame('/abportal/public', $base('/abportal/public/index.php', '/abportal/public/login'));
        // A folder with a space: the browser sends %20.
        self::assertSame('/ab portal', $base('/ab portal/public/index.php', '/ab%20portal/login'));
        // Command line (no web request).
        self::assertSame('', Router::basePath([]));
    }

    public function testRoutesSeeThePathWithoutTheFolder(): void
    {
        self::assertSame('/rekap/suhu', Router::withoutBase('/abportal/rekap/suhu', '/abportal'));
        self::assertSame('/', Router::withoutBase('/abportal', '/abportal'));
        self::assertSame('/', Router::withoutBase('/abportal/', '/abportal'));
        self::assertSame('/abportal-lain/login', Router::withoutBase('/abportal-lain/login', '/abportal'), 'a folder with a longer name is not cut');
        self::assertSame('/rekap/suhu', Router::withoutBase('/rekap/suhu', ''));
        self::assertSame('/login', Router::withoutBase('/ab%20portal/login', '/ab portal'));
    }
}
