<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (PHP_SAPI === 'cli-server') {
    $asset = realpath(__DIR__ . rawurldecode($path));
    $assetRoot = realpath(__DIR__ . '/assets');
    if ($asset !== false && $assetRoot !== false && str_starts_with($asset, $assetRoot . DIRECTORY_SEPARATOR) && is_file($asset)) {
        return false;
    }
}
try {
    $router = require dirname(__DIR__) . '/bootstrap/app.php';
    // In a subfolder (http://localhost/abportal/rekap/suhu) the routes see /rekap/suhu.
    $path = App\Core\Router::withoutBase($path, url_base());
    App\Core\SecurityHeaders::send();
    App\Core\Auth::startSession();
    require BASE_PATH . '/routes/web.php';
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);
} catch (Throwable $exception) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    error_log((string) $exception);
    http_response_code(500);
    echo '<!doctype html><html lang="id"><meta charset="utf-8"><title>Portal tidak tersedia</title><body><h1>Portal belum dapat ditampilkan</h1><p>Silakan muat ulang halaman atau hubungi IT.</p><a href="/">Muat ulang portal</a></body></html>';
}
