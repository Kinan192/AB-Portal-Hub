<header class="sticky top-0 z-40 border-b border-slate-200 bg-white">
    <div class="page-shell flex flex-wrap items-center justify-between gap-x-5 gap-y-3 py-3">
        <a href="/" class="flex flex-wrap items-center gap-4" aria-label="Beranda Portal RS Awal Bros Botania">
            <?php require BASE_PATH . '/app/Views/components/brand.php'; ?>
            <span class="flex flex-col border-l border-slate-200 pl-4">
                <span class="text-[11.7px] font-bold text-slate-700">Portal Monitoring IT</span>
                <span class="text-[10.8px] text-slate-500">RS Awal Bros Group • IT Division</span>
            </span>
        </a>
        <nav aria-label="Breadcrumb" class="order-last w-full border-t border-slate-100 pt-2 text-[11px] 2xl:order-none 2xl:w-auto 2xl:border-0 2xl:pt-0">
            <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <li><a href="/" class="inline-flex items-center gap-1 text-slate-500 hover:text-teal-700"><?= icon('home', 'size-3.5') ?>Dashboard</a></li>
                <li aria-hidden="true" class="text-slate-300">›</li>
                <li><a href="/#section-daily" class="text-slate-500 hover:text-teal-700">Monitoring Harian</a></li>
                <li aria-hidden="true" class="text-slate-300">›</li>
                <li aria-current="page" class="rounded-full border border-teal-200/60 bg-teal-50 px-2.5 py-1 font-medium text-teal-700">Hardware Server IT</li>
            </ol>
        </nav>
        <div class="flex flex-wrap items-center gap-3">
            <time id="portal-clock" datetime="<?= e(date(DATE_ATOM)) ?>" class="hidden items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-[11.7px] text-slate-600 lg:inline-flex">
                <?= icon('clock', 'size-3.5') ?><span data-clock-text><?= e(date('d/m/Y H:i:s')) ?> WIB</span>
            </time>
            <span class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-[11px] font-semibold text-emerald-700" title="Status halaman portal"><span class="size-2 rounded-full bg-emerald-400" aria-hidden="true"></span>Portal aktif</span>
            <div class="flex items-center gap-2">
                <span class="flex size-8 items-center justify-center rounded-lg bg-brand-dark text-xs font-bold text-white"><?= e($profile['initials']) ?></span>
                <span class="flex flex-col"><span class="text-xs font-bold text-slate-800"><?= e($profile['name']) ?></span><span class="text-[10px] text-teal-700"><?= e($profile['subtitle']) ?></span></span>
            </div>
            <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-rose-700" data-account="logout" aria-label="Logout, belum tersedia" aria-haspopup="dialog"><?= icon('logout', 'size-4') ?></button>
        </div>
    </div>
</header>
