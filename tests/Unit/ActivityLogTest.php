<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Core\ErrorLog;
use App\Services\ActivityLog;
use PHPUnit\Framework\TestCase;

/** PRD SEC-12 (Riwayat aktivitas) and NFR-13 (daily error logs kept 90 days). */
final class ActivityLogTest extends TestCase
{
    private static function log(array $changes): array
    {
        return $changes + ['id' => 1, 'action' => 'create', 'entity_type' => 'temperature_entry', 'entity_id' => 5, 'entry_no' => null, 'reason' => null, 'ip' => '10.1.2.3', 'user_agent' => '', 'meta_json' => null, 'created_at' => '2026-10-15 03:18:00'];
    }

    public function testDateFiltersAreWibDays(): void
    {
        $f = ActivityLog::filters(['dari' => '2026-10-01', 'sampai' => '2026-10-31']);
        // 1 Oct 00:00 WIB = 30 Sep 17:00 UTC; the end is 1 Nov 00:00 WIB, exclusive.
        self::assertSame(['2026-09-30 17:00:00', '2026-10-31 17:00:00', null], [$f['from_utc'], $f['to_utc'], $f['error']]);
        self::assertSame('Tanggal akhir tidak boleh sebelum tanggal awal.', ActivityLog::filters(['dari' => '2026-10-31', 'sampai' => '2026-10-01'])['error']);
        $open = ActivityLog::filters(['dari' => '2026-10-01']);
        self::assertSame(['2026-09-30 17:00:00', null, null], [$open['from_utc'], $open['to_utc'], $open['error']], 'one date alone is an open range');
        self::assertSame('Tanggal tidak valid.', ActivityLog::filters(['sampai' => '2026-02-30'])['error']);
        self::assertNull(ActivityLog::filters(['dari' => '2026-02-30', 'sampai' => '2026-03-01'])['from_utc'], 'impossible date ignored');
    }

    public function testActionGroupsAndUnknownValues(): void
    {
        self::assertSame(['update', 'move', 'hide', 'show', 'duplicate'], ActivityLog::filters(['aksi' => 'update'])['actions']);
        self::assertSame(['login_failed', 'login_locked'], ActivityLog::filters(['aksi' => 'login_failed'])['actions']);
        $f = ActivityLog::filters(['aksi' => "x' OR 1=1", 'objek' => 'users', 'halaman' => '-4', 'q' => str_repeat('a', 300)]);
        self::assertSame([[], '', 1, 100], [$f['actions'], $f['entity'], $f['page'], mb_strlen($f['q'])]);
    }

    public function testUpdateShowsChangedFieldsWithLabels(): void
    {
        $row = ActivityLog::row(self::log([
            'action' => 'update', 'entry_no' => 'TMP-202610-0001',
            'meta_json' => json_encode(['changes' => [['field' => 'avg_temp', 'old' => '21,5', 'new' => '25,0'], ['field' => 'ac_notes', 'old' => null, 'new' => 'AC 2 mati']]]),
        ]), ['temperature_entry' => ['avg_temp' => 'Suhu (°C)', 'ac_notes' => 'Catatan']]);
        self::assertSame(['15 Okt 2026, 10:18 WIB', 'Diubah', 'Suhu server', 'TMP-202610-0001'], [$row['time'], $row['action'], $row['entity'], $row['object']]);
        self::assertSame(['Suhu (°C): 21,5 → 25,0', 'Catatan: — → AC 2 mati'], $row['details']);
    }

    public function testDeleteExportAndLoginRows(): void
    {
        $delete = ActivityLog::row(self::log(['action' => 'delete', 'entity_type' => 'cctv_entry', 'entry_no' => 'CCTV-202610-0003', 'reason' => 'Salah input shift']));
        self::assertSame(['Dihapus', 'warn', ['Alasan: Salah input shift']], [$delete['action'], $delete['tone'], $delete['details']]);

        $export = ActivityLog::row(self::log(['action' => 'export', 'entity_type' => 'rekap', 'entity_id' => null,
            'meta_json' => json_encode(['module' => 'rekap-cctv', 'format' => 'pdf', 'filters' => ['periode' => '2026-10', 'status' => 'kritis'], 'rows' => 12, 'report_id' => 'RPT-1EEF2C6406'])]));
        self::assertSame('Rekap CCTV', $export['object']);
        self::assertSame(['PDF · 12 baris · RPT-1EEF2C6406', 'Filter: periode=2026-10, status=kritis'], $export['details']);
        $form = ActivityLog::row(self::log(['action' => 'export', 'entity_type' => 'rekap', 'meta_json' => json_encode(['module' => 'rekap-kesesuaian-user', 'format' => 'xlsx', 'filters' => ['id' => 'kesesuaian-user'], 'rows' => 3, 'report_id' => 'RPT-X'])]));
        self::assertSame(['Rekap kesesuaian-user', 'Filter: tanpa filter'], [$form['object'], $form['details'][1]]);

        $failed = ActivityLog::row(self::log(['action' => 'login_failed', 'entity_type' => 'user', 'entity_id' => null, 'meta_json' => json_encode(['username' => 'admin.salah']),
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36 Edg/130.0']));
        self::assertSame(['Gagal masuk', 'bad', 'admin.salah', 'Edge · Windows'], [$failed['action'], $failed['tone'], $failed['object'], $failed['device']]);
    }

    public function testLongValuesAndManyChangesAreShortened(): void
    {
        $changes = array_map(static fn (int $i): array => ['field' => 'f' . $i, 'old' => str_repeat('x', 200), 'new' => 'b'], range(1, 9));
        $row = ActivityLog::row(self::log(['action' => 'update', 'meta_json' => json_encode(['changes' => $changes])]));
        self::assertCount(7, $row['details']);
        self::assertSame('+3 perubahan lain', $row['details'][6]);
        self::assertLessThan(100, mb_strlen($row['details'][0]));
    }

    public function testDeviceNames(): void
    {
        self::assertSame('Chrome · Android', ActivityLog::device('Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/130.0 Mobile Safari/537.36'));
        self::assertSame('Safari · iOS', ActivityLog::device('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1'));
        self::assertSame('Firefox · Linux', ActivityLog::device('Mozilla/5.0 (X11; Linux x86_64; rv:131.0) Gecko/20100101 Firefox/131.0'));
        self::assertSame('', ActivityLog::device(''));
    }

    public function testOnlyOldDailyLogsExpire(): void
    {
        $names = ['app-2026-07-29.log', 'app-2026-07-30.log', 'app-2026-07-31.log', 'app-2026-10-28.log', 'auth-dev-bypass.log', 'app-notes.log'];
        // 28 Oct minus 90 days = 30 Jul: files before that day expire.
        self::assertSame(['app-2026-07-29.log'], ErrorLog::expired($names, '2026-10-28', 90));
        self::assertSame([], ErrorLog::expired(['auth-dev-bypass.log'], '2026-10-28', 1));
    }
}
