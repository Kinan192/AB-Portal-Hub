<?php
/** @var list<array{label: string, href?: string}> $breadcrumbs */
?>
<nav aria-label="Breadcrumb" class="app-breadcrumb text-xs text-slate-500">
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
        <li><a href="/" class="inline-flex items-center gap-1 font-medium transition-colors hover:text-emerald-700"><?= icon('home', 'size-3.5 shrink-0') ?><span>Portal Monitoring Terpadu</span></a></li>
        <?php foreach ($breadcrumbs as $breadcrumbIndex => $breadcrumb): ?>
        <li aria-hidden="true" class="text-slate-300">›</li>
        <li>
            <?php if ($breadcrumbIndex === array_key_last($breadcrumbs)): ?>
            <span aria-current="page" class="inline-block rounded bg-emerald-50 px-2 py-1 font-semibold text-emerald-800"><?= e($breadcrumb['label']) ?></span>
            <?php elseif (isset($breadcrumb['href'])): ?>
            <a href="<?= e($breadcrumb['href']) ?>" class="font-medium transition-colors hover:text-emerald-700"><?= e($breadcrumb['label']) ?></a>
            <?php else: ?>
            <span><?= e($breadcrumb['label']) ?></span>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ol>
</nav>
