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

    <?php foreach ($groups as $categoryKey => $category): ?>
    <section aria-labelledby="section-<?= e($categoryKey) ?>" class="portal-category space-y-3 <?= $categoryKey === 'daily' ? 'pt-2' : ($categoryKey === 'systems' ? 'pb-8 pt-4' : 'pt-4') ?>">
        <h2 id="section-<?= e($categoryKey) ?>" class="flex items-center gap-2 text-[11.7px] font-bold uppercase tracking-wider text-slate-500">
            <?= icon($category['icon'], 'size-3.5 text-slate-400') ?><?= e($category['title']) ?>
        </h2>
        <?php if ($category['cards'] === []): ?>
            <p class="rounded-xl border border-dashed border-slate-300 p-6 text-base text-slate-600">Belum ada menu pada kategori ini.</p>
        <?php else: ?>
        <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-3 md:grid-cols-4 <?= $categoryKey === 'daily' ? 'lg:grid-cols-6' : 'lg:grid-cols-7' ?>">
            <?php foreach ($category['cards'] as $card) { require BASE_PATH . '/app/Views/components/portal-card.php'; } ?>
        </div>
        <?php endif; ?>
    </section>
    <?php endforeach; ?>
    <noscript><p class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Aktifkan JavaScript untuk menggunakan menu portal.</p></noscript>
</main>
