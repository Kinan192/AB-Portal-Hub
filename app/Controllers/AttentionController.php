<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Models\AttentionList;
use App\Services\Attention;
use PDOException;

/**
 * GET /perlu-perhatian: every report marked "Perlu perhatian", newest first ("Lihat semua data" in the header
 * bell, which only shows the last days). Filters in the URL: form=<slug>, dari / sampai (report dates, WIB),
 * halaman=<n>. Each row names the marked answers and opens the report on its recap page.
 */
final class AttentionController extends Controller
{
    private const PER_PAGE = 25;

    /**
     * POST /perlu-perhatian/dilihat {ids: [entry id, …]} or {all: true}: the signed-in account has seen these
     * reports (a click in the header bell, or "Tandai semua dilihat"). Answers the bell's new unread count.
     */
    public function markSeen(): void
    {
        $userId = Attention::userId();
        $input = json_decode((string) file_get_contents('php://input'), true);
        $input = is_array($input) ? $input : [];
        if ($userId === null) {
            json_response(['message' => 'Sesi berakhir. Silakan masuk kembali.'], 401);
            return;
        }
        try {
            $list = new AttentionList(Database::connection());
            if (($input['all'] ?? false) === true) {
                $list->markAllRead($userId, Attention::DAYS);
            } else {
                $list->markRead($userId, array_slice(is_array($input['ids'] ?? null) ? $input['ids'] : [], 0, 100));
            }
            $unread = $list->recent($userId, Attention::DAYS, 1)['unread'];
        } catch (PDOException $exception) {
            error_log('Tandai dilihat: ' . $exception->getMessage());
            json_response(['message' => 'Status dilihat tidak tersimpan.'], 500);
            return;
        }
        json_response(['unread' => $unread]);
    }

    public function index(): void
    {
        $text = static fn (string $key): string => is_string($_GET[$key] ?? null) ? trim($_GET[$key]) : '';
        $date = static fn (string $value): string => preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 && checkdate((int) substr($value, 5, 2), (int) substr($value, 8, 2), (int) substr($value, 0, 4)) ? $value : '';
        $filters = ['form' => preg_match('/^[a-z0-9-]{1,64}$/', $text('form')) === 1 ? $text('form') : '', 'from' => $date($text('dari')), 'to' => $date($text('sampai'))];
        $error = $filters['from'] !== '' && $filters['to'] !== '' && $filters['to'] < $filters['from'] ? 'Tanggal akhir tidak boleh sebelum tanggal awal.' : null;
        if ($error !== null) {
            $filters['from'] = $filters['to'] = '';
        }
        $page = max(1, (int) $text('halaman'));
        try {
            $list = new AttentionList(Database::connection());
            $forms = $list->forms();
            $userId = Attention::userId() ?? 0;
            $result = $list->page($userId, $filters, $page, self::PER_PAGE);
            $pages = max(1, (int) ceil($result['total'] / self::PER_PAGE));
            if ($page > $pages) {
                $page = $pages;
                $result = $list->page($userId, $filters, $page, self::PER_PAGE);
            }
        } catch (PDOException $exception) {
            error_log('Perlu perhatian: ' . $exception->getMessage());
            http_response_code(503);
            $this->render('errors/design-unavailable', [
                'pageTitle' => 'Database belum siap',
                'errorTitle' => 'Database belum siap',
                'errorDescription' => 'Data perlu perhatian tidak dapat dibaca. Periksa koneksi database, lalu muat ulang halaman.',
            ]);
            return;
        }
        $query = array_filter(['form' => $filters['form'], 'dari' => $filters['from'], 'sampai' => $filters['to']], static fn (string $v): bool => $v !== '');
        $link = static fn (int $n): string => url('/perlu-perhatian') . '?' . http_build_query($query + ($n > 1 ? ['halaman' => $n] : []));
        $from = ($page - 1) * self::PER_PAGE;
        $this->render('attention/index', [
            // Opened in a new tab from the header bell (like the editor's Pratinjau), so no "Kembali ke Dashboard".
            'pageTitle' => 'Perlu perhatian',
            'portalPage' => true,
            'filters' => $filters,
            'filterError' => $error,
            'forms' => $forms,
            'rows' => array_map([Attention::class, 'item'], $result['rows']),
            'from' => $from,
            'countText' => $result['total'] > 0 ? 'Menampilkan ' . ($from + 1) . '–' . ($from + count($result['rows'])) . ' dari ' . $result['total'] . ' laporan' : '0 laporan',
            'page' => $page,
            'pages' => $pages,
            'prevUrl' => $page > 1 ? $link($page - 1) : null,
            'nextUrl' => $page < $pages ? $link($page + 1) : null,
            'filtered' => $query !== [],
        ], 'main');
    }
}
