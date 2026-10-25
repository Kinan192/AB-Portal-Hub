<?php
/**
 * @var array{
 *     source: string,
 *     default_period: string,
 *     per_page: int,
 *     periods: array<string, string>
 * } $report
 */
/**
 * @var list<array{
 *     id: string,
 *     at: string,
 *     shift: string,
 *     rack: string,
 *     status: string,
 *     cpu: string,
 *     monitor: string,
 *     environment: string,
 *     notes: string,
 *     inspector: string,
 *     initials: string,
 *     nik: string,
 *     verification: string
 * }> $records
 */
?>
<main id="main-content" class="hardware-history flex-1 bg-slate-100">
    <?php require BASE_PATH . '/app/Views/components/hardware-server-hero.php'; ?>
    <div class="page-shell report-content relative -mt-4 pb-16">
        <section class="report-toolbar mb-6 flex flex-col gap-4 rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="report-title">
            <p class="report-print-brand hidden text-sm font-bold text-slate-800"><?= e(config('app')['hospital']) ?> • IT Division</p>
            <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                <div class="min-w-0 flex-1">
                    <div class="flex items-start gap-2">
                        <?= icon('chart-search', 'mt-0.5 size-6 shrink-0 text-teal-700') ?>
                        <h2 id="report-title" class="text-lg font-bold tracking-tight text-slate-800">Log Rekapitulasi Monitoring Hardware Server</h2>
                    </div>
                    <p class="mt-0.5 text-xs leading-relaxed text-slate-500">Daftar riwayat pengecekan fisik perangkat server, rack mounting, storage, display, dan lingkungan data center.</p>
                </div>
                <div class="report-export-actions flex shrink-0 flex-wrap items-center gap-3">
                    <button type="button" data-report-export="excel" class="report-export-button bg-emerald-50 text-emerald-800 hover:bg-emerald-100" aria-haspopup="dialog"><?= icon('table', 'size-4 text-emerald-600') ?>Export Excel (.xlsx)</button>
                    <button type="button" data-report-export="pdf" class="report-export-button bg-rose-50 text-rose-800 hover:bg-rose-100" aria-haspopup="dialog"><?= icon('file', 'size-4 text-rose-600') ?>Export PDF Rekap Bulanan</button>
                    <button type="button" data-report-print class="report-export-button bg-slate-100 text-slate-700 hover:bg-slate-200"><?= icon('printer', 'size-4 text-slate-500') ?>Cetak Rekap</button>
                </div>
            </div>

            <form id="report-filters" class="grid grid-cols-1 gap-3 pt-2 sm:grid-cols-2 md:grid-cols-4" role="search" aria-label="Filter laporan hardware server">
                <div class="space-y-1">
                    <label for="report-period" class="report-filter-label">Periode Bulan</label>
                    <div class="relative"><select id="report-period" name="period" class="report-filter-input appearance-none pr-8">
                        <?php foreach ($report['periods'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $value === $report['default_period'] ? 'selected' : '' ?>><?= e($label) ?><?= $value === date('Y-m') ? ' (Aktif)' : '' ?></option>
                        <?php endforeach; ?>
                        <option value="all">Semua Arsip</option>
                    </select><?= icon('chevron-down', 'pointer-events-none absolute right-3 top-3 size-3.5 text-slate-400') ?></div>
                </div>
                <div class="space-y-1">
                    <label for="report-rack" class="report-filter-label">Unit Rak Server</label>
                    <div class="relative"><select id="report-rack" name="rack" class="report-filter-input appearance-none pr-8"><option value="all">Semua Rak Server</option><option value="rack-1">Rak Server 1 (SIMRS / DB Utama)</option><option value="rack-2">Rak Server 2 (Switch / Firewall / Core)</option><option value="rack-backup">Rak Server Backup &amp; Storage SAN</option></select><?= icon('chevron-down', 'pointer-events-none absolute right-3 top-3 size-3.5 text-slate-400') ?></div>
                </div>
                <div class="space-y-1">
                    <label for="report-status" class="report-filter-label">Status Kelayakan</label>
                    <div class="relative"><select id="report-status" name="status" class="report-filter-input appearance-none pr-8"><option value="all">Semua Status Kondisi</option><option value="baik">Normal / Baik (Operasional)</option><option value="warning">Perlu Perhatian / Warning</option><option value="danger">Kritis / Trouble Hardware</option></select><?= icon('chevron-down', 'pointer-events-none absolute right-3 top-3 size-3.5 text-slate-400') ?></div>
                </div>
                <div class="space-y-1">
                    <label for="report-search" class="report-filter-label">Cari Catatan / Petugas</label>
                    <div class="relative"><input id="report-search" name="q" type="search" maxlength="150" placeholder="Ketik kata kunci..." class="report-filter-input pl-9"><?= icon('search', 'pointer-events-none absolute left-3 top-3 size-3.5 text-slate-400') ?></div>
                </div>
            </form>
            <?php if ($report['source'] === 'reference'): ?>
            <p class="report-source flex items-center gap-1.5 text-[11px] leading-relaxed text-slate-500"><?= icon('info', 'size-3.5 shrink-0') ?>Data referensi desain. Laporan pemeriksaan belum terhubung ke server.</p>
            <?php endif; ?>
            <p id="report-print-filter" class="hidden text-xs text-slate-600"></p>
            <noscript><p class="rounded-lg bg-amber-50 p-3 text-xs text-amber-900">Aktifkan JavaScript untuk menggunakan filter, pencarian, dan detail laporan.</p></noscript>
        </section>

        <section class="report-table-panel overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-sm" aria-labelledby="report-title">
            <div class="report-table-scroll overflow-x-auto" tabindex="0" role="region" aria-label="Tabel rekap laporan; geser untuk melihat seluruh kolom">
                <table id="hardware-report-table" class="report-table w-full min-w-345 text-left text-xs text-slate-600">
                    <caption class="sr-only">Log pemeriksaan hardware server, kondisi perangkat, catatan, petugas, dan verifikasi</caption>
                    <thead class="bg-slate-50/80 text-[11px] font-bold tracking-wide text-slate-500 uppercase">
                        <tr><th scope="col">Waktu Inspeksi</th><th scope="col">Rak Server</th><th scope="col">Kondisi CPU</th><th scope="col">Monitor Console</th><th scope="col">Parameter Lingkungan &amp; Catatan</th><th scope="col">Petugas</th><th scope="col">Verifikasi</th><th scope="col" class="report-row-actions text-center">Aksi</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($records as $record): ?>
                        <?php
                        $warning = $record['status'] === 'warning';
                        $stamp = strtotime($record['at']);
                        $month = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'][(int) date('n', $stamp) - 1];
                        $rackLabel = match ($record['rack']) { 'rack-1' => 'Rak Server 1', 'rack-2' => 'Rak Server 2', default => 'Rak Backup' };
                        ?>
                        <tr data-report-id="<?= e($record['id']) ?>" class="transition-colors hover:bg-teal-50/40">
                            <td class="whitespace-nowrap"><time datetime="<?= e($record['at']) ?>" class="block font-bold text-slate-900"><?= e(date('d', $stamp) . ' ' . $month . ' ' . date('Y', $stamp)) ?> • <?= e(date('H:i', $stamp)) ?> WIB</time><span class="mt-0.5 block text-[10px] font-medium <?= $warning ? 'text-amber-700' : 'text-slate-500' ?>"><?= e($record['shift']) ?></span></td>
                            <td><span class="inline-flex items-center gap-1 whitespace-nowrap rounded-lg px-2.5 py-1 text-[11px] font-semibold <?= $warning ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-800' ?>"><?= icon('server', 'size-3 text-teal-600') ?><?= e($rackLabel) ?></span></td>
                            <td><span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2.5 py-0.5 text-[11px] font-bold <?= $warning ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' ?>"><span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span><?= e($record['cpu']) ?></span></td>
                            <td><span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700"><?= icon('check-circle', 'size-3') ?><?= e($record['monitor']) ?></span></td>
                            <td class="min-w-72"><span class="block text-[11px] font-semibold <?= $warning ? 'text-amber-800' : 'text-slate-800' ?>"><?= e($record['environment']) ?></span><p class="mt-0.5 max-w-xs truncate text-[11px] text-slate-500" title="<?= e($record['notes']) ?>"><?= e($record['notes']) ?></p></td>
                            <td><span class="flex items-center gap-2 whitespace-nowrap"><span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-teal-800 text-[10px] font-bold text-white"><?= e($record['initials']) ?></span><span><span class="block text-[11px] font-bold text-slate-800"><?= e($record['inspector']) ?></span><span class="block text-[10px] text-slate-500">NIK: <?= e($record['nik']) ?></span></span></span></td>
                            <td><span class="inline-flex items-center gap-1 whitespace-nowrap rounded-md bg-teal-100 px-2 py-0.5 text-[10px] font-bold text-teal-800"><?= icon('shield', 'size-3') ?><?= e($record['verification']) ?></span></td>
                            <td class="report-row-actions text-center"><span class="inline-flex items-center gap-1"><button type="button" data-report-detail="<?= e($record['id']) ?>" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-teal-700" aria-label="Lihat rincian <?= e($record['id']) ?>" aria-haspopup="dialog"><?= icon('eye', 'size-4') ?></button><button type="button" data-report-export="sheet" data-record-id="<?= e($record['id']) ?>" class="rounded-lg p-2 text-teal-700 hover:bg-teal-50" aria-label="Unduh lembar <?= e($record['id']) ?>, belum tersedia" aria-haspopup="dialog"><?= icon('download', 'size-4') ?></button></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr id="report-empty" hidden><td colspan="8" class="py-12 text-center"><p class="font-semibold text-slate-700">Tidak ada laporan yang sesuai.</p><p class="mt-1 text-xs text-slate-500">Ubah periode, rak, status, atau kata kunci pencarian.</p><button type="button" data-report-reset class="mt-4 rounded-lg border border-teal-200 px-3 py-2 text-xs font-semibold text-teal-700 hover:bg-teal-50">Reset Filter</button></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="report-pagination flex flex-col justify-between gap-4 border-t border-slate-100 bg-slate-50/70 px-5 py-4 sm:flex-row sm:items-center">
                <p id="report-result-count" role="status" aria-live="polite" class="text-xs text-slate-500">Menampilkan <?= count($records) ? '1–' . count($records) : '0' ?> dari <?= count($records) ?> data log monitoring</p>
                <nav aria-label="Halaman rekap laporan" class="flex flex-wrap items-center gap-1.5"><button type="button" data-report-step="previous" class="report-page-button" disabled>Sebelumnya</button><span id="report-page-numbers" class="flex items-center gap-1.5"><button type="button" data-report-page="1" aria-current="page" class="report-page-button bg-teal-700 font-semibold text-white">1</button></span><button type="button" data-report-step="next" class="report-page-button" disabled>Selanjutnya</button></nav>
            </div>
        </section>
    </div>
</main>

<script type="application/json" id="hardware-report-data"><?= json_encode(['records' => $records, 'perPage' => $report['per_page']], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<dialog id="report-detail-dialog" class="fixed inset-0 m-auto max-h-[85dvh] w-[calc(100%-2rem)] max-w-xl overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 text-slate-700 shadow-xl" aria-labelledby="report-detail-title">
    <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-semibold text-teal-700">Monitoring Hardware Server</p><h2 id="report-detail-title" class="mt-1 text-lg font-bold text-slate-900">Rincian pemeriksaan</h2><p id="report-detail-id" class="mt-1 text-xs text-slate-500"></p></div><button type="button" data-close-report-detail class="rounded-lg p-2 hover:bg-slate-100" aria-label="Tutup rincian"><?= icon('close', 'size-5') ?></button></div>
    <dl id="report-detail-fields" class="mt-5 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2"></dl>
    <div class="mt-6 flex justify-end"><button type="button" data-close-report-detail class="inspection-button border-slate-300 hover:bg-slate-50">Tutup Rincian</button></div>
</dialog>
