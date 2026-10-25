<?php
declare(strict_types=1);

return [
    'endpoint' => 'https://apicall.bpjs-kesehatan.go.id/vclaim-rest/',
    'shifts' => [
        'pagi' => ['label' => 'Shift Pagi (07:00–14:00)', 'short' => 'Shift Pagi'],
        'siang' => ['label' => 'Shift Siang (14:00–21:00)', 'short' => 'Shift Siang'],
        'malam' => ['label' => 'Shift Malam (21:00–07:00)', 'short' => 'Shift Malam'],
    ],
    'crons' => [
        ['key' => 'cron_antrian', 'title' => 'CRON WAKTU ANTRIAN ONLINE', 'description' => 'Status eksekusi cron job pengiriman waktu tunggu task ID antrian poli/farmasi ke server BPJS Kesehatan.', 'good' => 'Berhasil kirim log data antrian ke web service BPJS tanpa kegagalan.', 'bad' => 'Gagal terkirim / timed out / service bridging terputus dari server pusat.'],
        ['key' => 'cron_bed', 'title' => 'CRON BED PERJAM MJKN', 'description' => 'Status pengiriman pembaruan otomatis kuota ketersediaan tempat tidur (Bed Management System) per jam ke Mobile JKN.', 'good' => 'Data sinkronisasi bed per jam sukses diperbarui di sistem MJKN.', 'bad' => 'Cron gagal dieksekusi / data bed mismatch / kuota tidak terdorong.'],
    ],
    // Editable initial values supplied by the reference, not live BPJS measurements.
    'tickets' => [
        'poli_spesialis' => ['label' => 'Poli Spesialis', 'initial' => 284],
        'poli_umum' => ['label' => 'Poli Umum', 'initial' => 112],
        'farmasi' => ['label' => 'Farmasi', 'initial' => 68],
        'radiologi' => ['label' => 'Radiologi', 'initial' => 18],
    ],
];
