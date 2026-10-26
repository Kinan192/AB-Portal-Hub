<?php
/**
 * Page footer. Colour, border and size come from the shared footer style; pages set spacing.
 * Optional: $class, $text (null renders an empty bar), $innerClass (wraps the text in a div).
 */
$text = array_key_exists('text', get_defined_vars()) ? $text : '© ' . date('Y') . ' RS Awal Bros Botania';
?>
<footer<?= attrs(['class' => $class ?? 'text-center py-8']) ?>>
<?php if ($text !== null && isset($innerClass)): ?><div class="page-shell"><div class="<?= e($innerClass) ?>"><?= e($text) ?></div></div>
<?php elseif ($text !== null): ?><div class="page-shell"><?= e($text) ?></div>
<?php endif; ?>
</footer>
