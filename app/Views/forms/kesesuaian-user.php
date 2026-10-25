<?php
/** @var array{name: string, initials: string, subtitle: string} $profile */
/** @var array{reasons: array<string, string>, sample_source: string, recent_samples: list<array{username: string, unit: string, time: string, compliant: bool}>} $verification */
/** @var string $loginDate */
/** @var string $loginTime */
?>
<main id="main-content" class="page-shell flex-1 pb-4">
    <section class="hero-pattern mb-6 rounded-xl border border-teal-800 bg-brand-dark px-6 py-6 text-white shadow-sm sm:px-8" aria-labelledby="compliance-title">
        <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
            <div class="min-w-0 space-y-2">
                <p class="inline-flex items-center gap-2 rounded bg-emerald-950/60 px-2.5 py-1 text-[11px] font-bold tracking-wide text-emerald-200 uppercase"><span class="size-1.5 rounded-full bg-emerald-400" aria-hidden="true"></span>RS AWAL BROS GROUP • IT BOTANIA</p>
                <h1 id="compliance-title" class="text-2xl font-extrabold tracking-tight sm:text-3xl">Form Kesesuaian User (HIS)</h1>
                <p class="max-w-2xl text-sm leading-relaxed text-emerald-100/90">Verifikasi sampel kesesuaian hak akses data sesuai kondisi lapangan di RS Awal Bros Botania.</p>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-3">
                <div class="flex min-h-12 items-center gap-3 rounded-xl border border-white/10 bg-black/20 px-4 py-2">
                    <?= icon('calendar', 'size-5 shrink-0 text-emerald-300') ?><span><span class="block text-[10px] font-bold tracking-wide text-emerald-200/80 uppercase">Periode Verifikasi</span><time id="compliance-period" datetime="<?= e($loginDate) ?>" class="block text-xs font-bold"><?= e(date_wib()) ?></time></span>
                </div>
                <a href="/reports/kesesuaian-user" class="button-on-dark inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border px-4 py-2 text-xs font-semibold"><?= icon('download', 'size-4 shrink-0') ?>Lihat Rekap &amp; Unduh Laporan</a>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
        <section class="min-w-0 rounded-xl border border-slate-200/60 bg-white p-6 shadow-sm sm:p-8 lg:col-span-8" aria-labelledby="compliance-form-title">
            <div class="mb-6 pb-5">
                <div class="mb-2 flex items-start justify-between gap-3">
                    <h2 id="compliance-form-title" class="flex items-start gap-2 text-lg font-bold tracking-tight text-slate-800"><?= icon('user-search', 'mt-0.5 size-5 shrink-0 text-emerald-700') ?><span>Masukkan username yang dilakukan sampel penyesuaian login</span></h2>
                    <span class="shrink-0 rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-semibold text-rose-600">* Wajib Diisi</span>
                </div>
                <p class="text-xs text-slate-500">Leveling User Account sesuai regulasi SPO RS Awal Bros Botania.</p>
            </div>
            <p id="compliance-feedback" role="status" aria-live="polite" class="mb-5 hidden rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-xs leading-relaxed text-emerald-800"></p>
            <form id="compliance-form" method="post" class="space-y-6" data-inspector="<?= e($profile['name']) ?>">
                <div class="space-y-1.5">
                    <div class="flex flex-wrap items-center justify-between gap-2"><label for="compliance-username" class="text-sm font-bold text-slate-800">1. Masukkan username HIS di sini <span class="text-rose-600" aria-hidden="true">*</span></label><span class="text-[11px] text-slate-400">Database ID / Akun SIMRS</span></div>
                    <p id="compliance-username-help" class="text-xs leading-relaxed text-slate-500">Ketik username akun SIMRS / HIS yang sedang diverifikasi sampelnya saat login sesi bertugas.</p>
                    <div class="relative"><?= icon('clipboard', 'pointer-events-none absolute left-3.5 top-4 size-4 text-slate-400') ?><input id="compliance-username" name="username" type="text" required maxlength="100" autocomplete="off" autocapitalize="none" spellcheck="false" aria-describedby="compliance-username-help" placeholder="Contoh: dr.anita / nur.siti / adm.budi" class="inspection-input h-12 bg-slate-50/70 pl-10 shadow-sm"></div>
                </div>

                <fieldset class="min-w-0 space-y-2 pt-1" aria-describedby="compliance-status-help">
                    <legend class="sr-only">Kesesuaian username dengan regulasi, wajib diisi</legend>
                    <div class="flex flex-wrap items-center justify-between gap-2"><h3 class="text-sm font-bold text-slate-800">2. Username sesuai regulasi? <span class="text-rose-600" aria-hidden="true">*</span></h3><span class="text-[11px] text-slate-400">Validasi Otorisasi</span></div>
                    <p id="compliance-status-help" class="text-xs leading-relaxed text-slate-500">Apakah data kredensial, peran modul, dan hak akses user sesuai dengan wewenang unit kerja aktif?</p>
                    <div class="grid grid-cols-1 gap-4 pt-1 sm:grid-cols-2">
                        <div class="compliance-option"><input id="compliance-good" type="radio" class="compliance-radio sr-only" name="status" value="sesuai" required checked><label for="compliance-good" class="flex min-h-20 cursor-pointer items-center gap-3 p-4"><span class="compliance-option-icon" aria-hidden="true"><?= icon('check-circle', 'size-5') ?></span><span class="min-w-0 flex-1"><span class="flex flex-wrap items-center justify-between gap-2"><span class="text-sm font-bold text-slate-800">Sesuai Regulasi</span><span class="rounded bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">Sesuai Regulasi</span></span><span class="mt-1 block text-[11px] leading-relaxed text-slate-500">Hak akses modul tepat sesuai unit &amp; jabatan dinas.</span></span></label></div>
                        <div class="compliance-option compliance-option-bad"><input id="compliance-bad" type="radio" class="compliance-radio sr-only" name="status" value="tidak_sesuai" required><label for="compliance-bad" class="flex min-h-20 cursor-pointer items-center gap-3 p-4"><span class="compliance-option-icon" aria-hidden="true"><?= icon('close', 'size-5') ?></span><span class="min-w-0 flex-1"><span class="flex flex-wrap items-center justify-between gap-2"><span class="text-sm font-bold text-slate-800">Tidak Sesuai Regulasi</span><span class="rounded bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-800">Tidak Sesuai Regulasi</span></span><span class="mt-1 block text-[11px] leading-relaxed text-slate-500">Ditemukan anomali modul, mutasi belum diperbarui, dll.</span></span></label></div>
                    </div>
                    <div id="compliance-reason-panel" hidden class="space-y-2 rounded-lg border border-rose-100 bg-rose-50/60 p-4">
                        <label for="compliance-reason" class="flex items-center gap-1 text-xs font-bold text-rose-900"><?= icon('warning', 'size-4 text-rose-600') ?>Alasan / Catatan Ketidaksesuaian <span aria-hidden="true">*</span></label>
                        <select id="compliance-reason" name="reason" class="inspection-input" disabled><?php foreach ($verification['reasons'] as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?></select>
                        <label for="compliance-notes" class="block text-xs font-medium text-rose-900">Catatan spesifik tindak lanjut <span id="compliance-notes-required" hidden aria-hidden="true">*</span></label>
                        <textarea id="compliance-notes" name="notes" rows="2" maxlength="1000" class="inspection-input resize-y" placeholder="Catatan spesifik tindak lanjut..." disabled></textarea>
                    </div>
                </fieldset>

                <div class="space-y-1.5 pt-1">
                    <div class="flex flex-wrap items-center justify-between gap-2"><h3 class="text-sm font-bold text-slate-800">3. Waktu login <span class="text-rose-600" aria-hidden="true">*</span></h3><button type="button" data-compliance-now class="button-soft inline-flex min-h-8 items-center gap-1 rounded px-2 py-1 text-[11px] font-semibold"><?= icon('clock', 'size-3') ?>Set Jam Sekarang</button></div>
                    <p class="text-xs leading-relaxed text-slate-500">Waktu saat user melakukan autentikasi login pada sistem HIS (format 24 jam, WIB).</p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="relative"><label for="compliance-date" class="sr-only">Tanggal login, wajib diisi</label><?= icon('calendar', 'pointer-events-none absolute left-3.5 top-4 size-4 text-slate-400') ?><input id="compliance-date" name="login_date" type="date" required value="<?= e($loginDate) ?>" class="inspection-input h-12 bg-slate-50/70 pl-10 shadow-sm"></div>
                        <div class="relative"><label for="compliance-time" class="sr-only">Jam login WIB, wajib diisi</label><?= icon('clock', 'pointer-events-none absolute left-3.5 top-4 size-4 text-slate-400') ?><input id="compliance-time" name="login_time" type="time" required step="60" value="<?= e($loginTime) ?>" class="inspection-input h-12 bg-slate-50/70 pl-10 shadow-sm"></div>
                    </div>
                </div>
                <div class="flex items-start gap-2.5 rounded-lg bg-slate-50 p-3.5"><input id="compliance-confirm" name="confirmed" type="checkbox" required class="mt-0.5 size-4 shrink-0 accent-emerald-600"><label for="compliance-confirm" class="cursor-pointer text-xs leading-relaxed text-slate-600">Saya mengonfirmasi bahwa pengecekan sampel kesesuaian hak akses ini telah dilakukan secara valid sesuai regulasi melalui pengecekan terminal log / active session HIS.</label></div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-6">
                    <a href="/" class="button-neutral inspection-button"><?= icon('arrow-left', 'size-4') ?>Kembali ke Portal Terpadu</a>
                    <div class="flex flex-wrap gap-3"><a href="/reports/kesesuaian-user" class="button-soft inspection-button"><?= icon('table', 'size-4') ?>Lihat Riwayat &amp; Rekap Laporan</a><button type="button" data-compliance-save class="button-secondary inspection-button"><?= icon('bookmark', 'size-4') ?>Simpan Draft</button><button type="submit" class="button-primary inspection-button" aria-haspopup="dialog"><?= icon('send', 'size-4') ?>Kirim Verifikasi</button></div>
                </div>
                <noscript><p class="rounded-lg bg-amber-50 p-3 text-xs text-amber-900">Aktifkan JavaScript untuk menggunakan draft, catatan ketidaksesuaian, dan validasi form.</p></noscript>
            </form>
        </section>

        <aside class="min-w-0 rounded-xl border border-slate-200/60 bg-white p-4 shadow-sm lg:col-span-4" aria-labelledby="compliance-recent-title">
            <div class="mb-3 flex items-center justify-between gap-3"><h2 id="compliance-recent-title" class="flex items-center gap-1.5 text-xs font-bold text-slate-800"><?= icon('history', 'size-4 shrink-0 text-emerald-600') ?>Sampel Terakhir Tercatat</h2><span class="shrink-0 rounded bg-emerald-50 px-2 py-1 text-[10px] font-bold text-emerald-700"><?= count($verification['recent_samples']) ?> sampel</span></div>
            <ul class="space-y-2">
                <?php foreach ($verification['recent_samples'] as $sample): ?>
                <?php $sampleDot = $sample['compliant'] ? 'bg-emerald-500' : 'bg-rose-500'; $sampleBadge = $sample['compliant'] ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'; ?>
                <li class="flex flex-wrap items-center justify-between gap-2 rounded bg-slate-50 p-2.5"><span class="flex min-w-0 items-center gap-2"><span class="size-2 shrink-0 rounded-full <?= e($sampleDot) ?>" aria-hidden="true"></span><span class="min-w-0"><span class="block break-all text-xs font-bold text-slate-800"><?= e($sample['username']) ?></span><span class="block text-[10px] leading-relaxed text-slate-500"><?= e($sample['unit']) ?> • Login <?= e($sample['time']) ?> WIB</span></span></span><span class="rounded px-1.5 py-1 text-[10px] font-bold <?= e($sampleBadge) ?>"><?= $sample['compliant'] ? 'Sesuai Regulasi' : 'Tidak Sesuai Regulasi' ?></span></li>
                <?php endforeach; ?>
            </ul>
            <?php if ($verification['sample_source'] === 'reference'): ?><p class="mt-3 text-[10px] leading-relaxed text-slate-500">Data referensi desain. Sampel operasional belum terhubung ke server.</p><?php endif; ?>
        </aside>
    </div>
</main>

<dialog id="compliance-review" class="fixed inset-0 m-auto max-h-[85dvh] w-[calc(100%-2rem)] max-w-lg overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 text-slate-700 shadow-xl" aria-labelledby="compliance-review-title" aria-describedby="compliance-review-help">
    <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-semibold text-emerald-700">Kesesuaian User HIS</p><h2 id="compliance-review-title" class="mt-1 text-lg font-bold text-slate-900">Ringkasan Verifikasi</h2></div><button type="button" data-compliance-close class="button-ghost rounded-lg p-2" aria-label="Tutup ringkasan"><?= icon('close', 'size-5') ?></button></div>
    <dl id="compliance-review-fields" class="my-5 space-y-3 text-sm"></dl>
    <p id="compliance-review-help" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-relaxed text-amber-900">Pengiriman verifikasi ke server belum tersedia. Data belum dikirim. Simpan draft untuk melanjutkan pengisian nanti.</p>
    <div class="mt-5 flex flex-wrap justify-end gap-3"><button type="button" data-compliance-close class="button-neutral inspection-button">Kembali ke Form</button><button type="button" data-compliance-save class="button-secondary inspection-button">Simpan Draft</button></div>
</dialog>
