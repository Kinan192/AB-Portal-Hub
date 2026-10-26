<?php
/**
 * Breadcrumb trail.
 * @var array $items  [['label' => ..., 'href' => ?string, 'class' => ?string, 'icon' => ?string, 'iconClass' => ?string]];
 *                    the last item is the current page.
 * Optional: $class (extra classes for the trail), $tag (nav|div), $separator (['icon' => name] or ['text' => '/']
 *           plus 'class'), and per-item 'separator' to override the one placed before that item.
 */
$tag = $tag ?? 'nav';
$separator = ['text' => '/', 'class' => 'text-slate-400'];
$renderSeparator = static function (array $sep): string {
    return isset($sep['icon'])
        ? design_icon($sep['icon'], $sep['class'] ?? '')
        : '<span' . attrs(['class' => $sep['class'] ?? null, 'aria-hidden' => 'true']) . '>' . e($sep['text'] ?? '/') . '</span>';
};
?>
<<?= $tag ?><?= attrs(['aria-label' => 'Breadcrumb', 'class' => cx($class ?? '', 'rsab-crumbs')]) ?>>
<?php foreach ($items as $i => $item): ?>
<?php if ($i > 0): ?><?= $renderSeparator($separator) ?><?php endif; ?>
<?php $label = (isset($item['icon']) ? design_icon($item['icon'], $item['iconClass'] ?? '') : '') . (isset($item['icon']) ? '<span>' . e($item['label']) . '</span>' : e($item['label'])); ?>
<?php if ($i === 0 && isset($item['href']) && count($items) > 1): ?>
<a<?= attrs(['class' => $item['class'] ?? null, 'href' => $item['href']]) ?>><?= $label ?></a>
<?php else: ?>
<span<?= attrs(['aria-current' => $i === count($items) - 1 ? 'page' : null]) ?>><?= $label ?></span>
<?php endif; ?>
<?php endforeach; ?>
</<?= $tag ?>>
