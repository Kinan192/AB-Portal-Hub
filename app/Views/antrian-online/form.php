<?php
/** @var array{name: string, initials: string, subtitle: string} $profile */
/** @var array{crons: list<array{key: string, title: string, description: string, good: string, bad: string}>} $monitoring */
/** @var string $recordedAt */
?>
<main id="main-content" class="queue-page page-shell flex-1 space-y-6 pb-4">

    <section class="module-hero" aria-labelledby="queue-title"><div class="flex flex-col justify-between gap-6 xl:flex-row xl:items-center">
        <div class="min-w-0 max-w-3xl space-y-2"><p class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 font-mono text-xs font-semibold tracking-wide uppercase"><span class="size-2 rounded-full bg-emerald-300" aria-hidden="true"></span>RS AWAL BROS GROUP • IT BOTANIA</p><h1 id="queue-title" class="text-2xl font-bold tracking-tight sm:text-3xl">Form Monitoring Antrian Online BPJS</h1><p class="max-w-2xl text-sm leading-relaxed text-white/90">Pencatatan harian dan verifikasi sinkronisasi pengiriman data cron antrian online serta cron ketersediaan bed per jam MJKN Mobile JKN BPJS Kesehatan.</p></div>
        <div class="flex shrink-0 flex-wrap items-center gap-3"><div class="flex min-h-12 items-center gap-3 rounded-lg bg-white/10 px-4 py-3"><?= icon('calendar', 'size-6 shrink-0 text-emerald-200') ?><span><span class="block font-mono text-xs font-semibold tracking-wide text-white/70 uppercase">Periode Laporan</span><span id="queue-period" class="block text-sm font-bold">Inspeksi Harian</span></span></div><a href="/reports/antrian-online" class="button-soft module-action inline-flex min-h-12 items-center justify-center gap-2 rounded-lg px-4 py-3 text-xs font-semibold border"><?= icon('file', 'size-4') ?>Lihat Rekap &amp; Unduh Laporan</a></div>
    </div></section>

    <section class="min-w-0 rounded-xl border border-slate-200/60 bg-white p-5 shadow-sm sm:p-8" aria-labelledby="queue-form-title">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3"><div><h2 id="queue-form-title" class="text-xl font-bold tracking-tight text-slate-800">Data Verifikasi Antrian Online BPJS</h2><p class="mt-1 text-xs leading-relaxed text-emerald-800">Formulir Log Operasional Bridging SIMRS &amp; BPJS • Monitoring sinkronisasi harian</p></div><span class="rounded bg-emerald-100 px-2.5 py-1 font-mono text-xs font-semibold text-emerald-800">CRON AUDIT</span></div>
        <p id="queue-feedback" role="status" aria-live="polite" class="mb-5 hidden rounded-lg border border-teal-200 bg-teal-50 p-3 text-xs leading-relaxed text-teal-800"></p>
        <form id="queue-form" method="post" class="space-y-6" data-inspector="<?= e($profile['name']) ?>">
            <input type="hidden" name="recorded_at" value="<?= e($recordedAt) ?>">
            <div class="grid grid-cols-1 items-center gap-4 rounded-lg bg-slate-50 p-4 md:grid-cols-2">
                <div class="flex min-w-0 items-center gap-3"><span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-emerald-800 text-white"><?= icon('clipboard', 'size-4') ?></span><span class="min-w-0"><span class="queue-meta-label">Akun Verifikator IT</span><span class="block text-xs font-semibold text-slate-800"><?= e($profile['name']) ?></span><span class="block text-xs text-slate-500"><?= e($profile['subtitle']) ?></span></span></div>
                <div class="flex min-w-0 items-center gap-3"><?= icon('clock', 'size-5 shrink-0 text-slate-500') ?><span><span class="queue-meta-label">Waktu &amp; Tanggal</span><time id="queue-recorded-time" datetime="<?= e($recordedAt) ?>" class="block font-mono text-xs font-medium text-slate-800"><?= e(date('d/m/Y H:i')) ?> WIB</time></span></div>

            </div>

            <div class="queue-paired-grid">
                <?php foreach ($monitoring['crons'] as $index => $cron): ?>
                <section class="queue-field-group rounded-xl bg-slate-50 p-4" aria-labelledby="<?= e($cron['key']) ?>-title"><div class="flex items-start justify-between gap-3"><h3 id="<?= e($cron['key']) ?>-title" class="flex items-start gap-2 text-sm font-bold text-slate-800"><span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-red-700 text-xs text-white"><?= e($index + 1) ?></span><?= e($cron['title']) ?></h3><span class="shrink-0 rounded bg-red-100 px-2 py-1 font-mono text-xs font-bold text-red-700">WAJIB DIISI</span></div><p id="<?= e($cron['key']) ?>-help" class="text-xs leading-relaxed text-slate-500"><?= e($cron['description']) ?></p>
                    <fieldset class="grid grid-cols-1 gap-3 sm:grid-cols-2" aria-describedby="<?= e($cron['key']) ?>-help"><legend class="sr-only"><?= e($cron['title']) ?>, wajib diisi</legend>
                        <?php foreach (['TERKIRIM' => true, 'TIDAK_TERKIRIM' => false] as $value => $good): ?>
                        <?php $optionId = $cron['key'] . ($good ? '-good' : '-bad'); $optionClass = $good ? 'queue-choice-good' : 'queue-choice-bad'; $textClass = $good ? 'text-slate-800' : 'text-red-700'; $badgeClass = $good ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'; ?>
                        <div class="queue-choice <?= e($optionClass) ?>"><input id="<?= e($optionId) ?>" type="radio" class="queue-radio sr-only" name="<?= e($cron['key']) ?>" value="<?= e($value) ?>" required <?= $good ? 'checked' : '' ?>><label for="<?= e($optionId) ?>" class="flex h-full cursor-pointer items-start gap-2 p-3"><span class="queue-choice-dot mt-1" aria-hidden="true"></span><span class="min-w-0"><span class="flex flex-wrap items-center gap-1.5"><span class="text-sm font-bold <?= e($textClass) ?>"><?= $good ? 'TERKIRIM' : 'TIDAK TERKIRIM' ?></span><span class="rounded-full px-2 py-0.5 font-mono text-xs font-bold <?= e($badgeClass) ?>"><?= $good ? 'NORMAL' : 'CRITICAL' ?></span></span><span class="mt-1 block text-xs leading-relaxed text-slate-500"><?= e($cron[$good ? 'good' : 'bad']) ?></span></span></label></div>
                        <?php endforeach; ?>
                    </fieldset>
                </section>
                <?php endforeach; ?>
            </div>

            <div class="space-y-4">
                <div>
                    <label for="queue-notes" class="ui-field-label mb-2 block text-slate-800">CATATAN KENDALA / TINDAK LANJUT IT <span id="queue-notes-required" hidden class="text-red-600" aria-hidden="true">*</span></label>
                    <textarea id="queue-notes" name="notes" rows="4" maxlength="3000" aria-describedby="queue-notes-help" class="inspection-input min-h-28 resize-y bg-slate-50 p-4" placeholder="Tuliskan kendala cron dan tindak lanjut yang dilakukan..."></textarea>
                    <p id="queue-notes-help" class="ui-caption mt-1 text-slate-500">Isi catatan bila ada cron yang tidak terkirim.</p>
                </div>
                <div class="flex items-start gap-2 rounded-lg bg-slate-50 p-4"><input id="queue-confirm" name="confirmed" type="checkbox" required class="mt-0.5 size-4 shrink-0 accent-emerald-700"><label for="queue-confirm" class="cursor-pointer text-xs leading-relaxed text-slate-700">Saya menyatakan bahwa pemeriksaan sinkronisasi Antrian Online BPJS ini telah diverifikasi sesuai kondisi log server riil RS Awal Bros Botania.</label></div>
            </div>
            <div class="flex flex-col justify-between gap-4 border-t border-slate-100 pt-5 lg:flex-row lg:flex-wrap lg:items-center"><div class="flex flex-wrap gap-2"><a href="/" class="button-neutral module-action inspection-button border"><?= icon('arrow-left', 'size-4') ?>Kembali ke Portal Terpadu</a><a href="/reports/antrian-online" class="button-soft module-action inspection-button border"><?= icon('history', 'size-4') ?>Lihat Riwayat &amp; Rekap</a></div><div class="flex flex-wrap justify-end gap-2"><button type="reset" class="button-ghost module-action inspection-button"><?= icon('reset', 'size-4') ?>Clear form</button><button type="button" data-queue-save class="button-secondary module-action inspection-button border"><?= icon('bookmark', 'size-4') ?>Simpan Draft</button><button type="submit" class="button-primary module-action inspection-button" aria-haspopup="dialog"><?= icon('send', 'size-4') ?>Kirim Laporan Antrian</button></div></div>
            <noscript><p class="rounded-lg bg-amber-50 p-3 text-xs text-amber-900">Aktifkan JavaScript untuk validasi, draft, dan ringkasan laporan.</p></noscript>
        </form>
    </section>
</main>

<dialog id="queue-review" class="fixed inset-0 m-auto max-h-[85dvh] w-[calc(100%-2rem)] max-w-lg overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 text-slate-700 shadow-xl" aria-labelledby="queue-review-title" aria-describedby="queue-review-help"><div class="flex items-start justify-between gap-3"><div><p class="text-xs font-semibold text-emerald-700">Monitoring Antrian Online BPJS</p><h2 id="queue-review-title" class="mt-1 text-lg font-bold text-slate-900">Ringkasan Laporan Antrian</h2></div><button type="button" data-queue-close class="button-ghost module-action-small rounded-lg p-2" aria-label="Tutup ringkasan"><?= icon('close', 'size-5') ?></button></div><dl id="queue-review-fields" class="my-5 space-y-3 text-sm"></dl><p id="queue-review-help" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-relaxed text-amber-900">Pengiriman laporan ke server belum tersedia. Data belum dikirim. Draft menyimpan isian di browser perangkat ini.</p><div class="mt-5 flex flex-wrap justify-end gap-3"><button type="button" data-queue-close class="button-neutral module-action inspection-button border">Kembali ke Form</button><button type="button" data-queue-save class="button-secondary module-action inspection-button border">Simpan Draft</button></div></dialog>
