<?php
/**
 * Dialogs of the recap page (form-dinamis/rekap-form.php; public/assets/js/rekap.js): the detail of an
 * entry with its change history, Ubah and Hapus (FR-R7), and the delete dialog with its reason (FR-F9).
 */
?>
<dialog id="fr-detail" class="dash-dialog fr-detail" aria-labelledby="fr-detail-title">
    <div class="dash-dialog__head">
        <div>
            <h2 id="fr-detail-title" class="dash-dialog__title" data-detail-title></h2>
            <p class="fr-detail__sub" data-detail-sub></p>
        </div>
        <button type="button" class="fe-icon" data-detail-close aria-label="Tutup"><?= card_icon('x', 'size-5') ?></button>
    </div>
    <div class="dash-dialog__body fr-detail__body" data-detail-body></div>
    <div class="dash-dialog__foot fr-detail__foot">
        <button type="button" class="dash-btn dash-btn--ghost fr-danger" data-detail-delete><?= card_icon('trash-2', 'size-4') ?>Hapus</button>
        <a class="dash-btn dash-btn--ghost" data-detail-edit href="#"><?= card_icon('pencil', 'size-4') ?>Ubah</a>
        <button type="button" class="dash-btn dash-btn--primary" data-detail-close>Tutup</button>
    </div>
</dialog>

<dialog id="fr-delete" class="dash-dialog" aria-labelledby="fr-delete-title" aria-describedby="fr-delete-text">
    <form method="dialog" class="dash-dialog__pad" data-delete-form novalidate>
        <h2 id="fr-delete-title" class="dash-dialog__title">Hapus entri ini?</h2>
        <p id="fr-delete-text" class="dash-text">Entri <b data-delete-no></b> disembunyikan dari rekap, dashboard, dan ekspor, tetapi tetap disimpan untuk audit. Nomor entrinya tidak dipakai lagi.</p>
        <div class="dash-field">
            <label for="fr-delete-reason">Alasan hapus <span class="text-red-600" aria-hidden="true">*</span></label>
            <textarea id="fr-delete-reason" class="dash-input" rows="3" maxlength="500" required aria-describedby="fr-delete-reason-help fr-delete-reason-error" data-delete-reason></textarea>
            <p class="dash-help" id="fr-delete-reason-help"><span data-delete-count>0</span>/500 karakter, minimal 10.</p>
            <p class="dash-error" id="fr-delete-reason-error" data-delete-error hidden></p>
        </div>
        <div class="dash-dialog__actions">
            <button type="button" class="dash-btn dash-btn--ghost" data-delete-cancel>Batal</button>
            <button type="submit" class="dash-btn dash-btn--danger" data-delete-go><?= card_icon('trash-2', 'size-4') ?>Hapus</button>
        </div>
    </form>
</dialog>
