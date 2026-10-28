<?php
/**
 * Recap of a form as a sheet, drawn by public/assets/js/rekap.js from GET /form-dinamis/respons/data:
 * statistic cards, filters kept in the URL, one column per question, paging, and a detail dialog.
 */
$tone = $card['color'] ?? 'teal';
?>
<main id="main-content" class="fr-page page-shell flex-1 pb-12" data-recap data-data-url="<?= e($dataUrl) ?>" data-detail-url="<?= e($detailUrl) ?>" data-this-month="">
    <div class="fr-shell">
        <header class="fr-head fr-head--hero hero-pattern">
            <span class="card-icon tone-<?= e($tone) ?> fr-head__icon" aria-hidden="true"><?= card_icon($card['icon'] ?? 'clipboard-list') ?></span>
            <div class="fr-head__text">
                <p class="fr-head__eyebrow">Rekap</p>
                <h1 class="fr-head__title"><?= e($title) ?></h1>
            </div>
            <a class="dash-btn fr-head__action" href="<?= e($formUrl) ?>"><?= card_icon('file-pen-line', 'size-4') ?>Isi Form</a>
        </header>

        <section class="fr-stats" aria-label="Ringkasan rekap" data-stats>
            <?php foreach (['Laporan ditampilkan', 'Bulan ini', 'Perlu perhatian', 'Terakhir dikirim'] as $label): ?>
            <div class="fr-stat"><span><?= e($label) ?></span><b>–</b><small></small></div>
            <?php endforeach; ?>
        </section>

        <section class="fr-card" aria-labelledby="fr-table-title">
            <div class="fr-card__head">
                <h2 id="fr-table-title"><?= card_icon('table-2', 'size-4') ?>Data laporan</h2>
                <span class="fr-count" data-fr-count aria-live="polite"></span>
                <?= partial('recap-export', ['exportUrl' => $exportUrl]) ?>
            </div>
            <form class="fr-filters fr-filters--recap" role="search" data-filters>
                <div class="fr-search">
                    <label class="sr-only" for="fr-q">Cari</label>
                    <?= card_icon('search', 'size-4') ?>
                    <input id="fr-q" class="dash-input" type="search" name="q" maxlength="100" placeholder="Cari" autocomplete="off">
                </div>
                <label class="fr-field"><span>Bulan</span><input class="dash-input" type="month" name="periode"></label>
                <label class="fr-field"><span>Dari tanggal</span><input class="dash-input" type="date" name="dari"></label>
                <label class="fr-field"><span>Sampai tanggal</span><input class="dash-input" type="date" name="sampai"></label>
                <label class="fr-field"><span>Status</span>
                    <select class="dash-input" name="perhatian"><option value="">Semua laporan</option><option value="ya">Perlu perhatian</option><option value="tidak">Tanpa catatan</option></select>
                </label>
                <label class="fr-field fr-field--small"><span>Per halaman</span>
                    <select class="dash-input" name="per"><option value="25">25</option><option value="50">50</option><option value="100">100</option></select>
                </label>
                <a class="section-textbtn fr-reset" href="?" data-reset><?= card_icon('refresh-ccw', 'size-4') ?>Reset filter</a>
            </form>
            <p class="fr-filter-error" role="alert" data-filter-error hidden></p>
            <div data-fr-body aria-busy="true"><div class="fr-empty"><b>Memuat data…</b></div></div>
            <nav class="fr-pager" aria-label="Halaman rekap" data-fr-pager hidden>
                <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" data-fr-page="-1"><?= card_icon('chevron-left', 'size-4') ?>Sebelumnya</button>
                <span data-fr-page-info></span>
                <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" data-fr-page="1">Berikutnya<?= card_icon('chevron-right', 'size-4') ?></button>
            </nav>
        </section>
    </div>
</main>

<?= partial('recap-dialogs') ?>
<template id="dash-icons"><?php foreach ($iconNames as $name): ?><span data-icon="<?= e($name) ?>"><?= card_icon($name) ?></span><?php endforeach; ?></template>
<?= partial('toast') ?>
