<section class="inspection-hero relative bg-[#0a382c] pb-9 pt-8 text-white" aria-labelledby="inspection-title">
    <div class="page-shell relative flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
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
            <?php if (($hardwarePage ?? '') === 'history'): ?>
            <a href="/forms/hardware-server" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-emerald-500 px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-emerald-600"><?= icon('check-circle', 'size-4') ?>Input Form Baru</a>
            <?php else: ?>
            <button type="button" data-new-inspection class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-emerald-500 px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-emerald-600"><?= icon('check-circle', 'size-4') ?>Input Form Baru</button>
            <?php endif; ?>
        </div>
    </div>
</section>
