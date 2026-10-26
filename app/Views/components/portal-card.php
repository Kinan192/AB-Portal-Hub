<?php
$isExternal = $card['type'] === 'external';
$configured = $isExternal && is_string($card['target']) && filter_var($card['target'], FILTER_VALIDATE_URL) && parse_url($card['target'], PHP_URL_SCHEME) === 'https';
$available = !$isExternal && ($card['available'] ?? false);
$tag = $configured || $available ? 'a' : 'button';
?>
<<?= $tag ?> class="portal-card group" <?php if ($available): ?>href="<?= e(url($card['target'])) ?>"<?php elseif ($configured): ?>href="<?= e($card['target']) ?>" target="_blank" rel="noopener noreferrer"<?php else: ?>type="button" data-module="<?= e($card['key']) ?>" data-title="<?= e($card['title']) ?>" data-type="<?= e($card['type']) ?>" aria-haspopup="dialog"<?php endif; ?>>
    <span class="card-badge <?= $isExternal || $available ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-600' ?>"><?= $isExternal ? ($configured ? 'Tautan' : 'Atur URL') : ($available ? 'Form' : 'Segera') ?></span>
    <span class="card-icon tone-<?= e($card['tone']) ?>"><?= icon($card['icon'], 'size-6') ?></span>
    <span class="portal-card-title"><?= e($card['title']) ?></span>
    <span class="portal-card-description"><?= e($card['description']) ?></span>
</<?= $tag ?>>
