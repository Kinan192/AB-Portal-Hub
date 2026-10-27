<?php
declare(strict_types=1);

// Sample reports of the six built-in forms for their history pages (/rekap/suhu, /rekap/cctv, ...),
// keyed by card id. 'at' is the send time in UTC (WIB = UTC+7); 'answers' are keyed by question id and
// hold what public/assets/js/form-dinamis.js stores: option labels, numbers, file names, and for a
// grid question a map of row label => column label. Reports sent in the browser are added to these.

$ya = 'Ya';

return [
    'suhu-server' => [
        ['id' => 'tmp-202610-0003', 'at' => '2026-10-24T03:05:00Z', 'answers' => ['periode' => 'Oktober 2026', 'waktu' => '2026-10-24T09:55', 'suhu' => 24.6, 'lokasi' => 'Rak Server 2 (Virtualization & DB)', 'berkas' => 'log-rk2-oktober-2026.csv', 'catatan_pac' => 'Beban host virtualisasi naik setelah migrasi VM; setpoint PAC diturunkan ke 19°C.', 'pernyataan' => $ya]],
        ['id' => 'tmp-202610-0002', 'at' => '2026-10-15T03:18:00Z', 'answers' => ['periode' => 'Oktober 2026', 'waktu' => '2026-10-15T10:10', 'suhu' => 22.8, 'lokasi' => 'Rak Server 1 (Core Switch & Storage)', 'berkas' => 'log-rk1-oktober-2026.pdf', 'pernyataan' => $ya]],
        ['id' => 'tmp-202610-0001', 'at' => '2026-10-15T02:40:00Z', 'answers' => ['periode' => 'Oktober 2026', 'waktu' => '2026-10-15T09:35', 'suhu' => 21.4, 'lokasi' => 'Ruang Server Utama (Data Center A)', 'berkas' => 'log-dca-oktober-2026.pdf', 'catatan_pac' => 'Unit AC Precision 1 normal pada setpoint 20°C. Unit 2 standby.', 'pernyataan' => $ya]],
        ['id' => 'tmp-202609-0004', 'at' => '2026-09-29T07:30:00Z', 'answers' => ['periode' => 'September 2026', 'waktu' => '2026-09-29T14:20', 'suhu' => 27.2, 'lokasi' => 'Ruang UPS & Backup Power', 'berkas' => 'foto-thermometer-ups.jpg', 'catatan_pac' => 'AC ruang UPS mati 3 jam; teknisi vendor sudah mengganti kapasitor.', 'pernyataan' => $ya]],
        ['id' => 'tmp-202609-0003', 'at' => '2026-09-28T02:15:00Z', 'answers' => ['periode' => 'September 2026', 'waktu' => '2026-09-28T09:05', 'suhu' => 23.1, 'lokasi' => 'Rak Server 2 (Virtualization & DB)', 'berkas' => 'log-rk2-september-2026.csv', 'pernyataan' => $ya]],
        ['id' => 'tmp-202609-0002', 'at' => '2026-09-28T02:05:00Z', 'answers' => ['periode' => 'September 2026', 'waktu' => '2026-09-28T08:58', 'suhu' => 17.6, 'lokasi' => 'Rak Server 1 (Core Switch & Storage)', 'berkas' => 'log-rk1-september-2026.pdf', 'catatan_pac' => 'Setpoint terlalu rendah setelah perawatan; dikembalikan ke 20°C.', 'pernyataan' => $ya]],
    ],

    'cctv' => [
        ['id' => 'cctv-202610-0042', 'at' => '2026-10-27T01:20:00Z', 'answers' => ['cctv_rusak' => 'NIHIL', 'ntp_status' => 'Sesuai', 'storage_health' => 'Normal (Retensi > 30 Hari)', 'konfirmasi' => $ya]],
        ['id' => 'cctv-202610-0041', 'at' => '2026-10-26T01:35:00Z', 'answers' => ['cctv_rusak' => "1. CCTV-UGD-02 (IP: 192.168.10.42) - Offline / Putus Jaringan\n2. CCTV-PARKIR-TIMUR (IP: 192.168.10.112) - Kamera buram terkena embun", 'ntp_status' => 'Sesuai', 'storage_health' => 'Normal (Retensi > 30 Hari)', 'bukti' => ['matrix-nvr-ugd.png', 'parkir-timur.jpg'], 'catatan_shift' => 'Tiket Vendor Hikvision #HK-2026-103 penggantian adaptor port PoE UGD.', 'konfirmasi' => $ya]],
        ['id' => 'cctv-202610-0040', 'at' => '2026-10-25T02:10:00Z', 'answers' => ['cctv_rusak' => '1. CCTV-UGD-02 (IP: 192.168.10.42) - Offline / Putus Jaringan', 'ntp_status' => 'Tidak Sesuai / Selisih Waktu', 'storage_health' => 'Normal (Retensi > 30 Hari)', 'catatan_shift' => 'Jam NVR-2 selisih 2 menit, sudah disinkronkan ulang ke NTP 172.24.10.2.', 'konfirmasi' => $ya]],
        ['id' => 'cctv-202610-0039', 'at' => '2026-10-24T01:05:00Z', 'answers' => ['cctv_rusak' => 'NIHIL', 'ntp_status' => 'Sesuai', 'storage_health' => 'Kapasitas Kritis (> 90%)', 'bukti' => ['nvr-storage-92persen.png'], 'catatan_shift' => 'HDD NVR-1 terisi 92%; pengadaan HDD 8 TB diajukan.', 'konfirmasi' => $ya]],
        ['id' => 'cctv-202610-0038', 'at' => '2026-10-23T01:25:00Z', 'answers' => ['cctv_rusak' => 'NIHIL', 'ntp_status' => 'Sesuai', 'storage_health' => 'Normal (Retensi > 30 Hari)', 'konfirmasi' => $ya]],
        ['id' => 'cctv-202610-0037', 'at' => '2026-10-22T01:40:00Z', 'answers' => ['cctv_rusak' => '1. CCTV-FARMASI-01 (IP: 192.168.10.67) - Sudut bergeser', 'ntp_status' => 'Sesuai', 'storage_health' => 'Normal (Retensi > 30 Hari)', 'catatan_shift' => 'Sudut kamera farmasi sudah diarahkan ulang ke loket.', 'konfirmasi' => $ya]],
    ],

    'clear-desk' => [
        ['id' => 'cds-202610-0058', 'at' => '2026-10-27T03:18:00Z', 'answers' => ['shift' => 'Shift Pagi (07:00 - 14:00)', 'lokasi' => 'UGD & Triase', 'mac' => '00:1A:2B:44:88:91', 'ip' => '192.168.10.45', 'os' => 'Windows 11 Pro', 'audit' => ['Kepatuhan Clear Desk (Meja Bebas Dokumen Sensitif)' => 'Patuh', 'Kepatuhan Clear Screen (Penguncian Layar Monitor)' => 'Patuh', 'Keamanan Media Penyimpanan Eksternal' => 'Patuh'], 'deklarasi' => $ya]],
        ['id' => 'cds-202610-0057', 'at' => '2026-10-27T03:05:00Z', 'answers' => ['shift' => 'Shift Pagi (07:00 - 14:00)', 'lokasi' => 'Kasir / Billing', 'mac' => '00:1A:2B:44:90:12', 'ip' => '192.168.10.61', 'os' => 'Windows 10 Enterprise', 'audit' => ['Kepatuhan Clear Desk (Meja Bebas Dokumen Sensitif)' => 'Temuan', 'Kepatuhan Clear Screen (Penguncian Layar Monitor)' => 'Patuh', 'Keamanan Media Penyimpanan Eksternal' => 'Patuh'], 'foto' => 'kasir-sticky-note.jpg', 'catatan_temuan' => 'Sticky note berisi password di monitor loket 3; sudah diamankan dan staf ditegur.', 'deklarasi' => $ya]],
        ['id' => 'cds-202610-0056', 'at' => '2026-10-26T08:40:00Z', 'answers' => ['shift' => 'Shift Siang (14:00 - 21:00)', 'lokasi' => 'Farmasi Sentral', 'mac' => '00:1A:2B:45:01:7C', 'ip' => '192.168.10.83', 'os' => 'Windows 11 Pro', 'audit' => ['Kepatuhan Clear Desk (Meja Bebas Dokumen Sensitif)' => 'Patuh', 'Kepatuhan Clear Screen (Penguncian Layar Monitor)' => 'Temuan', 'Keamanan Media Penyimpanan Eksternal' => 'Patuh'], 'catatan_temuan' => 'Layar tidak terkunci saat ditinggal; timeout lock screen diatur ke 3 menit.', 'deklarasi' => $ya]],
        ['id' => 'cds-202610-0055', 'at' => '2026-10-25T02:55:00Z', 'answers' => ['shift' => 'Shift Pagi (07:00 - 14:00)', 'lokasi' => 'Laboratorium', 'mac' => '00:1A:2B:46:22:0D', 'ip' => '192.168.10.102', 'os' => 'Linux / Ubuntu Workstation', 'audit' => ['Kepatuhan Clear Desk (Meja Bebas Dokumen Sensitif)' => 'Patuh', 'Kepatuhan Clear Screen (Penguncian Layar Monitor)' => 'Patuh', 'Keamanan Media Penyimpanan Eksternal' => 'Patuh'], 'deklarasi' => $ya]],
        ['id' => 'cds-202610-0054', 'at' => '2026-10-24T03:30:00Z', 'answers' => ['shift' => 'Shift Pagi (07:00 - 14:00)', 'lokasi' => 'Poli Rawat Jalan', 'mac' => '00:1A:2B:44:9A:3E', 'ip' => '192.168.10.74', 'os' => 'Windows 11 Pro', 'audit' => ['Kepatuhan Clear Desk (Meja Bebas Dokumen Sensitif)' => 'Patuh', 'Kepatuhan Clear Screen (Penguncian Layar Monitor)' => 'Patuh', 'Keamanan Media Penyimpanan Eksternal' => 'Temuan'], 'catatan_temuan' => 'Flashdisk pribadi tercolok di PC poli anak; dicabut dan dilaporkan ke atasan unit.', 'deklarasi' => $ya]],
        ['id' => 'cds-202610-0053', 'at' => '2026-10-23T14:10:00Z', 'answers' => ['shift' => 'Shift Malam (21:00 - 07:00)', 'lokasi' => 'Ruang Server IT', 'mac' => '00:1A:2B:40:00:01', 'ip' => '192.168.10.10', 'os' => 'Sistem Operasi Lainnya', 'audit' => ['Kepatuhan Clear Desk (Meja Bebas Dokumen Sensitif)' => 'Patuh', 'Kepatuhan Clear Screen (Penguncian Layar Monitor)' => 'Patuh', 'Keamanan Media Penyimpanan Eksternal' => 'Patuh'], 'deklarasi' => $ya]],
    ],

    'hardware-server' => [
        ['id' => 'hw-202610-0027', 'at' => '2026-10-27T01:10:00Z', 'answers' => ['rak_server' => 'RAK SERVER 1', 'kondisi_cpu' => 'BAIK', 'kondisi_monitor' => 'BAIK', 'catatan_hardware' => "UPS berfungsi normal dengan baterai 100%\nKabel patch cord dan manajemen PDU tertata rapi"]],
        ['id' => 'hw-202610-0026', 'at' => '2026-10-26T01:15:00Z', 'answers' => ['rak_server' => 'RAK SERVER 2', 'kondisi_cpu' => 'BAIK', 'kondisi_monitor' => 'TIDAK BAIK', 'catatan_hardware' => 'Monitor KVM flickering; kabel VGA diganti, perlu cek ulang besok.']],
        ['id' => 'hw-202610-0025', 'at' => '2026-10-25T01:05:00Z', 'answers' => ['rak_server' => 'RAK SERVER 1', 'kondisi_cpu' => 'Kipas PSU kedua berisik, sudah dibersihkan', 'kondisi_monitor' => 'BAIK', 'catatan_hardware' => 'Suhu ruangan server terjaga di 20°C']],
        ['id' => 'hw-202610-0024', 'at' => '2026-10-24T01:20:00Z', 'answers' => ['rak_server' => 'RAK SERVER 2', 'kondisi_cpu' => 'BAIK', 'kondisi_monitor' => 'BAIK', 'catatan_hardware' => 'Semua port Switch Catalyst link up tanpa loss']],
        ['id' => 'hw-202610-0023', 'at' => '2026-10-23T01:00:00Z', 'answers' => ['rak_server' => 'RAK SERVER 1', 'kondisi_cpu' => 'TIDAK BAIK', 'kondisi_monitor' => 'BAIK', 'catatan_hardware' => 'Amber LED di server Oracle DB; disk 3 rebuild RAID, selesai pukul 13.00.']],
    ],

    'kesesuaian-user' => [
        ['id' => 'his-202610-0031', 'at' => '2026-10-27T02:30:00Z', 'answers' => ['username' => 'dr.anita', 'kesesuaian' => 'Sesuai Regulasi', 'waktu_login' => '2026-10-27 08:12', 'konfirmasi' => $ya]],
        ['id' => 'his-202610-0030', 'at' => '2026-10-26T03:10:00Z', 'answers' => ['username' => 'nur.siti', 'kesesuaian' => 'Tidak Sesuai Regulasi', 'alasan' => 'Karyawan mutasi unit tapi hak akses lama masih aktif', 'catatan_tindak_lanjut' => 'Akses modul rawat inap dicabut, diganti modul farmasi.', 'waktu_login' => '2026-10-26 09:45', 'konfirmasi' => $ya]],
        ['id' => 'his-202610-0029', 'at' => '2026-10-25T02:00:00Z', 'answers' => ['username' => 'adm.budi', 'kesesuaian' => 'Sesuai Regulasi', 'waktu_login' => '2026-10-25 07:58', 'konfirmasi' => $ya]],
        ['id' => 'his-202610-0028', 'at' => '2026-10-24T04:20:00Z', 'answers' => ['username' => 'kasir03', 'kesesuaian' => 'Tidak Sesuai Regulasi', 'alasan' => 'Terindikasi akun bersama (sharing user account)', 'catatan_tindak_lanjut' => 'Dibuatkan akun per orang untuk 3 kasir shift pagi.', 'waktu_login' => '2026-10-24 10:05', 'konfirmasi' => $ya]],
        ['id' => 'his-202610-0027', 'at' => '2026-10-23T02:45:00Z', 'answers' => ['username' => 'perawat.rina', 'kesesuaian' => 'Sesuai Regulasi', 'waktu_login' => '2026-10-23 08:30', 'konfirmasi' => $ya]],
        ['id' => 'his-202610-0026', 'at' => '2026-10-22T06:15:00Z', 'answers' => ['username' => 'lab.hendra', 'kesesuaian' => 'Tidak Sesuai Regulasi', 'alasan' => 'Lainnya (perlu audit lanjutan unit terkait)', 'catatan_tindak_lanjut' => 'Akses hasil radiologi tidak sesuai jabatan; diteruskan ke Kepala Instalasi Laboratorium.', 'waktu_login' => '2026-10-22 13:02', 'konfirmasi' => $ya]],
    ],

    'antrian-online' => [
        ['id' => 'bpjs-202610-0027', 'at' => '2026-10-27T01:30:00Z', 'answers' => ['cron_antrian' => 'TERKIRIM', 'cron_bed' => 'TERKIRIM', 'konfirmasi' => $ya]],
        ['id' => 'bpjs-202610-0026', 'at' => '2026-10-26T01:45:00Z', 'answers' => ['cron_antrian' => 'TIDAK TERKIRIM', 'cron_bed' => 'TERKIRIM', 'catatan' => 'Cron antrian pukul 09:00 timeout karena maintenance server BPJS pusat; sudah dikirim ulang manual.', 'konfirmasi' => $ya]],
        ['id' => 'bpjs-202610-0025', 'at' => '2026-10-25T01:20:00Z', 'answers' => ['cron_antrian' => 'TERKIRIM', 'cron_bed' => 'TERKIRIM', 'konfirmasi' => $ya]],
        ['id' => 'bpjs-202610-0024', 'at' => '2026-10-24T01:35:00Z', 'answers' => ['cron_antrian' => 'TERKIRIM', 'cron_bed' => 'TIDAK TERKIRIM', 'catatan' => 'Data bed mismatch di ruang ICU; mapping kelas kamar diperbaiki, cron jalan normal pukul 10:00.', 'konfirmasi' => $ya]],
        ['id' => 'bpjs-202610-0023', 'at' => '2026-10-23T01:25:00Z', 'answers' => ['cron_antrian' => 'TERKIRIM', 'cron_bed' => 'TERKIRIM', 'konfirmasi' => $ya]],
        ['id' => 'bpjs-202610-0022', 'at' => '2026-10-22T01:15:00Z', 'answers' => ['cron_antrian' => 'TERKIRIM', 'cron_bed' => 'TERKIRIM', 'konfirmasi' => $ya]],
    ],
];
