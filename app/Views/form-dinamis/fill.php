<?php
/**
 * Fill-in page of a form, drawn by public/assets/js/form-dinamis.js. $preview: the editor's Pratinjau tab.
 */
?>
<main id="main-content" class="gf-page gf-scope" data-form-fill data-form-id="<?= e($formId) ?>"<?= !empty($preview) ? ' data-preview' : '' ?>
    data-responses-url="<?= e(url('/form-dinamis/respons')) ?>"<?= isset($submitUrl) ? ' data-submit-url="' . e($submitUrl) . '"' : '' ?><?= isset($formToken) ? ' data-form-token="' . e($formToken) . '"' : '' ?><?= isset($formVersion) ? ' data-form-version="' . e((string) $formVersion) . '"' : '' ?>>
    <div class="fe-missing" data-fill-missing hidden>
        <?= card_icon('circle-alert', 'size-8 text-slate-400') ?>
        <h1>Form tidak ditemukan</h1>
        <p>Form ini tidak ada di dashboard (mungkin sudah dihapus).</p>
        <a class="dash-btn dash-btn--primary" href="<?= e(url('/')) ?>">Kembali ke Dashboard</a>
    </div>
    <form class="gf-form" data-fill-form novalidate hidden></form>
</main>

<dialog id="fill-success" class="dash-dialog" aria-labelledby="fill-success-title" aria-describedby="fill-success-text">
    <div class="dash-dialog__pad fill-success">
        <span class="fill-success__icon"><?= card_icon('circle-check', 'size-7') ?></span>
        <h2 id="fill-success-title" class="dash-dialog__title">Laporan Berhasil Terkirim!</h2>
        <p id="fill-success-text" class="dash-text" data-fill-success-text></p>
        <div class="dash-dialog__actions">
            <a class="dash-btn dash-btn--ghost" href="<?= e(url('/')) ?>">Kembali ke Dashboard</a>
            <a class="dash-btn dash-btn--primary" data-fill-success-rekap href="#" target="_blank" rel="noopener"><?= card_icon('table-2', 'size-4') ?>Lihat Rekap</a>
        </div>
    </div>
</dialog>
<template id="dash-icons"><?php foreach ($iconNames as $name): ?><span data-icon="<?= e($name) ?>"><?= card_icon($name) ?></span><?php endforeach; ?></template>
<?php if (isset($submitUrl)): ?>
<script type="application/json" id="fill-entry"><?= json_encode(['prefill' => (object) ($prefill ?? []), 'editing' => $editing ?? null], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<?php endif; ?>
<script type="application/json" id="dashboard-state"><?= json_encode($clientState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<?= partial('toast') ?>
