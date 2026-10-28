<?php
/**
 * Export buttons of a recap page (PRD §9, B8), as in the design's table header (docs/DESIGN/…/rekap-suhu.html):
 * Excel, PDF and Cetak. public/assets/js/rekap.js sets each address to the filters on screen after every load,
 * and turns a button off with a note when the filtered rows are above its limit (FR-E12) or there are none.
 * @var string $exportUrl  e.g. /rekap/suhu/ekspor (a dynamic form's keeps its ?id=…)
 */
$limits = config('report')['limits'];
?>
<div class="fr-export" role="group" aria-label="Ekspor rekap" data-export data-export-url="<?= e($exportUrl) ?>" data-limit-xlsx="<?= (int) $limits['xlsx'] ?>" data-limit-pdf="<?= (int) $limits['pdf'] ?>">
    <a class="dash-btn dash-btn--outline dash-btn--sm" data-export-format="xlsx" aria-disabled="true"><?= card_icon('sheet', 'size-4') ?>Ekspor Excel (.xlsx)</a>
    <a class="dash-btn dash-btn--outline dash-btn--sm" data-export-format="pdf" aria-disabled="true"><?= card_icon('file-text', 'size-4') ?>Ekspor PDF</a>
    <a class="dash-btn dash-btn--outline dash-btn--sm" data-export-format="cetak" target="_blank" rel="noopener" aria-disabled="true"><?= card_icon('printer', 'size-4') ?>Cetak</a>
    <p class="fr-export__note" data-export-note hidden></p>
</div>
