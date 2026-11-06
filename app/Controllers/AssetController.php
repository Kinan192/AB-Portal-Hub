<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database;
use App\Core\SecurityHeaders;
use App\Models\AssetRepository;
use App\Models\Attachment;
use App\Services\AssetLedger;
use App\Services\FileUpload;
use PDOException;
use RuntimeException;
use Throwable;

/**
 * Aset IT (IT Asset Management, design docs Portal_Web_RS_Awal_Bros_Botania/ASET-IT-README.md).
 * GET /aset: the module's menu. GET /aset/{registrasi|penempatan|lacak|servis|disposal|rekap}: the forms and the
 * recap, drawn by the design's scripts in public/assets/aset. GET /aset/data: every asset record as JSON.
 * POST /aset/perintah {commands: [{op, args}]}: the operations of one form submit, replayed on the server
 * (App\Services\AssetLedger) in one transaction; answers the new state, or 422 with the broken rule.
 */
final class AssetController extends Controller
{
    /** page => [title, page script] */
    public const PAGES = [
        'registrasi' => ['Registrasi Perangkat', 'aset/aset-register.js'],
        'penempatan' => ['Penempatan Perangkat', 'aset/aset-place.js'],
        'lacak' => ['Lacak Perangkat', 'aset/aset-track.js'],
        'servis' => ['Servis & Pemeliharaan', 'aset/aset-service.js'],
        'disposal' => ['Disposal Perangkat', 'aset/aset-disposal.js'],
        'rekap' => ['Rekap Aset IT', 'aset/aset-rekap.js'],
    ];
    /** Recap of one part (GET /aset/rekap-<view>), linked from each card of the Aset IT menu: view => [title, lead]. */
    public const RECAP_VIEWS = [
        'perangkat' => ['Rekap Daftar Perangkat', 'Semua perangkat terdaftar per kategori dan lokasi, dengan filter dan ekspor CSV.'],
        'perpindahan' => ['Rekap Penempatan & Perpindahan', 'Riwayat perpindahan lokasi perangkat, terbaru dulu.'],
        'servis' => ['Rekap Riwayat Servis', 'Semua laporan servis dan pemeliharaan dari formulir Servis.'],
        'disposal' => ['Rekap Disposal', 'Semua pengajuan, persetujuan, dan pelaksanaan disposal perangkat.'],
    ];
    private const MAX_COMMANDS = 20;
    /** Documents of the module (POST /aset/berkas): attachments of owner "asset", field = kind. */
    private const FILE_OWNER = 'asset';
    private const FILE_KINDS = ['berita_acara', 'invoice', 'disposal'];
    private const FILE_BYTES = 10 * 1024 * 1024;

    public function index(): void
    {
        $this->render('aset/index', ['pageTitle' => 'Aset IT', 'portalPage' => true, 'backToDashboard' => true], 'main');
    }

    public function registrasi(): void { $this->page('registrasi'); }
    public function penempatan(): void { $this->page('penempatan'); }
    public function lacak(): void { $this->page('lacak'); }
    public function servis(): void { $this->page('servis'); }
    public function disposal(): void { $this->page('disposal'); }
    public function rekap(): void { $this->page('rekap'); }
    public function rekapPerangkat(): void { $this->page('rekap', 'perangkat'); }
    public function rekapPerpindahan(): void { $this->page('rekap', 'perpindahan'); }
    public function rekapServis(): void { $this->page('rekap', 'servis'); }
    public function rekapDisposal(): void { $this->page('rekap', 'disposal'); }

    public function data(): void
    {
        try {
            $state = (new AssetRepository(Database::connection()))->load();
        } catch (PDOException $exception) {
            error_log('Aset IT data: ' . $exception->getMessage());
            json_response(['message' => 'Data aset tidak dapat dibaca. Periksa database (php bin/migrate.php).'], 503);
            return;
        }
        json_response(['state' => self::forBrowser($state)]);
    }

    public function command(): void
    {
        $input = json_decode((string) file_get_contents('php://input'), true);
        $commands = is_array($input) && is_array($input['commands'] ?? null) ? array_values($input['commands']) : [];
        if ($commands === [] || count($commands) > self::MAX_COMMANDS) {
            json_response(['message' => 'Tidak ada perubahan aset yang dikirim.'], 422);
            return;
        }
        $user = Auth::user();
        $by = trim((string) ($user['name'] ?? '')) ?: (string) ($user['username'] ?? 'Admin');
        $pdo = Database::connection();
        try {
            $pdo->beginTransaction();
            $repository = new AssetRepository($pdo);
            $ledger = new AssetLedger($repository->load(true), mb_substr($by, 0, 150), gmdate('Y-m-d\TH:i:s.000\Z'), $this->uploadedFiles($pdo, $commands));
            foreach ($commands as $command) {
                $ledger->apply($command);
            }
            $changes = $ledger->changes();
            $repository->save($changes);
            foreach ($changes['activity'] as $activity) {
                Audit::log(str_starts_with($activity['type'], 'register') ? 'create' : 'update', 'asset', null,
                    ['action' => $activity['type'], 'text' => $activity['text']], mb_substr($activity['deviceId'], 0, 30), null, $pdo);
            }
            $pdo->commit();
        } catch (RuntimeException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            json_response(['message' => $exception->getMessage()], 422);
            return;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Aset IT simpan: ' . $exception->getMessage());
            json_response(['message' => 'Perubahan aset tidak tersimpan. Coba lagi.'], 500);
            return;
        }
        json_response(['state' => self::forBrowser($ledger->state())]);
    }

    /**
     * POST /aset/berkas (multipart: file, jenis = berita_acara | invoice | disposal): one document of the module —
     * berita acara of a service, purchase invoice of a device, disposal document. PDF, JPG or PNG up to 10 MB,
     * kept as an attachment (owner "asset", outside the web root, /berkas/{id} for a signed-in session). It counts
     * only once an operation lists it (AssetLedger::attach()).
     */
    public function upload(): void
    {
        $kind = is_string($_POST['jenis'] ?? null) && in_array($_POST['jenis'], self::FILE_KINDS, true) ? $_POST['jenis'] : '';
        $file = is_array($_FILES['file'] ?? null) && !is_array($_FILES['file']['name'] ?? null) ? $_FILES['file'] : [];
        if ($kind === '') {
            json_response(['message' => 'Jenis berkas tidak dikenal.'], 422);
            return;
        }
        $check = FileUpload::check($file, ['pdf', 'jpg', 'jpeg', 'png'], self::FILE_BYTES);
        if (isset($check['empty']) || isset($check['error'])) {
            json_response(['message' => $check['error'] ?? 'Pilih berkas PDF, JPG, atau PNG.'], 422);
            return;
        }
        try {
            $id = (new Attachment(Database::connection()))->storeUpload($file['tmp_name'], (string) $file['name'], $check['ext'], $check['mime'], self::FILE_OWNER, 0, $kind);
            $stored = (new Attachment(Database::connection()))->find($id);
        } catch (Throwable $exception) {
            error_log('Berkas aset: ' . $exception->getMessage());
            json_response(['message' => 'Berkas tidak tersimpan. Coba lagi.'], 500);
            return;
        }
        Audit::tryLog('create', 'asset', null, ['action' => 'upload', 'text' => 'Berkas ' . $kind . ' diunggah: ' . ($stored['original_name'] ?? '')]);
        json_response(['file' => ['id' => $id, 'name' => $stored['original_name'] ?? '', 'type' => self::fileType((string) ($stored['mime_type'] ?? '')), 'size' => (int) ($stored['size_bytes'] ?? 0)]]);
    }

    /** The asset files the commands refer to (docs / invoices lists), as AssetLedger takes them: id => [name, type, size]. */
    private function uploadedFiles(\PDO $pdo, array $commands): array
    {
        $ids = [];
        array_walk_recursive($commands, static function ($value, $key) use (&$ids): void {
            if ($key === 'id' && is_int($value) && $value > 0) {
                $ids[$value] = true;
            }
        });
        if ($ids === []) {
            return [];
        }
        $ids = array_slice(array_keys($ids), 0, 100);
        $statement = $pdo->prepare('SELECT id, original_name, mime_type, size_bytes FROM attachments WHERE owner_type = ? AND deleted_at IS NULL AND id IN (' . implode(', ', array_fill(0, count($ids), '?')) . ')');
        $statement->execute([self::FILE_OWNER, ...$ids]);
        $files = [];
        foreach ($statement->fetchAll() as $row) {
            $files[(int) $row['id']] = ['name' => (string) $row['original_name'], 'type' => self::fileType((string) $row['mime_type']), 'size' => (int) $row['size_bytes']];
        }
        return $files;
    }

    private static function fileType(string $mime): string
    {
        return $mime === 'application/pdf' ? 'pdf' : (str_starts_with($mime, 'image/') ? 'image' : 'file');
    }

    /**
     * POST /aset/diagnosa {info: {...}, frame: "data:image/jpeg;base64,…"}: what the camera scanner saw when it read
     * nothing for a while (reader, camera size, frames tried, errors, one picture of the middle of the frame), kept
     * in storage/logs/aset-scan/ (outside the web root, last 30 reports) to find out why a phone does not read.
     */
    public function diagnose(): void
    {
        $raw = (string) file_get_contents('php://input');
        $input = strlen($raw) <= 3_000_000 ? json_decode($raw, true) : null;
        if (!is_array($input) || !is_array($input['info'] ?? null)) {
            json_response(['message' => 'Laporan diagnosa tidak valid.'], 422);
            return;
        }
        $dir = BASE_PATH . '/storage/logs/aset-scan';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            json_response(['message' => 'Folder diagnosa tidak dapat dibuat.'], 500);
            return;
        }
        $name = gmdate('Ymd-His') . '-' . bin2hex(random_bytes(3));
        $user = Auth::user();
        $info = ['at' => gmdate('c'), 'user' => $user['username'] ?? '', 'ip' => $_SERVER['REMOTE_ADDR'] ?? '', 'agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 300)]
            + array_slice($input['info'], 0, 40, true);
        file_put_contents($dir . '/' . $name . '.json', json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
        $frame = is_string($input['frame'] ?? null) ? $input['frame'] : '';
        if (preg_match('#^data:image/jpeg;base64,([A-Za-z0-9+/=]+)$#', $frame, $m) === 1) {
            $jpeg = base64_decode($m[1], true);
            if ($jpeg !== false && str_starts_with($jpeg, "\xFF\xD8")) {
                file_put_contents($dir . '/' . $name . '.jpg', $jpeg);
            }
        }
        $files = glob($dir . '/*.json') ?: [];
        sort($files);
        foreach (array_slice($files, 0, max(0, count($files) - 30)) as $old) {
            @unlink($old);
            @unlink(substr($old, 0, -5) . '.jpg');
        }
        json_response(['ok' => true]);
    }

    /** JSON objects where the browser expects objects (an empty PHP array would become []). */
    public static function forBrowser(array $state): array
    {
        $devices = [];
        foreach ($state['devices'] as $id => $device) {
            $device['spec'] = (object) ($device['spec'] ?? []);
            $device['location'] = (object) ($device['location'] ?? []);
            $devices[(string) $id] = $device;
        }
        $state['devices'] = (object) $devices;
        return $state;
    }

    private function page(string $key, string $view = ''): void
    {
        [$title, $script] = self::PAGES[$key];
        $lead = 'Ringkasan inventaris perangkat keras, servis, perpindahan, dan disposal.';
        if ($view !== '') {
            [$title, $lead] = self::RECAP_VIEWS[$view];
        }
        // The design's scripts draw markup with style attributes (progress bars, small layout tweaks); the camera
        // scanner compiles the portal's own zxing-cpp WebAssembly (public/assets/aset/vendor) and reads photos (blob:).
        header('Content-Security-Policy: ' . strtr(SecurityHeaders::CSP, [
            "script-src 'self'" => "script-src 'self' 'wasm-unsafe-eval'",
            "style-src 'self'" => "style-src 'self' 'unsafe-inline'",
            "img-src 'self' https: data:" => "img-src 'self' https: data: blob:",
        ]));
        $user = Auth::user();
        $this->render('aset/page', [
            'pageTitle' => $title,
            'asetPage' => $key,
            'recapView' => $view,
            'recapLead' => $lead,
            'portalPage' => true,
            'backToDashboard' => true,
            // The forms and the recap go back to the Aset IT menu, which goes back to the portal.
            'backHref' => url('/aset'),
            'backLabel' => 'Kembali ke Dashboard Aset IT',
            // Forms: the button sits beside the 760 px column on wide screens, like the dynamic forms.
            'breadcrumbWidth' => $key === 'rekap' ? '' : 'breadcrumb-fill',
            'bodyClass' => 'aset-shell',
            'pageStyles' => ['aset/aset-theme.css', 'aset/aset.css'],
            'pageScripts' => ['aset/icons.js', 'aset/aset-shim.js', 'aset/aset-store.js', 'aset/aset-ui.js', 'aset/aset-gf.js', $script],
            'bodyData' => ['data-aset-diag' => url_base() . '/aset/diagnosa', 'data-aset-api' => url_base() . '/aset', 'data-user-name' => trim((string) ($user['name'] ?? '')) ?: 'Admin', 'data-page' => 'aset-' . $key, 'data-rekap-view' => $view !== '' ? $view : null],
        ], 'main');
    }
}
