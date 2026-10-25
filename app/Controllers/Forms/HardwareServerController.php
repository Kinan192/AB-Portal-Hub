<?php
declare(strict_types=1);

namespace App\Controllers\Forms;

use App\Core\View;

final class HardwareServerController
{
    public function history(): void
    {
        if (config('app')['environment'] !== 'local') {
            http_response_code(503);
            View::render('errors/unavailable', ['pageTitle' => 'Portal belum tersedia']);
            return;
        }

        $report = config('hardware-server-reports');
        $records = $report['records'];
        usort($records, static fn (array $a, array $b): int => strcmp($b['at'], $a['at']));

        View::render('reports/hardware-server', [
            'pageTitle' => 'Rekap Bulanan Monitoring Hardware Server',
            'navbarVariant' => 'form',
            'hardwarePage' => 'history',
            'pageScripts' => ['js/hardware-server-history.js'],
            'profile' => config('dashboard')['profile'],
            'inspection' => config('hardware-server'),
            'report' => $report,
            'records' => $records,
        ]);
    }

    public function index(): void
    {
        // Keep the same access restriction as the dashboard until authentication is connected.
        if (config('app')['environment'] !== 'local') {
            http_response_code(503);
            View::render('errors/unavailable', ['pageTitle' => 'Portal belum tersedia']);
            return;
        }

        View::render('forms/hardware-server', [
            'pageTitle' => 'Form Monitoring Hardware Server IT',
            'navbarVariant' => 'form',
            'pageScripts' => ['js/hardware-server.js'],
            'profile' => config('dashboard')['profile'],
            'inspection' => config('hardware-server'),
            'recordedAt' => date(DATE_ATOM),
        ]);
    }
}
