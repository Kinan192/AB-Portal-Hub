<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require BASE_PATH . '/app/Views/' . $template . '.php';
        $content = ob_get_clean();
        require BASE_PATH . '/app/Views/layouts/main.php';
    }
}
