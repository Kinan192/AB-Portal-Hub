<?php
$logoPath = config('app')['logo'];
$hasLogo = is_file(BASE_PATH . '/public/assets/' . $logoPath);
?>
<span class="inline-flex shrink-0 items-center">
    <?php if ($hasLogo): ?>
        <img src="<?= e(asset($logoPath)) ?>?v=<?= e(filemtime(BASE_PATH . '/public/assets/' . $logoPath)) ?>" alt="RS Awal Bros" class="h-8 w-auto max-w-[220px] object-contain object-left" width="348" height="53">
    <?php else: ?>
        <span class="whitespace-nowrap text-2xl font-black uppercase leading-none tracking-tight text-red-600">RS AWAL BROS</span>
    <?php endif; ?>
</span>
