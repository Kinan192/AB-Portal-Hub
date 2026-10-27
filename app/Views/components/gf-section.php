<?php
/**
 * Section header card inside a Google-Forms style form (resources/css/gform.css).
 * @var int $number
 * @var string $title
 * Optional: $titleId, $titleTag (h2|h3), $description, $aside (HTML shown at the right, e.g. a pill).
 */
$titleTag = $titleTag ?? 'h2';
?>
<div class="gf-card gf-section">
    <div class="gf-section-head">
        <span class="gf-section-num" aria-hidden="true"><?= e($number) ?></span>
        <div class="gf-section-text">
            <<?= $titleTag ?><?= attrs(['class' => 'gf-section-title', 'id' => $titleId ?? null]) ?>><?= e($title) ?></<?= $titleTag ?>>
            <?php if (!empty($description)): ?><p class="gf-section-desc"><?= e($description) ?></p><?php endif; ?>
        </div>
        <?= $aside ?? '' ?>
    </div>
</div>
