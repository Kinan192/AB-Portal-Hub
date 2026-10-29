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
<<<<<<< HEAD
            <?php if (\App\Core\Auth::check()): $bell = \App\Services\Attention::notifications(); ?>
            <div class="notif-menu" data-notif-menu>
                <button type="button" class="notif-menu__button" aria-expanded="false" aria-controls="notif-panel" data-notif-toggle
                    data-label="<?= e('Notifikasi perlu perhatian, ' . \App\Services\Attention::DAYS . ' hari terakhir') ?>"
                    aria-label="<?= e('Notifikasi perlu perhatian, ' . \App\Services\Attention::DAYS . ' hari terakhir: ' . $bell['unread'] . ' belum dilihat') ?>">
                    <?= card_icon('bell', 'size-4') ?>
                    <span class="notif-menu__badge" aria-hidden="true" data-notif-badge<?= $bell['unread'] > 0 ? '' : ' hidden' ?>><?= $bell['unread'] > 9 ? '9+' : (int) $bell['unread'] ?></span>
                </button>
                <div class="notif-menu__panel" id="notif-panel" hidden data-seen-url="<?= e(url('/perlu-perhatian/dilihat')) ?>">
                    <div class="notif-menu__head">
                        <div><b>Perlu perhatian</b><span><?= \App\Services\Attention::DAYS ?> hari terakhir</span></div>
                        <button type="button" class="notif-menu__seen-all" data-notif-seen-all<?= $bell['unread'] > 0 ? '' : ' hidden' ?>>Tandai semua dilihat</button>
                    </div>
                    <?php if ($bell['items'] === []): ?>
                    <p class="notif-menu__empty">Tidak ada laporan yang perlu perhatian.</p>
                    <?php else: ?>
                    <ul class="notif-menu__list">
                        <?php foreach ($bell['items'] as $item): ?>
                        <li><a href="<?= e($item['url']) ?>" data-notif-id="<?= (int) $item['id'] ?>"<?= $item['read'] ? ' class="is-read"' : '' ?>>
                            <span class="notif-menu__form"><?= e($item['form']) ?></span>
                            <span class="notif-menu__meta"><?= e($item['entry_no']) ?> · <?= e($item['when']) ?></span>
                            <?php if ($item['reasons'] !== []): ?><span class="notif-menu__reason"><?= e(implode('; ', $item['reasons'])) ?></span><?php endif; ?>
                        </a></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    <a class="notif-menu__all" href="<?= e(url('/perlu-perhatian')) ?>" target="_blank" rel="noopener">Lihat semua data<?= card_icon('external-link', 'size-4') ?></a>
                </div>
            </div>
            <?php endif; ?>
=======
            <span class="inline-flex items-center gap-1.5 rounded border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-[11.7px] font-semibold text-emerald-800" title="Status halaman portal, bukan status layanan eksternal">
                <span class="size-2 rounded-full bg-emerald-500" aria-hidden="true"></span>Portal aktif
            </span>
>>>>>>> 899d036f59ce2361fefc2c1a8249a7b80cbc5bb5
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
