<?php
/**
 * Export buttons of a recap page (PRD §9, B8), as in the design's table header (docs/DESIGN/…/rekap-suhu.html):
 * Excel, PDF and Cetak. public/assets/js/rekap.js sets each address to the filters on screen after every load,
 * and turns a button off with a note when the filtered rows are above its limit (FR-E12) or there are none.
 * "Tanda tangan" opens the signature block of the PDF and Cetak export (rekap.js saves it to $signatureUrl).
 * @var string $exportUrl  e.g. /rekap/suhu/ekspor (a dynamic form's keeps its ?id=…)
 * @var string $signatureUrl  @var array<string, string> $signature (App\Services\ReportSignature)
 */
$limits = config('report')['limits'];
$signField = static fn (string $name, string $label, string $help = ''): string => '<div class="dash-field">'
    . '<label for="sign-' . e($name) . '">' . e($label) . '</label>'
    . '<input id="sign-' . e($name) . '" class="dash-input" name="' . e($name) . '" maxlength="' . \App\Services\ReportSignature::MAX . '" autocomplete="off" value="' . e($signature[$name] ?? '') . '"' . ($help !== '' ? ' aria-describedby="sign-' . e($name) . '-help"' : '') . '>'
    . ($help !== '' ? '<p class="dash-help" id="sign-' . e($name) . '-help">' . e($help) . '</p>' : '')
    . '<p class="dash-error" data-error-for="' . e($name) . '" hidden></p></div>';
?>
<div class="fr-export" role="group" aria-label="Ekspor rekap" data-export data-export-url="<?= e($exportUrl) ?>" data-limit-xlsx="<?= (int) $limits['xlsx'] ?>" data-limit-pdf="<?= (int) $limits['pdf'] ?>">
    <button type="button" class="dash-btn dash-btn--outline dash-btn--sm" data-sign-open aria-haspopup="dialog"><?= card_icon('pencil', 'size-4') ?>Tanda tangan</button>
    <a class="dash-btn dash-btn--outline dash-btn--sm" data-export-format="xlsx" aria-disabled="true"><?= card_icon('sheet', 'size-4') ?>Ekspor Excel (.xlsx)</a>
    <a class="dash-btn dash-btn--outline dash-btn--sm" data-export-format="pdf" aria-disabled="true"><?= card_icon('file-text', 'size-4') ?>Ekspor PDF</a>
    <a class="dash-btn dash-btn--outline dash-btn--sm" data-export-format="cetak" target="_blank" rel="noopener" aria-disabled="true"><?= card_icon('printer', 'size-4') ?>Cetak</a>
    <p class="fr-export__note" data-export-note hidden></p>
</div>

<dialog id="fr-sign" class="dash-dialog fr-sign" aria-labelledby="fr-sign-title">
    <form method="dialog" class="dash-dialog__pad" data-sign-form data-sign-url="<?= e($signatureUrl) ?>" novalidate>
        <h2 id="fr-sign-title" class="dash-dialog__title">Tanda tangan ekspor</h2>
        <p class="dash-text">Tampil di bawah tabel PDF dan Cetak rekap ini.</p>
        <div class="fr-sign__grid">
            <fieldset class="fr-sign__side">
                <legend>Kiri</legend>
                <?= $signField('left_title', 'Judul') ?>
                <?= $signField('left_role', 'Jabatan') ?>
                <?= $signField('left_name', 'Nama', 'Kosong: nama akun yang mengekspor.') ?>
            </fieldset>
            <fieldset class="fr-sign__side">
                <legend>Kanan</legend>
                <?= $signField('right_title', 'Judul') ?>
                <?= $signField('right_role', 'Jabatan') ?>
                <?= $signField('right_name', 'Nama', 'Kosong: garis untuk tanda tangan.') ?>
            </fieldset>
        </div>
        <div class="dash-dialog__actions">
            <button type="button" class="dash-btn dash-btn--ghost" data-sign-cancel>Batal</button>
            <button type="submit" class="dash-btn dash-btn--primary" data-sign-save><?= card_icon('save', 'size-4') ?>Simpan</button>
        </div>
    </form>
</dialog>
