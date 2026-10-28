<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];
    /** @var list<callable(string, string): bool> runs before every route, public ones too (e.g. CSRF) */
    private array $always = [];
    /** @var list<callable(string, string): bool> runs before non-public routes only (e.g. login) */
    private array $middleware = [];

    /**
     * The folder the portal is served from, e.g. "/abportal" for http://localhost/abportal (a copy in XAMPP
     * htdocs, see the project .htaccess), or "" at the root of a site. Found from the front controller's address:
     * /abportal/public/index.php serves /abportal/… (through the .htaccess) or /abportal/public/… (directly).
     */
    public static function basePath(array $server): string
    {
        $script = str_replace('\\', '/', (string) ($server['SCRIPT_NAME'] ?? ''));
        if (!str_ends_with($script, '/index.php')) {
            return '';
        }
        $dir = rtrim(substr($script, 0, -strlen('/index.php')), '/');
        // SCRIPT_NAME is decoded, REQUEST_URI is not (a folder "ab portal" arrives as ab%20portal).
        $uri = rawurldecode((string) (parse_url((string) ($server['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/'));
        if ($dir !== '' && ($uri === $dir || str_starts_with($uri, $dir . '/'))) {
            return $dir;
        }
        return str_ends_with($dir, '/public') ? substr($dir, 0, -strlen('/public')) : $dir;
    }

    /** The route path of a request path below $base: "/abportal/rekap/suhu" → "/rekap/suhu" ($path as sent, still encoded). */
    public static function withoutBase(string $path, string $base): string
    {
        $base = implode('/', array_map('rawurlencode', explode('/', $base)));
        if ($base === '' || ($path !== $base && !str_starts_with($path, $base . '/'))) {
            return $path;
        }
        return substr($path, strlen($base)) ?: '/';
    }

    /** A public route skips the login middleware (e.g. the login page). */
    public function get(string $path, callable $handler, bool $public = false): void
    {
        $this->routes['GET'][$path] = ['handler' => $handler, 'public' => $public];
    }

    public function post(string $path, callable $handler, bool $public = false): void
    {
        $this->routes['POST'][$path] = ['handler' => $handler, 'public' => $public];
    }

    /** Middleware returning false stops the request; $always also covers public routes. */
    public function middleware(callable $middleware, bool $always = false): void
    {
        if ($always) {
            $this->always[] = $middleware;
        } else {
            $this->middleware[] = $middleware;
        }
    }

    public function dispatch(string $method, string $path): void
    {
        // HEAD is answered like GET; the server discards the body.
        $method = $method === 'HEAD' ? 'GET' : $method;
        [$route, $params] = $this->match($method, $path);
        $chain = ($route['public'] ?? false) ? $this->always : [...$this->always, ...$this->middleware];
        foreach ($chain as $middleware) {
            if ($middleware($method, $path) === false) {
                return;
            }
        }
        if ($route === null) {
            if (wants_json()) {
                json_response(['message' => 'Alamat tidak ditemukan.'], 404);
                return;
            }
            http_response_code(404);
            View::render('errors/design-404', ['pageTitle' => 'Halaman tidak ditemukan'], 'design');
            return;
        }
        ($route['handler'])($params);
    }

    /**
     * Exact paths first, then patterns such as /dashboard/cards/{id}/hide, where {id} matches a
     * positive whole number only (no leading zero, at most 18 digits).
     * @return array{0: ?array, 1: array<string, string>}
     */
    public function match(string $method, string $path): array
    {
        if (isset($this->routes[$method][$path])) {
            return [$this->routes[$method][$path], []];
        }
        foreach ($this->routes[$method] ?? [] as $pattern => $route) {
            if (!str_contains((string) $pattern, '{id}')) {
                continue;
            }
            $regex = str_replace(preg_quote('{id}', '#'), '(?P<id>[1-9][0-9]{0,17})', preg_quote((string) $pattern, '#'));
            if (preg_match('#^' . $regex . '$#', $path, $found) === 1) {
                return [$route, ['id' => $found['id']]];
            }
        }
        return [null, []];
    }
}
