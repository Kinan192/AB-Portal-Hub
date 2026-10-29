<?php
/**
 * "Perlu perhatian" (AttentionController): every report with an answer marked red, newest first, as a sheet
 * (fr-* styles of the recap pages, resources/css/form-dinamis.css). Filters are a plain GET form.
 * @var array $filters  @var ?string $filterError  @var array<string, string> $forms  @var list<array> $rows
 */
?>
<main id="main-content" class="fr-page page-shell flex-1 pb-12">
    <div class="fr-shell">
        <header class="fr-head fr-head--hero hero-pattern">
            <span class="card-icon tone-amber fr-head__icon" aria-hidden="true"><?= card_icon('triangle-alert') ?></span>
            <div class="fr-head__text">
                <p class="fr-head__eyebrow">Notifikasi</p>
                <h1 class="fr-head__title">Perlu perhatian</h1>
            </div>
        </header>

        <section class="fr-card" aria-labelledby="fr-table-title">
            <div class="fr-card__head">
                <h2 id="fr-table-title"><?= card_icon('table-2', 'size-4') ?>Semua laporan</h2>
                <span class="fr-count" aria-live="polite"><?= e($countText) ?></span>
            </div>
            <form class="fr-filters fr-filters--recap" method="get" action="<?= e(url('/perlu-perhatian')) ?>" role="search">
                <label class="fr-field"><span>Form</span>
                    <select class="dash-input" name="form"><option value="">Semua form</option><?php foreach ($forms as $slug => $title): ?><option value="<?= e($slug) ?>"<?= $filters['form'] === $slug ? ' selected' : '' ?>><?= e($title) ?></option><?php endforeach; ?></select>
                </label>
                <label class="fr-field"><span>Dari tanggal</span><input class="dash-input" type="date" name="dari" value="<?= e($filters['from']) ?>"></label>
                <label class="fr-field"><span>Sampai tanggal</span><input class="dash-input" type="date" name="sampai" value="<?= e($filters['to']) ?>"></label>
                <button type="submit" class="dash-btn dash-btn--primary dash-btn--sm fr-apply"><?= card_icon('search', 'size-4') ?>Terapkan</button>
                <?php if ($filtered): ?><a class="section-textbtn fr-reset" href="<?= e(url('/perlu-perhatian')) ?>"><?= card_icon('refresh-ccw', 'size-4') ?>Reset filter</a><?php endif; ?>
            </form>
            <?php if ($filterError !== null): ?><p class="fr-filter-error" role="alert"><?= e($filterError) ?> Semua tanggal ditampilkan.</p><?php endif; ?>

            <?php if ($rows === []): ?>
            <div class="fr-empty"><?= card_icon('circle-check', 'size-6') ?><b><?= $filtered ? 'Tidak ada laporan untuk filter ini' : 'Belum ada laporan yang perlu perhatian' ?></b></div>
            <?php else: ?>
            <div class="fr-table-wrap" role="region" aria-label="Tabel laporan perlu perhatian" tabindex="0">
                <table class="fr-table">
                    <thead><tr><th scope="col" class="fr-rownum">#</th><th scope="col">Waktu kirim</th><th scope="col">Form</th><th scope="col">No. entri</th><th scope="col">Jawaban yang perlu perhatian</th><th scope="col"><span class="sr-only">Buka</span></th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $i => $row): ?>
                        <tr<?= $row['read'] ? ' class="fr-row--read"' : '' ?>>
                            <td class="fr-rownum"><?= $from + $i + 1 ?></td>
                            <td class="fr-nowrap"><?= e($row['when']) ?></td>
                            <td><b><?= e($row['form']) ?></b></td>
                            <td class="fr-nowrap"><?= e($row['entry_no']) ?></td>
                            <td><?= $row['reasons'] === [] ? '<span class="fr-muted">—</span>' : '<span class="fr-value fr-attention">' . e(implode("\n", $row['reasons'])) . '</span>' ?></td>
                            <td class="fr-nowrap"><a class="dash-btn dash-btn--ghost dash-btn--sm" href="<?= e($row['url']) ?>" aria-label="<?= e('Buka ' . $row['entry_no']) ?>">Buka<?= card_icon('chevron-right', 'size-4') ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            <?php if ($pages > 1): ?>
            <nav class="fr-pager" aria-label="Halaman perlu perhatian">
                <?php if ($prevUrl !== null): ?><a class="dash-btn dash-btn--ghost dash-btn--sm" href="<?= e($prevUrl) ?>"><?= card_icon('chevron-left', 'size-4') ?>Sebelumnya</a><?php else: ?><span class="dash-btn dash-btn--ghost dash-btn--sm" aria-disabled="true"><?= card_icon('chevron-left', 'size-4') ?>Sebelumnya</span><?php endif; ?>
                <span>Halaman <?= (int) $page ?> dari <?= (int) $pages ?></span>
                <?php if ($nextUrl !== null): ?><a class="dash-btn dash-btn--ghost dash-btn--sm" href="<?= e($nextUrl) ?>">Berikutnya<?= card_icon('chevron-right', 'size-4') ?></a><?php else: ?><span class="dash-btn dash-btn--ghost dash-btn--sm" aria-disabled="true">Berikutnya<?= card_icon('chevron-right', 'size-4') ?></span><?php endif; ?>
            </nav>
            <?php endif; ?>
        </section>
    </div>
</main>
