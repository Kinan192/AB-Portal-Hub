<?php
declare(strict_types=1);

// Reference rows supplied in the HTML. Replace with Model results at backend integration.
return [
    'source' => 'reference',
    'default_period' => '2026-10',
    'per_page' => 7,
    'periods' => ['2026-10' => 'Oktober 2026', '2026-09' => 'September 2026', '2026-08' => 'Agustus 2026', '2026-07' => 'Juli 2026'],
    'records' => [
        ['id' => 1, 'at' => '2026-10-15T10:18:00+07:00', 'shift' => 'pagi', 'queue_sent' => true, 'queue_http' => 200, 'bed_sent' => true, 'tickets' => 482, 'tasks' => 1420, 'task_note' => '', 'attachment' => 'Cron_Log_1018.txt', 'notes' => 'Normal lancar, latency BPJS rata-rata 85 ms tanpa retry loop.', 'inspector' => 'IT Botania', 'inspector_role' => 'Superadmin'],
        ['id' => 2, 'at' => '2026-10-14T20:45:00+07:00', 'shift' => 'siang', 'queue_sent' => true, 'queue_http' => 200, 'bed_sent' => true, 'tickets' => 614, 'tasks' => 1980, 'task_note' => '', 'attachment' => 'Bed_Push_2000.png', 'notes' => 'Semua task ID IGD dan Poliklinik terdeliver sempurna ke BPJS.', 'inspector' => 'Ahmad Faisal', 'inspector_role' => 'IT'],
        ['id' => 3, 'at' => '2026-10-14T10:15:00+07:00', 'shift' => 'pagi', 'queue_sent' => false, 'queue_http' => 504, 'bed_sent' => true, 'tickets' => 395, 'tasks' => null, 'task_note' => 'Timeout 1 batch', 'attachment' => 'Trace_Timeout.log', 'notes' => 'Sempat timeout di jam 09:00, re-push manual berhasil pada 09:14 WIB.', 'inspector' => 'IT Botania', 'inspector_role' => 'Superadmin'],
        ['id' => 4, 'at' => '2026-10-13T20:30:00+07:00', 'shift' => 'siang', 'queue_sent' => true, 'queue_http' => 200, 'bed_sent' => true, 'tickets' => 530, 'tasks' => 1610, 'task_note' => '', 'attachment' => 'Bed_Snapshot.jpg', 'notes' => 'Kapasitas 148 TT ICU dan Rawat Inap sinkron dengan display MJKN.', 'inspector' => 'Ahmad Faisal', 'inspector_role' => 'IT'],
        ['id' => 5, 'at' => '2026-10-13T10:10:00+07:00', 'shift' => 'pagi', 'queue_sent' => true, 'queue_http' => 200, 'bed_sent' => true, 'tickets' => 422, 'tasks' => 1288, 'task_note' => '', 'attachment' => 'Antrian_1310.txt', 'notes' => 'Proses bridging cron berjalan otomatis via scheduler server 192.168.10.15.', 'inspector' => 'IT Botania', 'inspector_role' => 'Superadmin'],
        ['id' => 6, 'at' => '2026-10-12T20:35:00+07:00', 'shift' => 'siang', 'queue_sent' => true, 'queue_http' => 200, 'bed_sent' => true, 'tickets' => 578, 'tasks' => 1794, 'task_note' => '', 'attachment' => 'Cron_Oct12.log', 'notes' => 'Seluruh log terverifikasi. Tidak ditemukan penumpukan antrean bridging.', 'inspector' => 'Ahmad Faisal', 'inspector_role' => 'IT'],
        ['id' => 7, 'at' => '2026-10-12T10:20:00+07:00', 'shift' => 'pagi', 'queue_sent' => true, 'queue_http' => 200, 'bed_sent' => true, 'tickets' => 410, 'tasks' => 1215, 'task_note' => '', 'attachment' => 'Bed_Audit_Oct12.pdf', 'notes' => 'Integrasi stabil. Respons HTTP BPJS rata-rata 92 ms.', 'inspector' => 'IT Botania', 'inspector_role' => 'Superadmin'],
    ],
];
