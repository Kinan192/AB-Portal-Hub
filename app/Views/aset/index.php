<?php
/**
 * Aset IT menu (AssetController::index): the module's forms as dashboard cards (portal-card styling), each with
 * "Lihat Rekap" to the recap of its own data (like the dashboard's form cards), and the global recap.
 * No statistics here; they are on the recap pages.
 */
$cards = [
    'Formulir Aset' => [
        ['registrasi', 'Registrasi Perangkat', 'Daftarkan perangkat baru: pindai barcode, isi data dan spesifikasi, cetak label.', 'circle-plus', 'teal', 'rekap-perangkat'],
        ['penempatan', 'Penempatan Perangkat', 'Tentukan lokasi dan penanggung jawab perangkat yang sudah terdaftar.', 'map-pin', 'sky', 'rekap-perpindahan'],
        ['lacak', 'Lacak Perangkat', 'Lihat lokasi saat ini, riwayat perpindahan dan servis, lalu laporkan lokasi baru.', 'search', 'emerald', 'rekap-perpindahan'],
        ['servis', 'Servis & Pemeliharaan', 'Laporkan kerusakan atau pemeliharaan, perbarui progres hingga selesai.', 'wrench', 'amber', 'rekap-servis'],
        ['disposal', 'Disposal Perangkat', 'Ajukan, setujui, dan laksanakan penghapusan perangkat. Riwayat tetap diarsipkan.', 'archive', 'rose', 'rekap-disposal'],
    ],
    'Rekap & Laporan' => [
        ['rekap', 'Rekap Global', 'Ringkasan seluruh aset: KPI, grafik kategori dan lokasi, daftar perangkat, servis, disposal, dan perpindahan.', 'chart-column', 'purple', null],
        ['rekap-perangkat', 'Daftar Perangkat', 'Semua perangkat terdaftar per kategori dan lokasi, dengan filter dan ekspor CSV.', 'table-2', 'slate', null],
        ['rekap-servis', 'Riwayat Servis', 'Semua laporan servis dan pemeliharaan dari formulir Servis.', 'history', 'orange', null],
    ],
];
?>
<main id="main-content" class="page-shell flex-1 space-y-5 pb-12">
    <section aria-labelledby="aset-title" class="welcome-hero hero-pattern overflow-hidden rounded-2xl border border-[#0d594e] bg-brand-dark p-6 text-white shadow-lg sm:p-8">
        <p class="flex items-center gap-2 text-[11.7px] font-semibold uppercase tracking-wider text-teal-300/90"><span class="h-px w-3 shrink-0 bg-teal-300/90" aria-hidden="true"></span>IT Asset Management</p>
        <h1 id="aset-title" class="mt-1 font-serif text-3xl font-bold leading-tight tracking-tight sm:text-4xl">Aset IT</h1>
        <p class="pt-2 text-sm text-teal-100/90">Siklus perangkat keras: registrasi, penempatan, pelacakan, servis, hingga disposal.</p>
    </section>

    <?php foreach ($cards as $sectionTitle => $items): $sectionId = 'aset-sec-' . md5($sectionTitle); ?>
    <section aria-labelledby="<?= e($sectionId) ?>" class="portal-category space-y-3 pt-2">
        <div class="section-head">
            <h2 id="<?= e($sectionId) ?>" class="flex items-center gap-2 text-[11.7px] font-bold uppercase tracking-wider text-slate-500"><?= e($sectionTitle) ?></h2>
        </div>
        <div class="card-grid--forms grid grid-cols-1 gap-3.5">
            <?php foreach ($items as [$path, $title, $description, $icon, $tone, $rekap]): ?>
            <article class="portal-card portal-card--form">
                <span class="card-icon tone-<?= e($tone) ?>"><?= card_icon($icon) ?></span>
                <h3 class="portal-card-title"><a class="card-link" href="<?= e(url('/aset/' . $path)) ?>"><?= e($title) ?></a></h3>
                <p class="portal-card-description"><?= e($description) ?></p>
                <?php if ($rekap !== null): ?>
                <div class="card-foot">
                    <a class="card-rekap" href="<?= e(url('/aset/' . $rekap)) ?>" aria-label="<?= e('Lihat rekap ' . $title) ?>"><?= card_icon('chart-column', 'size-4') ?>Lihat Rekap</a>
                </div>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
</main>
