<?php
/**
 * Row under the header: the back button of pages that pass $backToDashboard ("Kembali ke Dashboard", or
 * $backLabel to $backHref, e.g. the Aset IT pages back to their own menu), with an optional $breadcrumbAside
 * (HTML set by the view) at the far right. No breadcrumb trail is shown.
 */
$label = $backLabel ?? 'Kembali ke Dashboard';
?>
<div class="flex flex-wrap items-center justify-between gap-3">
    <?= partial('button', [
        'label' => $label, 'href' => $backHref ?? url('/'), 'icon' => 'arrow-left', 'iconClass' => 'text-[16px]',
        'class' => 'button-secondary module-action inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border px-4 py-2 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-700',
        'attrs' => ['aria-label' => $label],
    ]) ?>
    <?= $breadcrumbAside ?? '' ?>
</div>
