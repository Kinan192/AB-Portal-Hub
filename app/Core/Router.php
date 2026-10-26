<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];
    /** @var list<callable(string, string): bool> */
    private array $middleware = [];

    /** A public route skips the middleware (e.g. the login page). */
    public function get(string $path, callable $handler, bool $public = false): void
    {
        $this->routes['GET'][$path] = ['handler' => $handler, 'public' => $public];
    }

    public function post(string $path, callable $handler, bool $public = false): void
    {
        $this->routes['POST'][$path] = ['handler' => $handler, 'public' => $public];
    }

    /** Middleware runs before every non-public route; returning false stops the request. */
    public function middleware(callable $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    public function dispatch(string $method, string $path): void
    {
        // HEAD is answered like GET; the server discards the body.
        $method = $method === 'HEAD' ? 'GET' : $method;
        $route = $this->routes[$method][$path] ?? null;
        if (!($route['public'] ?? false)) {
            foreach ($this->middleware as $middleware) {
                if ($middleware($method, $path) === false) {
                    return;
                }
            }
        }
        if ($route === null) {
            http_response_code(404);
            View::render('errors/design-404', ['pageTitle' => 'Halaman tidak ditemukan'], 'design');
            return;
        }
        ($route['handler'])();
    }
}
