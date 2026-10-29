<main id="main-content" class="page-shell flex-1 space-y-5 py-4">
    <div class="security-notice flex items-start gap-3 rounded-lg border border-[#a2e6df] bg-[#e6f7f5] px-4 py-2.5 text-[11.7px] leading-relaxed text-[#0d6e66] shadow-sm" role="note">
        <?= icon('shield', 'mt-0.5 size-4 shrink-0 text-teal-600') ?>
        <p><strong class="font-semibold">Terima kasih telah menjaga kerahasiaan data internal RS Awal Bros Botania.</strong> Sistem ini berisi informasi sensitif pasien, finansial, dan operasional rumah sakit. Mohon gunakan akses Anda dengan bijak dan segera laporkan ke IT apabila menemukan aktivitas yang mencurigakan.</p>
    </div>

    <section aria-labelledby="welcome-title" class="welcome-hero hero-pattern overflow-hidden rounded-2xl border border-[#0d594e] bg-brand-dark p-6 text-white shadow-lg sm:p-8 md:p-10">
        <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
            <div class="min-w-0 space-y-1">
                <p class="flex items-center gap-2 text-[11.7px] font-semibold uppercase tracking-wider text-teal-300/90"><span class="h-px w-3 shrink-0 bg-teal-300/90" aria-hidden="true"></span>RS Awal Bros Botania</p>
                <h1 id="welcome-title" class="font-serif text-3xl font-bold leading-tight tracking-tight sm:text-4xl lg:text-[42px]">Selamat Datang,<br><span class="text-teal-200">IT Botania</span></h1>
                <p class="pt-1 text-xs font-normal text-teal-200/80 sm:text-sm">Sistem Monitoring Terpadu</p>
            </div>

            <?php require BASE_PATH . '/app/Views/components/progress-summary.php'; ?>
        </div>
    </section>

<<<<<<< HEAD
=======
    <?php if ($exceptions !== []): ?>
    <section class="dash-attention" aria-labelledby="attention-title">
        <h2 id="attention-title" class="dash-attention__title"><?= card_icon('triangle-alert', 'size-3.5') ?>Perlu perhatian</h2>
        <ul class="dash-attention__list">
            <?php foreach ($exceptions as $item): ?>
            <li>
                <a class="dash-attention__item" href="<?= e($item['url']) ?>">
                    <span class="dash-attention__module"><?= e($item['module']) ?></span>
                    <span class="dash-attention__no"><?= e($item['entry_no']) ?></span>
                    <span class="dash-attention__when"><?= e($item['when']) ?></span>
                    <span class="dash-attention__chip dash-attention__chip--<?= e($item['tone']) ?>"><?= e($item['status']) ?></span>
                    <?= card_icon('chevron-right', 'size-4 dash-attention__go') ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

>>>>>>> 899d036f59ce2361fefc2c1a8249a7b80cbc5bb5
    <div id="dashboard-sections" class="space-y-5">
    <?php foreach ($groups as $index => $group):
        $sectionId = 'section-' . e((string) ($group['id'] ?? $group['slug']));
        $isForms = $group['kind'] === 'forms';
        // First paint uses the default filter "Jadwal Hari Ini" (dashboard.js redraws with the remembered one).
        $visible = array_values(array_filter($group['cards'], static fn (array $card): bool => !$card['hidden']));
        $isDue = static fn (array $card): bool => $card['status'] !== null && $card['status']['due'];
        // Counter of the section: the same numbers the hero ring adds up (App\Services\Progress).
        $dueCount = $sectionCounts[$group['id']]['due'] ?? 0;
        $doneCount = $sectionCounts[$group['id']]['done'] ?? 0;
<<<<<<< HEAD
        // "Jadwal Hari Ini" lists what is still to do: due today and not yet filled today ("Semua Form" shows the rest).
=======
>>>>>>> 899d036f59ce2361fefc2c1a8249a7b80cbc5bb5
        if ($isForms) {
            $group['cards'] = array_values(array_filter($group['cards'], static fn (array $card): bool => $card['hidden'] || ($isDue($card) && !$card['done_today'])));
        }
        $filteredEmpty = $isForms && $visible !== [] && array_filter($group['cards'], static fn (array $card): bool => !$card['hidden']) === [];
        $allDone = $dueCount > 0 && $doneCount >= $dueCount;
        $hiddenCount = count(array_filter($group['cards'], static fn (array $card): bool => $card['hidden']));
        $spacing = $index === 0 ? 'pt-2' : ($index === count($groups) - 1 ? 'pb-8 pt-4' : 'pt-4');
    ?>
    <section aria-labelledby="<?= $sectionId ?>" class="portal-category space-y-3 <?= $spacing ?>" data-section>
        <div class="section-head">
            <h2 id="<?= $sectionId ?>" class="flex items-center gap-2 text-[11.7px] font-bold uppercase tracking-wider text-slate-500">
                <?= card_icon($group['icon'], 'size-3.5 text-slate-400') ?><?= e($group['title']) ?>
            </h2>
            <?php if ($editable && ($hiddenCount > 0 || ($isForms && $showDialogs))): ?>
            <div class="section-actions">
                <?php if ($hiddenCount > 0): ?>
                <button type="button" class="section-textbtn" data-toggle-hidden aria-pressed="false" data-label-show="<?= e('Tampilkan ' . $hiddenCount . ' tersembunyi') ?>" data-label-hide="<?= e('Sembunyikan ' . $hiddenCount . ' tersembunyi') ?>">
                    <?= card_icon('eye', 'size-3.5') ?><span data-toggle-label><?= e('Tampilkan ' . $hiddenCount . ' tersembunyi') ?></span>
                </button>
                <?php endif; ?>
                <?php if ($isForms && $showDialogs): ?>
                <button type="button" class="dash-btn dash-btn--primary dash-btn--sm" data-open-add aria-haspopup="dialog"><?= card_icon('plus', 'size-4') ?>Tambah</button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php if ($isForms): ?>
        <div class="section-filter">
            <div class="section-seg" role="group" aria-label="Filter form">
                <button type="button" data-filter="today" aria-pressed="true">Jadwal Hari Ini</button>
                <button type="button" data-filter="all" aria-pressed="false">Semua Form</button>
            </div>
            <span class="section-count"><i aria-hidden="true"></i><b><?= e($dueCount . ' dari ' . count($visible) . ' form dijadwalkan hari ini') ?></b><?= $dueCount > 0 ? e(' · ' . $doneCount . ' selesai') : '' ?></span>
        </div>
        <?php endif; ?>
        <?php if ($filteredEmpty): ?>
        <div class="section-empty">
            <?= card_icon($allDone ? 'circle-check' : 'calendar-check', 'size-6 text-slate-400') ?>
            <b><?= $allDone ? 'Semua form hari ini sudah diisi' : 'Tidak ada form yang dijadwalkan hari ini' ?></b>
            <span><?= $allDone ? 'Form yang sudah diisi ada di tampilan Semua Form.' : 'Form lain tetap bisa dibuka dari tampilan Semua Form.' ?></span>
            <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" data-filter="all"><?= card_icon('list-checks', 'size-4') ?>Lihat Semua Form</button>
        </div>
        <?php if ($group['cards'] !== []): ?>
        <div class="card-grid--forms grid grid-cols-1 gap-3.5">
            <?php foreach ($group['cards'] as $card) { require BASE_PATH . '/app/Views/components/portal-card.php'; } ?>
        </div>
        <?php endif; ?>
        <?php elseif ($group['cards'] === []): ?>
        <div class="section-empty">
            <?= card_icon($isForms ? 'clipboard-list' : 'link', 'size-6 text-slate-400') ?>
            <b><?= $isForms ? 'Belum ada form di bagian ini' : 'Belum ada tautan di bagian ini' ?></b>
            <span><?= $isForms ? 'Buat form monitoring pertama untuk mulai mencatat.' : 'Tambahkan tautan lewat tombol + Tambah, tab Kategori & Tautan.' ?></span>
            <?php if ($editable && $group['custom']): ?>
            <button type="button" class="section-textbtn section-textbtn--danger" data-delete-section="<?= e((string) $group['id']) ?>"><?= card_icon('trash-2', 'size-3.5') ?>Hapus kategori ini</button>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <?php if ($hiddenCount === count($group['cards'])): ?>
        <p class="section-empty section-empty--compact" data-all-hidden>Semua kartu di bagian ini disembunyikan.</p>
        <?php endif; ?>
        <div class="<?= $isForms ? 'card-grid--forms grid grid-cols-1 gap-3.5' : 'grid grid-cols-2 gap-3.5 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7' ?>">
            <?php foreach ($group['cards'] as $card) { require BASE_PATH . '/app/Views/components/portal-card.php'; } ?>
        </div>
        <?php endif; ?>
    </section>
    <?php endforeach; ?>
    </div>
    <noscript><p class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Aktifkan JavaScript untuk menggunakan menu portal.</p></noscript>
</main>
<?php if ($editable): ?>
<div class="card-menu" id="card-menu" role="menu" data-editor-url="<?= e(url('/form-editor')) ?>" data-api-url="<?= e(rtrim(url('/dashboard'), '/')) ?>" hidden></div>
<template id="dash-icons"><?php foreach ($iconNames as $name): ?><span data-icon="<?= e($name) ?>"><?= card_icon($name) ?></span><?php endforeach; ?></template>
<script type="application/json" id="dashboard-state"><?= json_encode($clientState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<?php endif; ?>
<?php if ($showDialogs): ?>
<?php require BASE_PATH . '/app/Views/components/dashboard-dialogs.php'; ?>
<?php endif; ?>
<?php if ($editable): ?>
<?= partial('toast') ?>
<?php endif; ?>
