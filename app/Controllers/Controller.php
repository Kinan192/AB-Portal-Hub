<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Helpers\DashboardData;
use PDOException;
use RuntimeException;

abstract class Controller
{
    /**
     * Render a page, by default on the design-system layout (layouts/design.php); pass 'main' for
     * the portal dashboard layout. Every page except /login is behind RequireLogin (routes/web.php).
     */
    protected function render(string $template, array $data = [], string $layout = 'design'): void
    {
        View::render($template, $data, $layout);
    }

    /**
     * Dashboard sections, cards and forms from the database (App\Helpers\DashboardData::load()), or null
     * after showing "Database belum siap" when the tables cannot be read (migrations not applied).
     */
    protected function dashboardData(bool $withQuestions = false): ?array
    {
        try {
            return DashboardData::load($withQuestions);
        } catch (PDOException | RuntimeException $exception) {
            error_log('Dashboard data: ' . $exception->getMessage());
            http_response_code(503);
            View::render('errors/design-unavailable', [
                'pageTitle' => 'Database belum siap',
                'errorTitle' => 'Database belum siap',
                'errorDescription' => 'Data dashboard tidak dapat dibaca. Jalankan php bin/migrate.php lalu php bin/migrate.php seed di server, kemudian muat ulang halaman.',
            ], 'design');
            return null;
        }
    }
}
