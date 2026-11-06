<?php
declare(strict_types=1);

namespace App\Services;

/** The header's side menu (hamburger): which entry is the page being shown. */
final class NavMenu
{
    /** The page being shown, as the portal path ("/", "/perlu-perhatian", …) without the folder prefix. */
    public static function currentPath(): string
    {
        $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $base = url_base();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        return '/' . trim($path, '/');
    }
}
