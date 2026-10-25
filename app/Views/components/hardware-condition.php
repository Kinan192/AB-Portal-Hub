<?php
/**
 * @var array{
 *     key: string,
 *     number: int,
 *     title: string,
 *     description: string,
 *     good: string,
 *     bad: string,
 *     good_badge: string,
 *     bad_badge: string,
 *     placeholder: string
 * } $condition
 */
$key = $condition['key'];
?>
<fieldset class="inspection-panel" aria-describedby="<?= e($key) ?>-help">
    <legend class="sr-only"><?= e($condition['title']) ?>, wajib diisi</legend>
    <div class="inspection-section-heading">
        <span class="inspection-step"><?= e($condition['number']) ?></span>
        <div class="min-w-0 flex-1">
            <h2 class="text-base font-bold tracking-tight text-slate-900 uppercase"><?= e($condition['title']) ?> <span class="ml-1 text-rose-500" aria-hidden="true">*</span></h2>
            <p id="<?= e($key) ?>-help" class="mt-0.5 text-xs text-slate-500"><?= e($condition['description']) ?></p>
        </div>
        <span class="inspection-section-tag">Radio Option</span>
    </div>
    <div class="mt-6 space-y-3">
        <?php foreach (['good' => 'BAIK', 'bad' => 'TIDAK BAIK'] as $state => $label): ?>
        <?php
        $dotClass = $state === 'good' ? 'bg-emerald-500' : 'bg-rose-500';
        $badgeClass = $state === 'good'
            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
            : 'border-rose-200 bg-rose-50 text-rose-600';
        ?>
        <div class="inspection-choice">
            <input class="inspection-radio sr-only" type="radio" id="<?= e($key . '-' . $state) ?>" name="kondisi_<?= e($key) ?>" value="<?= e($label) ?>" required <?= $state === 'good' ? 'checked' : '' ?>>
            <label for="<?= e($key . '-' . $state) ?>" class="inspection-choice-label">
                <span class="inspection-radio-indicator" aria-hidden="true"></span>
                <span class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2.5 gap-y-1">
                    <span class="size-2.5 shrink-0 rounded-full <?= e($dotClass) ?>" aria-hidden="true"></span>
                    <span class="text-sm font-bold text-slate-800"><?= e($label) ?></span>
                    <span class="text-xs leading-relaxed text-slate-500">(<?= e($condition[$state]) ?>)</span>
                </span>
                <span class="inspection-condition-badge <?= e($badgeClass) ?>"><?= e($condition[$state . '_badge']) ?></span>
            </label>
        </div>
        <?php endforeach; ?>
        <div class="inspection-choice">
            <input class="inspection-radio sr-only" type="radio" id="<?= e($key) ?>-other" name="kondisi_<?= e($key) ?>" value="Other" required>
            <label for="<?= e($key) ?>-other" class="inspection-choice-label pb-0">
                <span class="inspection-radio-indicator" aria-hidden="true"></span>
                <span class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-1"><span class="text-sm font-bold text-slate-800">Other:</span><span class="text-xs text-slate-500">Tuliskan keterangan kondisi lainnya</span></span>
                <span class="inspection-condition-badge border-slate-200 bg-slate-100 text-slate-500">Kustom</span>
            </label>
            <div class="p-4 pt-3 sm:pl-12">
                <label for="<?= e($key) ?>-other-text" class="sr-only">Keterangan <?= e($condition['title']) ?> lainnya</label>
                <input id="<?= e($key) ?>-other-text" name="keterangan_<?= e($key) ?>" data-other-for="kondisi_<?= e($key) ?>" type="text" maxlength="500" class="inspection-input" placeholder="<?= e($condition['placeholder']) ?>">
            </div>
        </div>
    </div>
</fieldset>
