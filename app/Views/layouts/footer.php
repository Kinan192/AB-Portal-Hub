<?php
/** @var string|null $navbarVariant */
?>
<?php if (($navbarVariant ?? '') === 'form'): ?><div class="border-t border-slate-200 bg-white"><?php endif; ?>
<footer class="portal-footer page-shell mt-auto py-8 text-center text-[11.7px] text-slate-500">
    &copy; <?= e(date('Y')) ?> RS Awal Bros Botania
</footer>
<?php if (($navbarVariant ?? '') === 'form'): ?></div><?php endif; ?>
