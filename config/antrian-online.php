<?php
declare(strict_types=1);

return [
    'crons' => [
        ['key' => 'cron_antrian', 'title' => 'CRON WAKTU ANTRIAN ONLINE', 'description' => 'Status eksekusi cron job pengiriman waktu tunggu task ID antrian poli/farmasi ke server BPJS Kesehatan.', 'good' => 'Berhasil kirim log data antrian ke web service BPJS tanpa kegagalan.', 'bad' => 'Gagal terkirim / timed out / service bridging terputus dari server pusat.'],
        ['key' => 'cron_bed', 'title' => 'CRON BED PERJAM MJKN', 'description' => 'Status pengiriman pembaruan otomatis kuota ketersediaan tempat tidur (Bed Management System) per jam ke Mobile JKN.', 'good' => 'Data sinkronisasi bed per jam sukses diperbarui di sistem MJKN.', 'bad' => 'Cron gagal dieksekusi / data bed mismatch / kuota tidak terdorong.'],
    ],
];
