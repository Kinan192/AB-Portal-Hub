<main id="main-content" class="flex-1 bg-slate-100">
    <section class="relative bg-[#0a382c] pb-9 pt-8 text-white" aria-labelledby="inspection-title">
        <div class="inspection-shell relative flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
            <div class="min-w-0">
                <p class="mb-3 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold text-teal-200"><?= icon('server', 'size-3.5') ?>Checklist Inspeksi Ruang Server • Unit <?= e($inspection['unit']) ?></p>
                <h1 id="inspection-title" class="font-serif text-2xl font-bold tracking-tight lg:text-3xl">Form Monitoring Hardware Server IT</h1>
                <p class="mt-1 max-w-2xl text-sm leading-relaxed text-teal-100/80">Pencatatan kondisi fisik dan operasional hardware server, rak, konsol CPU, display central, serta perangkat pendukung IT secara harian dan berkala.</p>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-3">
                <div class="flex min-h-12 items-center gap-3 rounded-xl border border-white/20 bg-white/10 px-4 py-2">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-teal-400/30 bg-teal-500/20 text-teal-300"><?= icon('calendar', 'size-4') ?></span>
                    <span class="flex flex-col"><span class="text-[10px] font-bold tracking-wide text-teal-200/80 uppercase">Periode Laporan</span><span class="text-xs font-bold"><?= e($inspection['period']) ?></span></span>
                </div>
                <button type="button" data-new-inspection class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-emerald-500 px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-emerald-600"><?= icon('check-circle', 'size-4') ?>Input Form Baru</button>
            </div>
        </div>
    </section>

    <div class="inspection-shell relative -mt-4 pb-16">
        <form id="hardware-monitoring-form" method="post" class="space-y-6" data-inspector="<?= e($profile['name']) ?>">
            <input type="hidden" name="recorded_at" value="<?= e($recordedAt) ?>">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center gap-4">
                    <dl class="grid w-full flex-1 grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="inspection-metadata">
                            <span class="inspection-metadata-icon border-teal-100 bg-teal-50 text-teal-700"><?= icon('clipboard', 'size-4') ?></span>
                            <div class="min-w-0"><dt>Petugas Pemeriksa:</dt><dd><?= e($profile['name']) ?></dd></div>
                        </div>
                        <div class="inspection-metadata">
                            <span class="inspection-metadata-icon border-slate-200 bg-slate-100 text-rose-500"><?= icon('location', 'size-4') ?></span>
                            <div class="min-w-0"><dt>Lokasi Ruang Server:</dt><dd><?= e($inspection['location']) ?></dd></div>
                        </div>
                        <div class="inspection-metadata">
                            <span class="inspection-metadata-icon border-slate-200 bg-slate-100 text-teal-600"><?= icon('clock', 'size-4') ?></span>
                            <div class="min-w-0"><dt>Waktu Pencatatan:</dt><dd><time id="inspection-timestamp" datetime="<?= e($recordedAt) ?>"><?= e(date('d/m/Y, H:i')) ?> WIB</time></dd></div>
                        </div>
                    </dl>
                    <p class="inline-flex min-h-10 items-center gap-1.5 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800"><?= icon('warning', 'size-4 shrink-0 text-amber-500') ?>Wajib Diisi Lengkap</p>
                </div>
            </div>
            <p id="inspection-feedback" role="status" aria-live="polite" class="hidden rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800"></p>
            <noscript><p class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Aktifkan JavaScript untuk menggunakan draft, catatan cepat, dan pemeriksaan form.</p></noscript>

            <fieldset class="inspection-panel" aria-describedby="rack-help">
                <legend class="sr-only">Pilih rak server, wajib diisi</legend>
                <div class="inspection-section-heading">
                    <span class="inspection-step">1</span>
                    <div class="min-w-0 flex-1"><h2 class="text-base font-bold tracking-tight text-slate-900">PILIH RAK SERVER <span class="ml-1 text-rose-500" aria-hidden="true">*</span></h2><p id="rack-help" class="mt-0.5 text-xs text-slate-500">Pilih rack enclosure server yang sedang diinspeksi</p></div>
                    <span class="inspection-section-tag">Single Selection</span>
                </div>
                <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <?php foreach ($inspection['racks'] as $index => $rack): ?>
                    <div class="inspection-choice">
                        <input type="radio" class="inspection-radio sr-only" id="rack-<?= e($index) ?>" name="rak_server" value="<?= e($rack['value']) ?>" required <?= $index === 0 ? 'checked' : '' ?>>
                        <label for="rack-<?= e($index) ?>" class="flex h-full cursor-pointer flex-col justify-between p-5">
                            <span class="flex items-center gap-3.5">
                                <span class="inspection-radio-indicator" aria-hidden="true"></span>
                                <span class="min-w-0 flex-1"><span class="block text-sm font-bold text-slate-800"><?= e($rack['value']) ?></span><span class="mt-0.5 block text-xs leading-relaxed text-slate-500"><?= e($rack['description']) ?></span></span>
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-teal-600"><?= icon($rack['icon'], 'size-4') ?></span>
                            </span>
                            <span class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-slate-200/60 pt-3 text-[11px] font-medium text-slate-500"><span class="inline-flex items-center gap-1.5"><?= icon('cube', 'size-3.5 text-slate-400') ?>42U Enclosure</span><span class="inline-flex items-center gap-1.5 text-emerald-700"><?= icon('check-circle', 'size-3.5') ?><?= e($rack['detail']) ?></span></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <?php foreach ($inspection['conditions'] as $condition): ?>
                <?php require __DIR__ . '/condition.php'; ?>
            <?php endforeach; ?>

            <section class="inspection-panel" aria-labelledby="hardware-notes-title">
                <div class="inspection-section-heading">
                    <span class="inspection-step">4</span>
                    <div class="min-w-0 flex-1"><h2 id="hardware-notes-title" class="text-base font-bold tracking-tight text-slate-900">KONDISI HARDWARE DAN LAIN-LAIN</h2><p id="hardware-notes-help" class="mt-0.5 text-xs text-slate-500">Catatan tambahan perangkat keras: UPS, AC Server, Kerapian Kabel (Patch Cord), PDU, Switch, dsb.</p></div>
                    <span class="inspection-section-tag">Catatan / Detail</span>
                </div>
                <div class="mt-5 flex flex-wrap items-center gap-2">
                    <span class="mr-1 text-xs font-semibold text-slate-500">Rekomendasi Cepat:</span>
                    <?php foreach ($inspection['quick_notes'] as $note): ?>
                    <button type="button" data-quick-note="<?= e($note['text']) ?>" class="rounded-full border border-slate-200 bg-slate-100 px-3 py-1.5 text-[11px] text-slate-600 transition-colors hover:border-teal-200 hover:bg-teal-50 hover:text-teal-700">+ <?= e($note['label']) ?></button>
                    <?php endforeach; ?>
                </div>
                <label for="hardware-notes" class="sr-only">Catatan kondisi hardware dan lain-lain</label>
                <textarea id="hardware-notes" name="catatan_hardware" rows="4" maxlength="3000" aria-describedby="hardware-notes-help" class="inspection-input mt-4 min-h-28 resize-y bg-slate-50/50 p-4 text-sm" placeholder="Tuliskan catatan kondisi hardware lainnya secara terperinci (misal: kondisi UPS, temperatur ruang AC server, kebersihan rak, perangkat storage)..."></textarea>
            </section>

            <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:grid lg:grid-cols-[1.2fr_0.55fr_1.4fr_0.8fr_1.4fr] lg:gap-3">
                <div class="flex flex-wrap items-center gap-3 lg:contents">
                    <a href="/" class="inspection-button border-slate-300 text-slate-700 hover:bg-slate-100"><?= icon('arrow-left', 'size-4 shrink-0') ?><span>Kembali ke Portal Terpadu</span></a>
                    <button type="reset" class="min-h-11 rounded-lg px-3 py-2.5 text-xs font-semibold text-slate-500 hover:bg-slate-50 hover:text-slate-800">Reset Form</button>
                    <button type="button" data-inspection-history class="inspection-button border-teal-200 bg-teal-50 text-teal-700 hover:bg-teal-100" aria-haspopup="dialog"><?= icon('file', 'size-4 shrink-0') ?><span>Lihat Riwayat &amp; Rekap Laporan</span></button>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row lg:contents">
                    <button type="button" data-save-inspection class="inspection-button border-teal-600 text-teal-700 hover:bg-teal-50"><?= icon('bookmark', 'size-4 shrink-0') ?>Simpan Draft</button>
                    <button type="submit" class="inspection-button border-teal-700 bg-teal-700 text-white shadow-sm hover:bg-teal-800" aria-haspopup="dialog"><?= icon('send', 'size-4 shrink-0') ?>Kirim Laporan Monitoring</button>
                </div>
            </div>
        </form>
    </div>
</main>

<dialog id="inspection-review" class="fixed inset-0 m-auto max-h-[85dvh] w-[calc(100%-2rem)] max-w-lg overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 text-slate-700 shadow-xl" aria-labelledby="inspection-review-title" aria-describedby="inspection-review-help">
    <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-semibold text-teal-700">Pemeriksaan Hardware Server</p><h2 id="inspection-review-title" class="mt-1 text-lg font-bold text-slate-900">Ringkasan laporan</h2></div><button type="button" data-close-inspection-review class="rounded-lg p-2 hover:bg-slate-100" aria-label="Tutup ringkasan"><?= icon('close', 'size-5') ?></button></div>
    <dl id="inspection-review-details" class="my-5 space-y-3 text-sm"></dl>
    <p id="inspection-review-help" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-relaxed text-amber-900">Pengiriman laporan ke server belum tersedia. Data belum dikirim. Simpan draft di perangkat ini untuk melanjutkan pengisian nanti.</p>
    <div class="mt-5 flex flex-wrap justify-end gap-3"><button type="button" data-close-inspection-review class="inspection-button border-slate-300 hover:bg-slate-50">Kembali ke Form</button><button type="button" data-save-inspection class="inspection-button border-teal-700 bg-teal-700 text-white hover:bg-teal-800">Simpan Draft</button></div>
</dialog>
