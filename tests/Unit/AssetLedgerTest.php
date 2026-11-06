<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AssetLedger;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AssetLedgerTest extends TestCase
{
    private const NOW = '2026-11-03T08:00:00.000Z';

    private function ledger(array $state = []): AssetLedger
    {
        return new AssetLedger($state, 'Admin IT', self::NOW);
    }

    private function pc(array $extra = []): array
    {
        return $extra + ['id' => 'ab-btn-pc-0001', 'name' => 'PC Admisi 1', 'department' => 'Admisi & Pendaftaran', 'category' => 'pc', 'brand' => 'HP', 'model' => 'ProDesk', 'serial' => 'SN1',
            'ip' => '10.20.1.21', 'mac' => '3cd92b1a2f4e', 'location' => ['building' => 'Gedung Utama', 'floor' => 'Lantai 1', 'dept' => 'Admisi & Pendaftaran', 'room' => 'Loket 1']];
    }

    private function registered(): AssetLedger
    {
        $ledger = $this->ledger();
        $ledger->register($this->pc());
        return $ledger;
    }

    private function expectRule(string $message, callable $action): void
    {
        try {
            $action();
            self::fail('Expected: ' . $message);
        } catch (RuntimeException $exception) {
            self::assertStringContainsString($message, $exception->getMessage());
        }
    }

    public function testRegisterNormalisesIdAndMacAndLogsTheServerAccount(): void
    {
        $ledger = $this->registered();
        $device = $ledger->state()['devices']['AB-BTN-PC-0001'];
        self::assertSame('3C:D9:2B:1A:2F:4E', $device['mac']);
        self::assertSame('aktif', $device['status']);
        self::assertSame('Admin IT', $device['createdBy']);
        self::assertSame(self::NOW, $device['createdAt']);
        $changes = $ledger->changes();
        self::assertCount(1, $changes['devices']);
        self::assertSame('Perangkat diregistrasi di Admisi & Pendaftaran · Loket 1 (Gedung Utama, Lantai 1)', $changes['activity'][0]['text']);
    }

    public function testRegisterRejectsDuplicatesBadFormatsAndConflicts(): void
    {
        $ledger = $this->registered();
        $this->expectRule('sudah terdaftar', fn () => $ledger->register($this->pc()));
        $this->expectRule('Format IP', fn () => $ledger->register($this->pc(['id' => 'ITEM-00000001', 'ip' => '10.20.1.300', 'mac' => '', 'serial' => ''])));
        $this->expectRule('MAC harus', fn () => $ledger->register($this->pc(['id' => 'ITEM-00000002', 'ip' => '', 'mac' => '12:34', 'serial' => ''])));
        $this->expectRule('IP sudah dipakai oleh PC Admisi 1', fn () => $ledger->register($this->pc(['id' => 'ITEM-00000003', 'mac' => '', 'serial' => ''])));
        $this->expectRule('Serial number sudah dipakai', fn () => $ledger->register($this->pc(['id' => 'ITEM-00000004', 'ip' => '', 'mac' => ''])));
        $this->expectRule('Pilih kategori', fn () => $ledger->register($this->pc(['id' => 'ITEM-00000005', 'category' => 'toaster'])));
    }

    public function testDepartmentIsAskedAtRegistrationAndKeptOnEdit(): void
    {
        $ledger = $this->ledger();
        $this->expectRule('departemen', fn () => $ledger->register($this->pc(['department' => '  '])));
        self::assertSame('Admisi & Pendaftaran', $ledger->register($this->pc())['department']);
        $this->expectRule('departemen', fn () => $ledger->update('AB-BTN-PC-0001', ['department' => '']));
        self::assertSame('Farmasi', $ledger->update('AB-BTN-PC-0001', ['department' => 'Farmasi'])['department']);
        self::assertStringContainsString('Departemen', end($ledger->changes()['activity'])['text']);
        // An edit that does not send it (an older device) keeps whatever it has.
        self::assertSame('Farmasi', $ledger->update('AB-BTN-PC-0001', ['notes' => 'x'])['department']);
    }

    public function testDeviceWithoutBarcodeGetsTheNextFreeIdOfItsCategory(): void
    {
        $ledger = $this->registered();
        $laser = ['autoId' => true, 'id' => 'whatever-the-page-guessed', 'name' => 'Remot Laser', 'department' => 'Diklat', 'category' => 'other', 'brand' => 'Logitech', 'model' => 'R400', 'spec' => ['kind' => 'Remote Laser']];
        self::assertSame('AB-BTN-OTH-0001', $ledger->register($laser)['id']);
        self::assertSame('AB-BTN-OTH-0002', $ledger->register($laser)['id']);
        self::assertSame('AB-BTN-PC-0002', $ledger->register(['category' => 'pc', 'name' => 'PC Diklat', 'autoId' => true] + $this->pc(['ip' => '', 'mac' => '', 'serial' => '']))['id']);
        $this->expectRule('Pilih kategori', fn () => $ledger->register(['category' => ''] + $laser));
    }

    public function testRegisterRejectsCodesThatAreNotAssetCodes(): void
    {
        $ledger = $this->ledger();
        $this->expectRule('tidak lengkap', fn () => $ledger->register($this->pc(['id' => 'ITEM-000498'])));
        $this->expectRule('tidak lengkap', fn () => $ledger->register($this->pc(['id' => 'AB-BTN-PC-01'])));
        $this->expectRule('tidak dikenali', fn () => $ledger->register($this->pc(['id' => '8991234567890'])));
        $this->expectRule('tidak dikenali', fn () => $ledger->register($this->pc(['id' => 'https://inventarisreg.awalbros-hospital.com/asset-inventaris/history'])));
        self::assertNull(AssetLedger::codeProblem('AB-BTN-CCTV-0012'));
        self::assertSame([], $ledger->changes()['devices']);
    }

    public function testVendorDeadlineIsOptionalNotBeforeTheReportAndLoggedWhenMoved(): void
    {
        $ledger = $this->registered();
        $this->expectRule('sebelum tanggal laporan', fn () => $ledger->addService(['deviceId' => 'AB-BTN-PC-0001', 'type' => 'Perbaikan', 'problem' => 'Mati', 'reportDate' => '2026-11-03', 'dueDate' => '2026-11-02']));
        $this->expectRule('tidak valid', fn () => $ledger->addService(['deviceId' => 'AB-BTN-PC-0001', 'type' => 'Perbaikan', 'problem' => 'Mati', 'reportDate' => '2026-11-03', 'dueDate' => '7/11/2026']));
        $t = $ledger->addService(['deviceId' => 'AB-BTN-PC-0001', 'type' => 'Perbaikan', 'problem' => 'Mati', 'reportDate' => '2026-11-03', 'dueDate' => '2026-11-07', 'technician' => 'Vendor A']);
        self::assertSame('2026-11-07', $t['dueDate']);
        $t = $ledger->updateService($t['id'], ['dueDate' => '2026-11-10']);
        self::assertSame('2026-11-10', $t['dueDate']);
        self::assertSame('Tenggat vendor: 10 Nov 2026', end($t['log'])['note']);
        $t = $ledger->updateService($t['id'], ['note' => 'Vendor minta tambahan waktu', 'dueDate' => '']);
        self::assertSame('', $t['dueDate']);
        self::assertSame('Vendor minta tambahan waktu · Tenggat vendor dihapus', end($t['log'])['note']);
        self::assertSame('', $ledger->updateService($t['id'], ['note' => 'Cek', 'dueDate' => null])['dueDate']);
    }

    public function testServiceLocksTheStatusAndTheTechnicianPicksTheEndStatus(): void
    {
        $ledger = new AssetLedger([], 'Admin IT', self::NOW, [5 => ['name' => 'BA-rusak.jpg', 'type' => 'image', 'size' => 300]]);
        $ledger->register($this->pc());
        $ticket = $ledger->addService(['deviceId' => 'AB-BTN-PC-0001', 'type' => 'Perbaikan', 'problem' => 'Tidak menyala', 'reportDate' => '2026-11-03']);
        self::assertSame('SRV-2026-0001', $ticket['id']);
        self::assertSame('servis', $ledger->state()['devices']['AB-BTN-PC-0001']['status']);
        $this->expectRule('masih memiliki laporan servis', fn () => $ledger->addService(['deviceId' => 'AB-BTN-PC-0001', 'type' => 'Perbaikan', 'problem' => 'Lagi']));
        $this->expectRule('dikunci', fn () => $ledger->update('AB-BTN-PC-0001', ['status' => 'aktif']));
        $this->expectRule('Selesaikan dulu laporan servis SRV-2026-0001', fn () => $ledger->requestDisposal(['deviceId' => 'AB-BTN-PC-0001', 'reason' => 'Usang', 'condition' => 'Rusak']));
        // Selesai with the device found broken needs its berita acara; with it attached the service closes as Rusak.
        $this->expectRule('berita acara kerusakan', fn () => $ledger->updateService('SRV-2026-0001', ['status' => 'done', 'afterStatus' => 'rusak']));
        $done = $ledger->updateService('SRV-2026-0001', ['status' => 'done', 'afterStatus' => 'rusak', 'docs' => [['id' => 5]]]);
        self::assertSame('rusak', $done['afterStatus']);
        self::assertSame('2026-11-03', $done['completedAt']);
        self::assertSame(['BA-rusak.jpg'], array_column($done['docs'], 'name'));
        self::assertCount(2, $done['log']);
        self::assertSame('rusak', $ledger->state()['devices']['AB-BTN-PC-0001']['status']);
        // Selesai with the device in working order needs no document.
        $again = $ledger->addService(['deviceId' => 'AB-BTN-PC-0001', 'type' => 'Pemeliharaan Rutin', 'problem' => 'Cek berkala']);
        self::assertSame('aktif', $ledger->updateService($again['id'], ['status' => 'done', 'afterStatus' => 'aktif'])['afterStatus']);
    }

    public function testRejectedDisposalRestoresTheStatusAndCompletedOneArchives(): void
    {
        $ledger = $this->registered();
        $ledger->update('AB-BTN-PC-0001', ['status' => 'cadangan']);
        $first = $ledger->requestDisposal(['deviceId' => 'AB-BTN-PC-0001', 'reason' => 'Usang', 'condition' => 'Berfungsi']);
        self::assertSame('disposal', $ledger->state()['devices']['AB-BTN-PC-0001']['status']);
        $this->expectRule('sudah memiliki pengajuan disposal', fn () => $ledger->requestDisposal(['deviceId' => 'AB-BTN-PC-0001', 'reason' => 'Usang', 'condition' => 'Berfungsi']));
        $this->expectRule('setelah disetujui', fn () => $ledger->completeDisposal($first['id'], ['method' => 'Lelang']));
        $ledger->rejectDisposal($first['id'], ['approvedBy' => 'Manajer IT']);
        self::assertSame('cadangan', $ledger->state()['devices']['AB-BTN-PC-0001']['status']);

        $second = $ledger->requestDisposal(['deviceId' => 'AB-BTN-PC-0001', 'reason' => 'Rusak berat', 'condition' => 'Rusak total']);
        self::assertSame('DSP-2026-0002', $second['id']);
        $ledger->approveDisposal($second['id'], ['approvedBy' => 'Manajer IT', 'role' => 'Manajer IT']);
        $ledger->completeDisposal($second['id'], ['method' => 'Lelang', 'reference' => 'BA-1', 'date' => '2026-11-04']);
        $device = $ledger->state()['devices']['AB-BTN-PC-0001'];
        self::assertSame('disposed', $device['status']);
        self::assertSame($second['id'], $device['disposalId']);
        $this->expectRule('sudah dihapuskan', fn () => $ledger->update('AB-BTN-PC-0001', ['name' => 'Baru']));
        // A disposed device frees its IP, MAC and serial for a new one.
        $ledger->register($this->pc(['id' => 'AB-BTN-PC-0002']));
        self::assertCount(2, $ledger->changes()['disposals']);
    }

    public function testMoveAndPlaceRecordTheHistory(): void
    {
        $ledger = $this->ledger();
        $ledger->register($this->pc(['location' => null]));
        self::assertNull($ledger->place('AB-BTN-PC-0001', ['location' => ['building' => 'Gedung Utama', 'dept' => 'UGD', 'room' => 'Triase'], 'pic' => 'Rina']));
        self::assertSame([], $ledger->changes()['moves']);
        $move = $ledger->place('AB-BTN-PC-0001', ['location' => ['building' => 'Gedung Utama', 'dept' => 'ICU', 'room' => 'Nurse Station']]);
        self::assertSame('MV00001', $move['id']);
        self::assertSame('UGD', $move['from']['dept']);
        self::assertSame('Rina', $move['picTo']);
        $this->expectRule('sama dengan lokasi saat ini', fn () => $ledger->move('AB-BTN-PC-0001', ['to' => $move['to'], 'reason' => 'Relokasi unit kerja']));
        $this->expectRule('alasan', fn () => $ledger->move('AB-BTN-PC-0001', ['to' => ['dept' => 'Farmasi']]));
    }

    public function testOtherCategoryNeedsItsOwnTypeAndUnitsCanBeNew(): void
    {
        $ledger = $this->ledger();
        $proj = ['id' => 'ITEM-00000010', 'name' => 'Proyektor Aula', 'department' => 'Diklat', 'category' => 'other', 'brand' => 'Epson', 'model' => 'EB-X51'];
        $this->expectRule('jenis perangkat', fn () => $ledger->register($proj));
        $d = $ledger->register($proj + ['spec' => ['kind' => 'Proyektor'], 'location' => ['dept' => 'Aula Serbaguna', 'room' => 'Panggung']]);
        self::assertSame('Proyektor', $d['spec']['kind']);
        // A unit outside the built-in list is kept as typed.
        self::assertSame('Aula Serbaguna', $d['location']['dept']);
        $this->expectRule('jenis perangkat', fn () => $ledger->update('ITEM-00000010', ['spec' => []]));
    }

    public function testLocationIsUnitAndRoomWithoutGedungLantai(): void
    {
        // An older record with Gedung / Lantai, placed again at the same unit and room without them: not a move.
        $ledger = $this->registered();
        $this->expectRule('sama dengan lokasi saat ini', fn () => $ledger->move('AB-BTN-PC-0001',
            ['to' => ['building' => '', 'floor' => '', 'dept' => 'Admisi & Pendaftaran', 'room' => 'Loket 1'], 'reason' => 'Relokasi unit kerja']));
        self::assertSame('Admisi & Pendaftaran · Loket 1', AssetLedger::locText(['building' => '', 'floor' => '', 'dept' => 'Admisi & Pendaftaran', 'room' => 'Loket 1']));
        self::assertSame('ICU · Bed 2 (Gedung Utama, Lantai 3)', AssetLedger::locText(['building' => 'Gedung Utama', 'floor' => 'Lantai 3', 'dept' => 'ICU', 'room' => 'Bed 2']));
    }

    public function testScannedLabelLinkGivesItsAssetCode(): void
    {
        self::assertSame('ITEM-00049895', AssetLedger::normId('https://inventarisreg.awalbros-hospital.com/asset-inventaris/history/ITEM-00049895'));
        self::assertSame('ITEM-00049895', AssetLedger::normId("HTTPS://INVENTARISREG.AWALBROS-HOSPITAL.COM/ASSET-INVENTARIS/HISTORY/ITEM-00049895/?x=1\r\n"));
        self::assertSame('AB-BTN-PC-0001', AssetLedger::normId(' ab-btn-pc-0001 '));
        // a scanner that dropped the start of the link
        self::assertSame('ITEM-00049895', AssetLedger::normId('ASSET-INVENTARIS/HISTORY/ITEM-00049895'));
        $ledger = $this->ledger();
        $ledger->register($this->pc(['id' => 'https://inventarisreg.awalbros-hospital.com/asset-inventaris/history/ITEM-00049895']));
        self::assertArrayHasKey('ITEM-00049895', $ledger->state()['devices']);
    }

    public function testRejectedServiceNeedsItsBeritaAcaraAndFreesTheDevice(): void
    {
        $files = [41 => ['name' => 'BA-tolak-garansi.pdf', 'type' => 'pdf', 'size' => 1200], 42 => ['name' => 'nota.jpg', 'type' => 'image', 'size' => 900]];
        $ledger = new AssetLedger([], 'Admin IT', self::NOW, $files);
        $ledger->register($this->pc());
        $ticket = $ledger->addService(['deviceId' => 'AB-BTN-PC-0001', 'type' => 'Klaim Garansi', 'problem' => 'Mati total', 'docs' => [['id' => 42]]]);
        self::assertSame([['id' => 42, 'name' => 'nota.jpg', 'type' => 'image', 'size' => 900]], $ticket['docs']);
        // Unknown ids (not uploaded asset files) are dropped; a rejection without any document is refused.
        $bare = new AssetLedger([], 'Admin IT', self::NOW, $files);
        $bare->register($this->pc());
        $open = $bare->addService(['deviceId' => 'AB-BTN-PC-0001', 'type' => 'Perbaikan', 'problem' => 'Layar', 'docs' => [['id' => 999, 'name' => 'palsu.pdf']]]);
        self::assertSame([], $open['docs']);
        $this->expectRule('berita acara', fn () => $bare->updateService($open['id'], ['status' => 'rejected']));

        $rejected = $ledger->updateService($ticket['id'], ['status' => 'rejected', 'note' => 'Garansi ditolak vendor', 'docs' => [['id' => 41]]]);
        self::assertSame('rejected', $rejected['status']);
        self::assertSame([42, 41], array_column($rejected['docs'], 'id'));
        self::assertSame('2026-11-03', $rejected['completedAt']);
        self::assertStringContainsString('BA-tolak-garansi.pdf', end($rejected['log'])['note']);
        self::assertSame('rusak', $ledger->state()['devices']['AB-BTN-PC-0001']['status']); // default after a rejection
        $this->expectRule('sudah ditolak', fn () => $ledger->updateService($ticket['id'], ['status' => 'done']));
        // A rejected service is closed: a new one may be opened.
        self::assertSame('reported', $ledger->addService(['deviceId' => 'AB-BTN-PC-0001', 'type' => 'Perbaikan', 'problem' => 'Coba lagi'])['status']);
    }

    public function testInvoicesAreKeptAddedAndRemovedOnEdit(): void
    {
        $files = [7 => ['name' => 'invoice-1.pdf', 'type' => 'pdf', 'size' => 10], 8 => ['name' => 'faktur.png', 'type' => 'image', 'size' => 20]];
        $ledger = new AssetLedger([], 'Admin IT', self::NOW, $files);
        $device = $ledger->register($this->pc(['invoices' => [['id' => 7], ['id' => 7], ['id' => 3]]]));
        self::assertSame([7], array_column($device['invoices'], 'id'));
        $saved = $ledger->state();

        // Later request: invoice 7 is already the device's, 8 newly uploaded; then 7 is taken off the list.
        $edit = new AssetLedger($saved, 'Admin IT', self::NOW, [8 => $files[8]]);
        self::assertSame([7, 8], array_column($edit->update('AB-BTN-PC-0001', ['invoices' => [['id' => 7], ['id' => 8]]])['invoices'], 'id'));
        self::assertSame([8], array_column($edit->update('AB-BTN-PC-0001', ['invoices' => [['id' => 8]]])['invoices'], 'id'));
        self::assertStringContainsString('Invoice Pembelian', end($edit->changes()['activity'])['text']);
        $this->expectRule('Maksimal 5', fn () => (new AssetLedger([], 'A', self::NOW, array_fill(1, 6, ['name' => 'x.pdf', 'type' => 'pdf', 'size' => 1])))
            ->register($this->pc(['invoices' => array_map(fn ($i) => ['id' => $i], range(1, 6))])));
    }

    public function testApplyRejectsUnknownOperations(): void
    {
        $ledger = $this->registered();
        $this->expectRule('tidak dikenal', fn () => $ledger->apply(['op' => 'resetSample', 'args' => []]));
        $this->expectRule('tidak dikenal', fn () => $ledger->apply(['op' => 'update', 'args' => ['AB-BTN-PC-0001']]));
        $ledger->apply(['op' => 'update', 'args' => ['AB-BTN-PC-0001', ['notes' => "Baris 1\nBaris 2"]]]);
        self::assertSame("Baris 1\nBaris 2", $ledger->state()['devices']['AB-BTN-PC-0001']['notes']);
        self::assertSame('AB-BTN-PC-0002', AssetLedger::nextId($ledger->state()['devices'], 'pc'));
    }
}
