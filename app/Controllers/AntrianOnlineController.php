<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;

final class AntrianOnlineController
{
    public function rekap(): void
    {
        if (config('app')['environment'] !== 'local') {
            http_response_code(503);
            View::render('errors/unavailable', ['pageTitle' => 'Portal belum tersedia']);
            return;
        }

        $report = config('antrian-online-reports');
        $records = $report['records'];
        usort($records, static fn (array $a, array $b): int => strcmp($b['at'], $a['at']));
        $periodRecords = array_values(array_filter($records, static fn (array $record): bool => str_starts_with($record['at'], $report['default_period'])));
        $total = count($periodRecords);
        $queueSent = count(array_filter($periodRecords, static fn (array $record): bool => $record['queue_sent']));
        $bedSent = count(array_filter($periodRecords, static fn (array $record): bool => $record['bed_sent']));
        View::render('antrian-online/rekap', [
            'pageTitle' => 'Rekap & Riwayat Monitoring Antrian Online BPJS',
            'breadcrumbs' => monitoring_breadcrumbs('antrian-online', true),
            'profile' => config('dashboard')['profile'],
            'report' => $report,
            'records' => $records,
            'summary' => [
                'total' => $total,
                'queue_sent' => $queueSent,
                'bed_sent' => $bedSent,
                'queue_percentage' => $total ? round($queueSent / $total * 100, 1) : null,
                'bed_percentage' => $total ? round($bedSent / $total * 100, 1) : null,
                'latest' => $periodRecords[0] ?? null,
            ],
            'pageScripts' => ['js/antrian-online-history.js'],
        ]);
    }

    public function form(): void
    {
        if (config('app')['environment'] !== 'local') {
            http_response_code(503);
            View::render('errors/unavailable', ['pageTitle' => 'Portal belum tersedia']);
            return;
        }

        View::render('antrian-online/form', [
            'pageTitle' => 'Form Monitoring Antrian Online BPJS',
            'breadcrumbs' => monitoring_breadcrumbs('antrian-online'),
            'profile' => config('dashboard')['profile'],
            'monitoring' => config('antrian-online'),
            'recordedAt' => date(DATE_ATOM),

            'pageScripts' => ['js/antrian-online.js'],
        ]);
    }
}
