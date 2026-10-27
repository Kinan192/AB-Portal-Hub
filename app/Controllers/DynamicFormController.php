<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\DashboardData;

/**
 * Pages drawn from a question list, frontend only: the fill-in page of a dynamic form
 * (GET /form-dinamis?id=…) and of the six built-in module forms (GET /form/suhu, /form/cctv, …), and
 * the history of each (GET /form-dinamis/respons?id=…, /rekap/suhu, /rekap/cctv, …; see routes/web.php).
 * The question lists and reports live in the browser (public/assets/js/dashboard-store.js: dummy data +
 * sessionStorage, so "Edit Isi Form" changes show here). public/assets/js/form-dinamis.js draws the
 * fill-in page and keeps a submitted report in the browser; public/assets/js/form-riwayat.js draws the
 * history with one column per question. Nothing is sent to the server.
 */
final class DynamicFormController extends Controller
{
    private const ICONS = ['send', 'arrow-left', 'calendar', 'clock', 'file-up', 'star', 'check', 'circle-check', 'circle-alert', 'triangle-alert', 'table-2', 'chart-column', 'plus', 'x', 'list-checks', 'info', 'search', 'eye', 'history', 'refresh-ccw', 'chevron-left', 'chevron-right', 'file-pen-line'];

    public function fill(): void
    {
        $id = $this->formId();
        if ($id !== null) {
            $this->page('form-dinamis/fill', 'Isi Form', $id, [
                'backToDashboard' => true,
                'rekapUrl' => null,
            ]);
        }
    }

    public function responses(): void
    {
        $id = $this->formId();
        if ($id !== null) {
            $this->page('form-dinamis/respons', 'Riwayat Respons', $id, [
                'backToDashboard' => true,
                'fillUrl' => url('/form-dinamis') . '?id=' . rawurlencode($id),
                'builtin' => false,
            ]);
        }
    }

    /** A built-in module form (card id in database/dummy/dashboard.php); "Lihat Rekap" opens history($id). */
    public function builtin(string $id): void
    {
        $data = dummy('dashboard');
        $this->page('form-dinamis/fill', $data['forms'][$id]['title'] ?? 'Isi Form', $id, [
            'backToDashboard' => true,
            'rekapUrl' => url($this->card($id)['rekap']),
        ]);
    }

    /** History of a built-in module form; $module names its breadcrumb (config/monitoring-navigation.php). */
    public function history(string $id, string $module): void
    {
        $this->page('form-dinamis/respons', config('monitoring-navigation')[$module]['report'], $id, [
            'backToDashboard' => true,
            'breadcrumbs' => monitoring_breadcrumbs($module),
            'fillUrl' => url($this->card($id)['url']),
            'builtin' => true,
        ]);
    }

    private function card(string $id): array
    {
        $cards = array_filter(dummy('dashboard')['cards'], static fn (array $card): bool => $card['id'] === $id && $card['kind'] === 'builtin');
        return current($cards);
    }

    private function formId(): ?string
    {
        $id = (string) ($_GET['id'] ?? '');
        if (preg_match('/^[a-z0-9-]{1,64}$/', $id) !== 1) {
            redirect('/');
            return null;
        }
        return $id;
    }

    private function page(string $view, string $title, string $id, array $extra): void
    {
        $data = dummy('dashboard');
        $script = $view === 'form-dinamis/respons' ? 'js/form-riwayat.js' : 'js/form-dinamis.js';
        $this->render($view, $extra + [
            'pageTitle' => $title,
            'portalPage' => true,
            'formId' => $id,
            'clientState' => DashboardData::clientState($data),
            'iconNames' => DashboardData::iconNames($data, self::ICONS),
            'pageScripts' => ['js/dashboard-store.js', $script],
        ], 'main');
    }
}
