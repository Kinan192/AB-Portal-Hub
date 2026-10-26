<?php
declare(strict_types=1);

// Reference rows supplied in the HTML. Replace with Model results at backend integration.
return [
    'source' => 'reference',
    'default_period' => '2026-10',
    'per_page' => 7,
    'periods' => ['2026-10' => 'Oktober 2026', '2026-09' => 'September 2026', '2026-08' => 'Agustus 2026', '2026-07' => 'Juli 2026'],
    'records' => [
        ['id' => 1, 'at' => '2026-10-15T10:18:00+07:00', 'queue_sent' => true, 'queue_http' => 200, 'bed_sent' => true, 'notes' => 'Normal lancar, latency BPJS rata-rata 85 ms tanpa retry loop.', 'inspector' => 'IT Botania', 'inspector_role' => 'Superadmin'],
        ['id' => 2, 'at' => '2026-10-14T20:45:00+07:00', 'queue_sent' => true, 'queue_http' => 200, 'bed_sent' => true, 'notes' => 'Semua task ID IGD dan Poliklinik terdeliver sempurna ke BPJS.', 'inspector' => 'Ahmad Faisal', 'inspector_role' => 'IT'],
        ['id' => 3, 'at' => '2026-10-14T10:15:00+07:00', 'queue_sent' => false, 'queue_http' => 504, 'bed_sent' => true, 'notes' => 'Sempat timeout di jam 09:00, re-push manual berhasil pada 09:14 WIB.', 'inspector' => 'IT Botania', 'inspector_role' => 'Superadmin'],
        ['id' => 4, 'at' => '2026-10-13T20:30:00+07:00', 'queue_sent' => true, 'queue_http' => 200, 'bed_sent' => true, 'notes' => 'Kapasitas 148 TT ICU dan Rawat Inap sinkron dengan display MJKN.', 'inspector' => 'Ahmad Faisal', 'inspector_role' => 'IT'],
        ['id' => 5, 'at' => '2026-10-13T10:10:00+07:00', 'queue_sent' => true, 'queue_http' => 200, 'bed_sent' => true, 'notes' => 'Proses bridging cron berjalan otomatis via scheduler server 192.168.10.15.', 'inspector' => 'IT Botania', 'inspector_role' => 'Superadmin'],
        ['id' => 6, 'at' => '2026-10-12T20:35:00+07:00', 'queue_sent' => true, 'queue_http' => 200, 'bed_sent' => true, 'notes' => 'Seluruh log terverifikasi. Tidak ditemukan penumpukan antrean bridging.', 'inspector' => 'Ahmad Faisal', 'inspector_role' => 'IT'],
        ['id' => 7, 'at' => '2026-10-12T10:20:00+07:00', 'queue_sent' => true, 'queue_http' => 200, 'bed_sent' => true, 'notes' => 'Integrasi stabil. Respons HTTP BPJS rata-rata 92 ms.', 'inspector' => 'IT Botania', 'inspector_role' => 'Superadmin'],
    ],
];
