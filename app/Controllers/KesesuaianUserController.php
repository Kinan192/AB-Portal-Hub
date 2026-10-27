<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;

final class KesesuaianUserController
{
    public function rekap(): void
    {
        if (config('app')['environment'] !== 'local') {
            http_response_code(503);
            View::render('errors/unavailable', ['pageTitle' => 'Portal belum tersedia']);
            return;
        }

        $report = config('kesesuaian-user-reports');
        $records = $report['records'];
        usort($records, static fn (array $a, array $b): int => strcmp($b['at'], $a['at']));
        $periodRecords = array_values(array_filter($records, static fn (array $record): bool => str_starts_with($record['at'], $report['default_period'])));
        $total = count($periodRecords);
        $compliant = count(array_filter($periodRecords, static fn (array $record): bool => $record['compliant']));

        View::render('kesesuaian-user/rekap', [
            'pageTitle' => 'Rekap & Riwayat Kesesuaian User (HIS)',
            'breadcrumbs' => monitoring_breadcrumbs('kesesuaian-user'),
            'profile' => config('dashboard')['profile'],
            'report' => $report,
            'records' => $records,
            'summary' => [
                'total' => $total,
                'compliant' => $compliant,
                'non_compliant' => $total - $compliant,
                'percentage' => $total ? round($compliant / $total * 100, 1) : null,
                'latest' => $periodRecords[0] ?? null,
            ],
            'pageScripts' => ['js/kesesuaian-user-history.js'],
        ]);
    }

    public function form(): void
    {
        if (config('app')['environment'] !== 'local') {
            http_response_code(503);
            View::render('errors/unavailable', ['pageTitle' => 'Portal belum tersedia']);
            return;
        }

        View::render('kesesuaian-user/form', [
            'pageTitle' => 'Form Kesesuaian User (HIS)',
            'backToDashboard' => true,
            'profile' => config('dashboard')['profile'],
            'verification' => config('kesesuaian-user'),
            'pageScripts' => ['js/kesesuaian-user.js'],
            'loginDate' => date('Y-m-d'),
            'loginTime' => date('H:i'),
        ]);
    }
}
