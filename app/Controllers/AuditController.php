<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Models\AuditLogs;
use App\Services\ActivityLog;
use PDOException;

/**
 * GET /riwayat-aktivitas: the change log, read only (PRD SEC-12, decision Q18). Every create, edit, delete,
 * export, login, failed login, logout and password change, newest first, with date, action, object and word
 * filters kept in the URL. The page offers no edit or delete; there is no route that changes audit_logs.
 */
final class AuditController extends Controller
{
    public function index(): void
    {
        $filters = ActivityLog::filters($_GET);
        try {
            $logs = new AuditLogs(Database::connection());
            $page = $logs->page($filters, $filters['page'], ActivityLog::PER_PAGE);
            $pages = max(1, (int) ceil($page['total'] / ActivityLog::PER_PAGE));
            if ($filters['page'] > $pages) {
                $filters['page'] = $pages;
                $page = $logs->page($filters, $pages, ActivityLog::PER_PAGE);
            }
        } catch (PDOException $exception) {
            error_log('Riwayat aktivitas: ' . $exception->getMessage());
            http_response_code(503);
            $this->render('errors/design-unavailable', [
                'pageTitle' => 'Database belum siap',
                'errorTitle' => 'Database belum siap',
                'errorDescription' => 'Riwayat aktivitas tidak dapat dibaca. Periksa koneksi database, lalu muat ulang halaman.',
            ]);
            return;
        }
        $from = ($filters['page'] - 1) * ActivityLog::PER_PAGE;
        // Paging links keep the filters (FR-R4).
        $query = array_filter(['dari' => $filters['from'], 'sampai' => $filters['to'], 'aksi' => $filters['group'], 'objek' => $filters['entity'], 'q' => $filters['q']], static fn (string $v): bool => $v !== '');
        $link = static fn (int $n): string => url('/riwayat-aktivitas') . '?' . http_build_query($query + ($n > 1 ? ['halaman' => $n] : []));
        $this->render('account/activity', [
            'pageTitle' => 'Riwayat aktivitas',
            'portalPage' => true,
            'backToDashboard' => true,
            'breadcrumbs' => [['label' => 'Riwayat aktivitas']],
            'filters' => $filters,
            'rows' => array_map(static fn (array $row): array => ActivityLog::row($row), $page['rows']),
            'countText' => $page['total'] > 0 ? 'Menampilkan ' . ($from + 1) . '–' . ($from + count($page['rows'])) . ' dari ' . $page['total'] . ' catatan' : '0 catatan',
            'pages' => $pages,
            'prevUrl' => $filters['page'] > 1 ? $link($filters['page'] - 1) : null,
            'nextUrl' => $filters['page'] < $pages ? $link($filters['page'] + 1) : null,
            'filtered' => $query !== [],
        ], 'main');
    }
}
