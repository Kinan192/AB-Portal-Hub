<?php
/**
 * Riwayat aktivitas (PRD SEC-12, AuditController): the change log, read only, newest first. Filters are a plain
 * GET form (kept in the URL); one table on every width (fr-* styles of the recap pages, resources/css/form-dinamis.css).
 * No edit or delete is offered.
 * @var array $filters  @var list<array> $rows  @var string $countText  @var ?string $prevUrl  @var ?string $nextUrl
 */
use App\Services\ActivityLog;

$chip = static fn (array $row): string => '<span class="fr-chip fr-chip--' . e($row['tone']) . '">' . e($row['action']) . '</span>';
?>
<main id="main-content" class="fr-page page-shell flex-1 pb-12">
    <div class="fr-shell">
        <header class="fr-head fr-head--hero hero-pattern">
            <span class="card-icon tone-slate fr-head__icon" aria-hidden="true"><?= card_icon('history') ?></span>
            <div class="fr-head__text">
                <p class="fr-head__eyebrow">Akun admin</p>
                <h1 class="fr-head__title">Riwayat aktivitas</h1>
            </div>
        </header>

        <section class="fr-card" aria-labelledby="fr-table-title">
            <div class="fr-card__head">
                <h2 id="fr-table-title"><?= card_icon('history', 'size-4') ?>Catatan aktivitas</h2>
                <span class="fr-count" aria-live="polite"><?= e($countText) ?></span>
            </div>
            <form class="fr-filters fr-filters--recap" method="get" action="<?= e(url('/riwayat-aktivitas')) ?>" role="search">
                <div class="fr-search">
                    <label class="sr-only" for="fr-q">Cari catatan</label>
                    <?= card_icon('search', 'size-4') ?>
                    <input id="fr-q" class="dash-input" type="search" name="q" maxlength="100" value="<?= e($filters['q']) ?>" placeholder="Cari no. entri, ID laporan, judul kartu, alasan" autocomplete="off">
                </div>
                <label class="fr-field"><span>Dari tanggal</span><input class="dash-input" type="date" name="dari" value="<?= e($filters['from']) ?>"></label>
                <label class="fr-field"><span>Sampai tanggal</span><input class="dash-input" type="date" name="sampai" value="<?= e($filters['to']) ?>"></label>
                <label class="fr-field"><span>Aksi</span>
                    <select class="dash-input" name="aksi"><option value="">Semua aksi</option><?php foreach (ActivityLog::GROUPS as $code => [$label]): ?><option value="<?= e($code) ?>"<?= $filters['group'] === $code ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
                </label>
                <label class="fr-field"><span>Objek</span>
                    <select class="dash-input" name="objek"><option value="">Semua objek</option><?php foreach (ActivityLog::ENTITIES as $code => $label): ?><option value="<?= e($code) ?>"<?= $filters['entity'] === $code ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
                </label>
                <button type="submit" class="dash-btn dash-btn--primary dash-btn--sm fr-apply"><?= card_icon('search', 'size-4') ?>Terapkan</button>
                <?php if ($filtered): ?><a class="section-textbtn fr-reset" href="<?= e(url('/riwayat-aktivitas')) ?>"><?= card_icon('refresh-ccw', 'size-4') ?>Reset filter</a><?php endif; ?>
            </form>
            <?php if ($filters['error'] !== null): ?><p class="fr-filter-error" role="alert"><?= e($filters['error']) ?> Semua tanggal ditampilkan.</p><?php endif; ?>

            <?php if ($rows === []): ?>
            <div class="fr-empty"><?= card_icon('history', 'size-6') ?><b>Tidak ada catatan untuk filter ini</b><span>Ubah filter, atau <a href="<?= e(url('/riwayat-aktivitas')) ?>">reset filter</a>.</span></div>
            <?php else: ?>
            <div class="fr-table-wrap" role="region" aria-label="Tabel riwayat aktivitas" tabindex="0">
                <table class="fr-table">
                    <thead><tr><th scope="col">Waktu</th><th scope="col">Aksi</th><th scope="col">Objek</th><th scope="col">Rincian</th><th scope="col">IP · perangkat</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td class="fr-nowrap"><?= e($row['time']) ?></td>
                            <td class="fr-nowrap"><?= $chip($row) ?></td>
                            <td><b><?= e($row['entity']) ?></b><?php if ($row['object'] !== ''): ?><br><span class="fr-value"><?= e($row['object']) ?></span><?php endif; ?></td>
                            <td><?= $row['details'] === [] ? '<span class="fr-muted">—</span>' : '<span class="fr-value">' . e(implode("\n", $row['details'])) . '</span>' ?></td>
                            <td class="fr-nowrap"><?= e($row['ip'] !== '' ? $row['ip'] : '—') ?><?php if ($row['device'] !== ''): ?><br><span class="fr-muted"><?= e($row['device']) ?></span><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            <?php if ($pages > 1): ?>
            <nav class="fr-pager" aria-label="Halaman riwayat aktivitas">
                <?php if ($prevUrl !== null): ?><a class="dash-btn dash-btn--ghost dash-btn--sm" href="<?= e($prevUrl) ?>"><?= card_icon('chevron-left', 'size-4') ?>Sebelumnya</a><?php else: ?><span class="dash-btn dash-btn--ghost dash-btn--sm" aria-disabled="true"><?= card_icon('chevron-left', 'size-4') ?>Sebelumnya</span><?php endif; ?>
                <span>Halaman <?= (int) $filters['page'] ?> dari <?= (int) $pages ?></span>
                <?php if ($nextUrl !== null): ?><a class="dash-btn dash-btn--ghost dash-btn--sm" href="<?= e($nextUrl) ?>">Berikutnya<?= card_icon('chevron-right', 'size-4') ?></a><?php else: ?><span class="dash-btn dash-btn--ghost dash-btn--sm" aria-disabled="true">Berikutnya<?= card_icon('chevron-right', 'size-4') ?></span><?php endif; ?>
            </nav>
            <?php endif; ?>
        </section>
    </div>
</main>
