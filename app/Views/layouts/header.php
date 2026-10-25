<?php
/** @var string|null $navbarVariant */
if (($navbarVariant ?? '') === 'form') {
    require __DIR__ . '/header-form.php';
    return;
}
?>
<header class="sticky top-0 z-40 border-b border-slate-200 bg-white">
    <div class="portal-header page-shell flex flex-wrap items-center justify-between gap-4 py-3">
        <a href="/" class="flex min-w-0 flex-wrap items-center gap-4" aria-label="Beranda Portal RS Awal Bros Botania">
            <?php require BASE_PATH . '/app/Views/components/brand.php'; ?>
            <span class="flex flex-col border-l border-slate-300 pl-4">
                <span class="text-[11.7px] font-bold leading-tight text-slate-800">Portal Monitoring IT</span>
                <span class="text-[10.8px] font-medium text-slate-500">IT Division</span>
            </span>
        </a>
        <div class="flex w-full flex-wrap items-center gap-2.5 sm:w-auto">
            <time id="portal-clock" class="inline-flex items-center rounded border border-slate-200 bg-slate-100/90 px-3 py-1.5 text-[11.7px] font-medium text-slate-600" datetime="<?= e(date(DATE_ATOM)) ?>">
                <span data-clock-text><?= e(date('d/m/Y H:i:s')) ?> WIB</span>
            </time>
            <span class="inline-flex items-center gap-1.5 rounded border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-[11.7px] font-semibold text-emerald-800" title="Status halaman portal, bukan status layanan eksternal">
                <span class="size-2 rounded-full bg-emerald-500" aria-hidden="true"></span>Portal aktif
            </span>
            <div class="flex items-center gap-2 rounded border border-slate-200 bg-slate-50 py-1 pl-1 pr-2">
                <span class="flex size-6 items-center justify-center rounded bg-teal-600 text-[10.8px] font-bold text-white"><?= e($profile['initials'] ?? 'IT') ?></span>
                <span class="flex flex-col"><span class="text-[11.7px] font-bold leading-none"><?= e($profile['name'] ?? 'Portal IT') ?></span><span class="text-[9.9px] font-medium leading-tight text-slate-500"><?= e($profile['subtitle'] ?? 'IT Division') ?></span></span>
            </div>
            <nav aria-label="Menu akun" class="flex flex-wrap gap-2.5">
                <!-- TODO: Enable authenticated account routes in implementation stage 2. -->
                <button type="button" class="header-action" data-account="password" aria-haspopup="dialog"><?= icon('lock', 'size-3') ?><span>Password<span class="sr-only">, belum tersedia</span></span></button>
                <button type="button" class="header-action" data-account="users" aria-haspopup="dialog"><?= icon('users', 'size-3') ?><span>Users<span class="sr-only">, belum tersedia</span></span></button>
                <button type="button" class="header-action hover:border-red-300 hover:bg-red-50 hover:text-red-700" data-account="logout" aria-haspopup="dialog"><?= icon('logout', 'size-3') ?><span>Logout<span class="sr-only">, belum tersedia</span></span></button>
            </nav>
        </div>
    </div>
</header>
