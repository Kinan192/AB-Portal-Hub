<?php $profile = current_profile(); ?>
<header class="portal-navbar sticky top-0 z-40 border-b border-slate-200 bg-white">
    <div class="portal-header page-shell flex flex-wrap items-center justify-between gap-4 py-3">
        <a href="<?= e(url('/')) ?>" class="flex min-w-0 flex-wrap items-center gap-4" aria-label="Beranda Portal RS Awal Bros Botania">
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
            <div class="profile-menu" data-profile-menu>
                <button type="button" class="profile-menu__button" aria-haspopup="menu" aria-expanded="false" aria-controls="profile-menu-list" data-profile-toggle>
                    <span class="profile-menu__avatar"><?= e($profile['initials'] ?? 'IT') ?></span>
                    <span class="profile-menu__name"><?= e($profile['name'] ?? 'Admin') ?></span>
                    <?= icon('chevron-down', 'size-3.5') ?>
                </button>
                <div class="profile-menu__list" id="profile-menu-list" role="menu" hidden>
                    <a role="menuitem" href="<?= e(url('/password')) ?>"><?= icon('lock', 'size-4') ?>Ubah password</a>
                    <a role="menuitem" href="<?= e(url('/riwayat-aktivitas')) ?>"><?= icon('history', 'size-4') ?>Riwayat aktivitas</a>
                    <form method="post" action="<?= e(url('/logout')) ?>"><?= csrf_field() ?><button type="submit" role="menuitem" class="is-danger"><?= icon('logout', 'size-4') ?>Logout</button></form>
                </div>
            </div>
        </div>
    </div>
</header>
