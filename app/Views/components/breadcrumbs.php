<?php
/**
 * Row under the header. Form pages pass $backToDashboard and get a "Kembali ke Dashboard" button, with an
 * optional $breadcrumbAside (HTML set by the view) at the far right; report pages pass $breadcrumbs.
 * @var list<array{label: string}>|null $breadcrumbs
 */
if (!empty($backToDashboard)): ?>
<div class="flex flex-wrap items-center justify-between gap-3">
    <?= partial('button', [
        'label' => 'Kembali ke Dashboard', 'href' => url('/'), 'icon' => 'arrow-left', 'iconClass' => 'text-[16px]',
        'class' => 'button-secondary module-action inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border px-4 py-2 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-700',
        'attrs' => ['aria-label' => 'Kembali ke Dashboard'],
    ]) ?>
    <?= $breadcrumbAside ?? '' ?>
</div>
<?php else:
    echo partial('breadcrumb', [
        'items' => array_merge([
            ['label' => 'Portal Monitoring Terpadu', 'href' => url('/'), 'icon' => 'house', 'iconClass' => 'text-[14px]'],
        ], $breadcrumbs),
    ]);
endif;
