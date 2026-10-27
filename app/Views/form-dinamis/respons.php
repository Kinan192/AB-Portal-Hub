<?php
/**
 * History of a form (a built-in module form or a dynamic form), frontend only: public/assets/js/form-riwayat.js
 * draws the reports kept in the browser state (dashboard-store.js: sample reports + the ones sent in this tab)
 * with one column per question of the form as it is now, and a detail dialog with every question and its
 * answer. Built-in forms show the breadcrumb, dynamic forms the "Kembali ke Dashboard" button (layout).
 */
?>
<main id="main-content" class="fr-page page-shell flex-1 pb-12" data-form-history data-form-id="<?= e($formId) ?>"
    data-fill-url="<?= e($fillUrl) ?>" data-builtin="<?= $builtin ? '1' : '0' ?>">
    <div class="fe-missing" data-fill-missing hidden>
        <?= card_icon('circle-alert', 'size-8 text-slate-400') ?>
        <h1>Form tidak ditemukan</h1>
        <p>Form ini tidak ada di dashboard (mungkin sudah dihapus, atau data contoh sudah diatur ulang).</p>
        <a class="dash-btn dash-btn--primary" href="<?= e(url('/')) ?>">Kembali ke Dashboard</a>
    </div>
    <div class="fr-shell" data-fr-root hidden>
        <header class="fr-head">
            <span class="card-icon tone-teal fr-head__icon" data-fr-tile aria-hidden="true"></span>
            <div class="fr-head__text">
                <p class="fe-sub__eyebrow">Riwayat Laporan</p>
                <h1 class="fe-sub__title" data-fr-title></h1>
                <p class="fr-head__desc">Kolom mengikuti pertanyaan form saat ini. Pilih satu laporan untuk melihat semua jawabannya.</p>
            </div>
            <a class="dash-btn dash-btn--primary" data-fr-fill href="<?= e($fillUrl) ?>"><?= card_icon('file-pen-line', 'size-4') ?>Isi Form</a>
        </header>

        <section class="fr-stats" aria-label="Ringkasan riwayat">
            <div class="fr-stat"><span>Laporan ditampilkan</span><b data-stat="shown">–</b></div>
            <div class="fr-stat"><span>Bulan ini</span><b data-stat="month">–</b></div>
            <div class="fr-stat"><span>Perlu perhatian</span><b data-stat="attention">–</b></div>
            <div class="fr-stat"><span>Terakhir dikirim</span><b data-stat="last">–</b></div>
        </section>

        <section class="fr-card" aria-labelledby="fr-table-title">
            <div class="fr-card__head">
                <h2 id="fr-table-title"><?= card_icon('history', 'size-4') ?>Riwayat</h2>
                <span class="fr-count" data-fr-count aria-live="polite"></span>
            </div>
            <div class="fr-filters" role="search">
                <div class="fr-search">
                    <label class="sr-only" for="fr-search">Cari di riwayat</label>
                    <?= card_icon('search', 'size-4') ?>
                    <input id="fr-search" class="dash-input" type="search" placeholder="Cari jawaban, mis. nama lokasi, IP, catatan" autocomplete="off" data-fr-search>
                </div>
                <div class="fr-period">
                    <label class="sr-only" for="fr-month">Periode</label>
                    <select id="fr-month" class="dash-input" data-fr-month><option value="">Semua periode</option></select>
                </div>
                <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" data-fr-reset hidden><?= card_icon('refresh-ccw', 'size-4') ?>Reset filter</button>
            </div>
            <div data-fr-body></div>
            <nav class="fr-pager" aria-label="Halaman riwayat" data-fr-pager hidden>
                <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" data-fr-page="-1"><?= card_icon('chevron-left', 'size-4') ?>Sebelumnya</button>
                <span data-fr-page-info></span>
                <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" data-fr-page="1">Berikutnya<?= card_icon('chevron-right', 'size-4') ?></button>
            </nav>
        </section>
        <p class="fr-note"><?= card_icon('info', 'size-4 shrink-0') ?><span>Data contoh: laporan contoh ditambah laporan yang dikirim di tab browser ini. Laporan baru hilang saat tab ditutup.</span></p>
    </div>
</main>

<dialog id="fr-detail" class="dash-dialog fr-detail" aria-labelledby="fr-detail-title">
    <div class="dash-dialog__head">
        <div>
            <h2 id="fr-detail-title" class="dash-dialog__title" data-detail-title></h2>
            <p class="fr-detail__sub" data-detail-sub></p>
        </div>
        <button type="button" class="fe-icon" data-detail-close aria-label="Tutup"><?= card_icon('x', 'size-5') ?></button>
    </div>
    <div class="dash-dialog__body fr-detail__body" data-detail-body></div>
    <div class="dash-dialog__foot">
        <button type="button" class="dash-btn dash-btn--primary" data-detail-close>Tutup</button>
    </div>
</dialog>
<template id="dash-icons"><?php foreach ($iconNames as $name): ?><span data-icon="<?= e($name) ?>"><?= card_icon($name) ?></span><?php endforeach; ?></template>
<script type="application/json" id="dashboard-state"><?= json_encode($clientState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<?= partial('toast') ?>
