<?php
declare(strict_types=1);

namespace App\Controllers\Forms;

use App\Core\View;

final class AntrianOnlineController
{
    public function history(): void
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
        View::render('reports/antrian-online', [
            'pageTitle' => 'Rekap & Riwayat Monitoring Antrian Online BPJS',
            'breadcrumbs' => [
                ['label' => 'Monitoring Harian'],
                ['label' => 'Antrian Online BPJS', 'href' => '/forms/antrian-online'],
                ['label' => 'Rekap Monitoring Antrian Online BPJS'],
            ],
            'profile' => config('dashboard')['profile'],
            'report' => $report,
            'records' => $records,
            'shifts' => config('antrian-online')['shifts'],
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

    public function index(): void
    {
        if (config('app')['environment'] !== 'local') {
            http_response_code(503);
            View::render('errors/unavailable', ['pageTitle' => 'Portal belum tersedia']);
            return;
        }

        $hour = (int) date('G');
        View::render('forms/antrian-online', [
            'pageTitle' => 'Form Monitoring Antrian Online BPJS',
            'breadcrumbs' => [
                ['label' => 'Monitoring Harian'],
                ['label' => 'Form Monitoring Antrian Online BPJS'],
            ],
            'profile' => config('dashboard')['profile'],
            'monitoring' => config('antrian-online'),
            'recordedAt' => date(DATE_ATOM),
            'initialShift' => $hour >= 7 && $hour < 14 ? 'pagi' : ($hour >= 14 && $hour < 21 ? 'siang' : 'malam'),
            'pageScripts' => ['js/antrian-online.js'],
        ]);
    }
}
