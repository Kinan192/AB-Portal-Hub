<?php
declare(strict_types=1);

// Question lists of the six built-in monitoring forms, keyed by their dashboard card id. Their pages
// (/form/suhu, /form/cctv, ...) are drawn from these lists by public/assets/js/form-dinamis.js and
// "Edit Isi Form" can change them (frontend only: the edited copy lives in the browser tab).
// The texts come from the former PHP form views.
//
// Besides the answer types of a dynamic form, built-in forms use these fixed types:
//   temperature  number in °C with a live status (bands below)
//   datetime     date and time, filled with the current WIB time
//   datetime2    separate date and time fields with a "Set Jam Sekarang" button
//   grid         rows × choices (each row one radio group)
//   confirm      the declaration checkbox (FR-F7)
// Options are a string or ['value', 'label', 'note', 'badge', 'tone' => 'red']. 'other' adds a free-text
// choice; 'showIf' shows a question only for one answer of another; 'requiredWhen' makes it required
// when any listed answer is chosen. Meta 'auto': now | today | month (filled in the browser, WIB).

$shifts = ['Shift Pagi (07:00 - 14:00)', 'Shift Siang (14:00 - 21:00)', 'Shift Malam (21:00 - 07:00)'];

return [
    'hardware-server' => [
        'title' => 'Form Monitoring Hardware Server IT',
        'description' => 'Pencatatan kondisi fisik dan operasional hardware server, rak, konsol CPU, display central, serta perangkat pendukung IT secara harian dan berkala.',
        'meta' => [
            ['label' => 'Periode Laporan', 'value' => 'Inspeksi Harian'],
            ['label' => 'Lokasi Ruang Server', 'value' => 'Ruang Server IT'],
            ['label' => 'Waktu Pencatatan', 'auto' => 'now'],
        ],
        'submitLabel' => 'Kirim Laporan Monitoring',
        'questions' => [
            ['id' => 'rak_server', 'type' => 'radio', 'title' => 'PILIH RAK SERVER', 'required' => true, 'default' => 'RAK SERVER 1',
                'help' => 'Pilih rack enclosure server yang sedang diinspeksi',
                'options' => [
                    ['value' => 'RAK SERVER 1', 'label' => 'RAK SERVER 1', 'note' => 'Rack Utama (Server SIMRS, Database Oracle, Active Directory) · 42U Enclosure · Power Redundant OK'],
                    ['value' => 'RAK SERVER 2', 'label' => 'RAK SERVER 2', 'note' => 'Rack Jaringan & Backup (Switch Core, Router Mikrotik, NAS Storage) · 42U Enclosure · UPS Online Sync'],
                ]],
            ['id' => 'kondisi_cpu', 'type' => 'radio', 'title' => 'KONDISI CPU', 'required' => true, 'default' => 'BAIK',
                'help' => 'Status operasional unit pemrosesan server fisik, indikator LED, dan kipas pendingin',
                'options' => [
                    ['value' => 'BAIK', 'label' => 'BAIK', 'note' => 'Indikator hijau, kipas normal, suhu terkontrol normal tanpa alarm', 'badge' => 'Normal Operasional'],
                    ['value' => 'TIDAK BAIK', 'label' => 'TIDAK BAIK', 'note' => 'Terdengar noise berlebih, amber LED, overheat, atau restart mendadak', 'badge' => 'Perlu Penanganan', 'tone' => 'red'],
                ],
                'other' => ['label' => 'Lainnya:', 'placeholder' => 'Masukkan keterangan kondisi CPU lainnya...']],
            ['id' => 'kondisi_monitor', 'type' => 'radio', 'title' => 'KONDISI MONITOR CENTRAL SERVER', 'required' => true, 'default' => 'BAIK',
                'help' => 'Pemeriksaan display monitor utama KVM / central console server di ruang data center',
                'options' => [
                    ['value' => 'BAIK', 'label' => 'BAIK', 'note' => 'Layar menyala jernih, resolusi normal, sinyal HDMI/VGA stabil', 'badge' => 'Jernih & Normal'],
                    ['value' => 'TIDAK BAIK', 'label' => 'TIDAK BAIK', 'note' => 'Layar blank, bergaris, flickering, kabel kendor, tidak ada output', 'badge' => 'Trouble / Rusak', 'tone' => 'red'],
                ],
                'other' => ['label' => 'Lainnya:', 'placeholder' => 'Masukkan keterangan kondisi monitor server lainnya...']],
            ['id' => 'catatan_hardware', 'type' => 'paragraph', 'title' => 'KONDISI HARDWARE DAN LAIN-LAIN', 'required' => false, 'maxlength' => 3000, 'rows' => 4,
                'help' => 'Catatan tambahan perangkat keras: UPS, AC Server, Kerapian Kabel (Patch Cord), PDU, Switch, dsb.',
                'placeholder' => 'Tuliskan catatan kondisi hardware lainnya secara terperinci (misal: kondisi UPS, temperatur ruang AC server, kebersihan rak, perangkat storage)...',
                'chips' => [
                    ['label' => '+ UPS Normal (100%)', 'text' => 'UPS berfungsi normal dengan baterai 100%'],
                    ['label' => '+ Suhu Ruangan 20°C', 'text' => 'Suhu ruangan server terjaga di 20°C'],
                    ['label' => '+ Kabel Rapi & Aman', 'text' => 'Kabel patch cord dan manajemen PDU tertata rapi'],
                    ['label' => '+ Switch Normal', 'text' => 'Semua port Switch Catalyst link up tanpa loss'],
                ]],
        ],
    ],

    'kesesuaian-user' => [
        'title' => 'Form Kesesuaian User (HIS)',
        'description' => 'Verifikasi sampel kesesuaian hak akses data sesuai kondisi lapangan di RS Awal Bros Botania.',
        'meta' => [['label' => 'Periode Verifikasi', 'auto' => 'today']],
        'submitLabel' => 'Kirim Verifikasi',
        'questions' => [
            ['id' => 'sec_user', 'type' => 'section', 'title' => 'Masukkan username yang dilakukan sampel penyesuaian login',
                'help' => 'Leveling User Account sesuai regulasi SPO RS Awal Bros Botania.', 'chip' => ''],
            ['id' => 'username', 'type' => 'short', 'title' => '1. Masukkan username HIS di sini', 'required' => true, 'badge' => 'Database ID / Akun SIMRS', 'maxlength' => 100,
                'help' => 'Ketik username akun SIMRS / HIS yang sedang diverifikasi sampelnya saat login sesi bertugas.',
                'placeholder' => 'Contoh: dr.anita / nur.siti / adm.budi'],
            ['id' => 'kesesuaian', 'type' => 'radio', 'title' => '2. Username sesuai regulasi?', 'required' => true, 'badge' => 'Validasi Otorisasi', 'default' => 'sesuai',
                'help' => 'Apakah data kredensial, peran modul, dan hak akses user sesuai dengan wewenang unit kerja aktif?',
                'options' => [
                    ['value' => 'sesuai', 'label' => 'Sesuai Regulasi', 'note' => 'Hak akses modul tepat sesuai unit & jabatan dinas.'],
                    ['value' => 'tidak_sesuai', 'label' => 'Tidak Sesuai Regulasi', 'note' => 'Ditemukan anomali modul, mutasi belum diperbarui, dll.', 'tone' => 'red'],
                ]],
            ['id' => 'alasan', 'type' => 'select', 'title' => 'Alasan / Catatan Ketidaksesuaian', 'required' => true,
                'showIf' => ['q' => 'kesesuaian', 'value' => 'tidak_sesuai'],
                'options' => [
                    ['value' => 'mutasi', 'label' => 'Karyawan mutasi unit tapi hak akses lama masih aktif'],
                    ['value' => 'sharing_account', 'label' => 'Terindikasi akun bersama (sharing user account)'],
                    ['value' => 'over_privileged', 'label' => 'Hak akses berlebih / bukan wewenangnya (Privilege Escalation)'],
                    ['value' => 'resign', 'label' => 'Karyawan resign / nonaktif masih dapat login'],
                    ['value' => 'lainnya', 'label' => 'Lainnya (perlu audit lanjutan unit terkait)'],
                ]],
            ['id' => 'catatan_tindak_lanjut', 'type' => 'paragraph', 'title' => 'Catatan spesifik tindak lanjut', 'required' => false, 'maxlength' => 1000, 'rows' => 2,
                'showIf' => ['q' => 'kesesuaian', 'value' => 'tidak_sesuai'],
                'requiredWhen' => [['q' => 'alasan', 'value' => 'lainnya']],
                'placeholder' => 'Catatan spesifik tindak lanjut...'],
            ['id' => 'waktu_login', 'type' => 'datetime2', 'title' => '3. Waktu login', 'required' => true,
                'help' => 'Waktu saat user melakukan autentikasi login pada sistem HIS (format 24 jam, WIB).'],
            ['id' => 'konfirmasi', 'type' => 'confirm', 'required' => true,
                'title' => 'Saya mengonfirmasi bahwa pengecekan sampel kesesuaian hak akses ini telah dilakukan secara valid sesuai regulasi melalui pengecekan terminal log / active session HIS.'],
        ],
    ],

    'suhu-server' => [
        'title' => 'Form Monitoring Rata - Rata Suhu Server Perbulan',
        'description' => 'Pencatatan dan verifikasi data tren suhu ruangan dan rak server data center IT RS Awal Bros Botania.',
        'meta' => [['label' => 'Periode Laporan', 'auto' => 'month']],
        'submitLabel' => 'Kirim Data Suhu',
        'questions' => [
            ['id' => 'sec_suhu', 'type' => 'section', 'title' => 'Data Pengukuran Suhu Server Bulanan', 'help' => '', 'chip' => ''],
            ['id' => 'periode', 'type' => 'select', 'title' => 'Periode Bulan Monitoring', 'required' => true, 'default' => '2026-10',
                'options' => [
                    ['value' => '2026-10', 'label' => 'Oktober 2026'], ['value' => '2026-09', 'label' => 'September 2026'],
                    ['value' => '2026-08', 'label' => 'Agustus 2026'], ['value' => '2026-07', 'label' => 'Juli 2026'], ['value' => '2026-06', 'label' => 'Juni 2026'],
                ]],
            ['id' => 'waktu', 'type' => 'datetime', 'title' => 'Waktu Pengambilan Data', 'required' => true],
            ['id' => 'suhu', 'type' => 'temperature', 'title' => 'RATA - RATA SUHU PERBULAN', 'required' => true, 'unit' => '°C (Celsius)',
                'badge' => 'Normal: 18,0 – 24,0 °C',
                'help' => 'Masukkan nilai rata-rata hasil logging sensor thermometer digital selama 1 bulan berjalan.',
                // PRD FR-T2 (assumption A-1, pending SOP confirmation).
                'bands' => ['low' => 18.0, 'high' => 24.0, 'critical' => 27.0]],
            ['id' => 'lokasi', 'type' => 'radio', 'title' => 'Lokasi / Titik Sensor Monitoring Suhu', 'required' => true, 'default' => 'dc-a',
                'options' => [
                    ['value' => 'dc-a', 'label' => 'Ruang Server Utama (Data Center A)', 'note' => 'Sensor ID: SNS-DCA-01'],
                    ['value' => 'rack-1', 'label' => 'Rak Server 1 (Core Switch & Storage)', 'note' => 'Sensor ID: SNS-RK1-04'],
                    ['value' => 'rack-2', 'label' => 'Rak Server 2 (Virtualization & DB)', 'note' => 'Sensor ID: SNS-RK2-09'],
                    ['value' => 'ups-room', 'label' => 'Ruang UPS & Backup Power', 'note' => 'Sensor ID: SNS-UPS-02'],
                ]],
            ['id' => 'berkas', 'type' => 'file', 'title' => 'Unggah Berkas Bukti / Laporan Suhu Sensor Perbulan', 'required' => true, 'maxMb' => 10,
                'help' => 'Unggah 1 berkas, maksimal 10 MB.', 'dropText' => 'Tarik & letakkan file log sensor di sini',
                'formats' => 'Mendukung format: PDF, CSV, XLSX, JPG/PNG (foto thermometer)', 'accept' => '.pdf,.csv,.xlsx,.jpg,.jpeg,.png'],
            ['id' => 'catatan_pac', 'type' => 'paragraph', 'title' => 'Catatan Operasional AC & Pendingin Ruangan (PAC)', 'required' => false, 'maxlength' => 2000,
                'placeholder' => 'Contoh: Unit AC Precision 1 beroperasi normal pada setpoint 20°C. Unit AC Precision 2 standby aktif. Filter udara dibersihkan pada tanggal 05 Oktober.'],
            ['id' => 'pernyataan', 'type' => 'confirm', 'required' => true,
                'title' => 'Saya menyatakan bahwa pencatatan rata-rata suhu server ini diambil dari data logger sensor resmi dan telah diverifikasi sesuai batas aman operasional data center RS Awal Bros Botania.'],
        ],
    ],

    'cctv' => [
        'title' => 'Form Monitoring CCTV & Kamera Pengawas',
        'description' => 'Pencatatan harian status kamera pengawas, kelayakan IP, dan kesesuaian sinkronisasi jam CCTV di lingkungan RS Awal Bros Botania.',
        'meta' => [
            ['label' => 'Periode Laporan', 'value' => 'Inspeksi Harian'],
            ['label' => 'Waktu & Tanggal Pemeriksaan', 'auto' => 'now'],
        ],
        'submitLabel' => 'Kirim Laporan CCTV',
        'questions' => [
            ['id' => 'sec_cctv', 'type' => 'section', 'title' => 'Data Pemeriksaan CCTV Harian', 'help' => 'Formulir Log Operasional Standar NOC IT Botania', 'chip' => ''],
            ['id' => 'cctv_rusak', 'type' => 'paragraph', 'title' => 'ISI CCTV YANG RUSAK DAN IP-NYA', 'required' => true, 'badge' => 'Log Unit & IP', 'rows' => 4, 'counter' => true,
                'help' => 'Masukkan nama titik kamera, nomor IP, dan kendala (contoh: CCTV-UGD-02 / 192.168.10.42 - Offline). Ketik "NIHIL" jika semua kamera normal.',
                'placeholder' => "Contoh:\n1. CCTV-UGD-02 (IP: 192.168.10.42) - Offline / Putus Jaringan\n2. CCTV-PARKIR-TIMUR (IP: 192.168.10.112) - Kamera buram terkena embun",
                'hint' => 'Format: [Nama Titik / Channel] / [Alamat IP] - [Gejala Kerusakan]'],
            ['id' => 'ntp_status', 'type' => 'radio', 'title' => 'KESESUAIAN JAM CCTV HARIAN', 'required' => true, 'badge' => 'NTP Synchronize', 'default' => 'sesuai',
                'help' => 'Pastikan jam internal NVR/DVR dan tampilan kamera CCTV tersinkronisasi presisi dengan master NTP Server RS Botania',
                'options' => [
                    ['value' => 'sesuai', 'label' => 'Sesuai', 'badge' => 'Optimal', 'note' => 'Sinkron NTP Server RS 172.24.10.2 / Toleransi < 5 detik pada seluruh rekaman channel.'],
                    ['value' => 'tidak_sesuai', 'label' => 'Tidak Sesuai / Selisih Waktu', 'badge' => 'Perlu Kalibrasi', 'tone' => 'red', 'note' => 'Perlu kalibrasi ulang waktu NVR karena selisih > 1 menit atau offset time zone berbeda.'],
                ],
                'other' => ['label' => 'Lainnya / Keterangan Khusus', 'placeholder' => 'Tuliskan keterangan detail deviasi waktu atau unit yang bermasalah...']],
            ['id' => 'storage_health', 'type' => 'radio', 'title' => 'STATUS & HEALTH CHECK NVR / STORAGE', 'required' => true, 'badge' => 'RAID / HDD Integrity', 'default' => 'normal',
                'help' => 'Kapasitas Penyimpanan dan ketahanan retensi data video rekaman (Standar Akreditasi KARS minimal 30 hari).',
                'options' => [
                    ['value' => 'normal', 'label' => 'Normal (Retensi > 30 Hari)', 'note' => 'Sisa ruang aman (> 20%), status hard disk OK, auto-overwrite berjalan.'],
                    ['value' => 'kritis', 'label' => 'Kapasitas Kritis (> 90%)', 'tone' => 'red', 'note' => 'Penyimpanan menipis, peringatan disk degraded atau resiko rekaman terpotong.'],
                ]],
            ['id' => 'bukti', 'type' => 'file', 'title' => 'UNGGAH BUKTI MATRIX / TAMPILAN CCTV ATAU DOKUMENTASI', 'required' => false, 'badge' => 'Opsional / Screenshot',
                'maxMb' => 10, 'multiple' => true, 'maxFiles' => 5,
                'dropText' => 'Tarik & letakkan tangkapan layar monitoring CCTV / foto fisik kamera',
                'formats' => 'Format didukung: JPG, PNG, PDF (maksimal 10 MB per file, 5 file)', 'accept' => '.jpg,.jpeg,.png,.pdf'],
            ['id' => 'catatan_shift', 'type' => 'paragraph', 'title' => 'CATATAN TINDAK LANJUT / SERAH TERIMA SHIFT', 'required' => false, 'rows' => 2, 'maxlength' => 2000,
                'placeholder' => 'Catatan operasional untuk serah terima shift atau nomor tiket vendor (contoh: Tiket Vendor Hikvision #HK-2026-103 penggantian adaptor port PoE UGD)...'],
            ['id' => 'konfirmasi', 'type' => 'confirm', 'required' => true,
                'title' => 'Saya menyatakan bahwa data pemeriksaan CCTV ini telah diverifikasi sesuai kondisi riil perangkat di lapangan dan mematuhi regulasi operasional IT RS Awal Bros Botania.'],
        ],
    ],

    'clear-desk' => [
        'title' => 'Form Monitoring Clear Desk & Clear Screen',
        'description' => 'Pencatatan kepatuhan keamanan informasi fisik, penataan meja kerja (Clear Desk) dan penguncian layar monitor (Clear Screen) di lingkungan RS Awal Bros Botania sesuai standar ISO 27001 dan keamanan data rekam medis elektronik.',
        'meta' => [
            ['label' => 'Periode Laporan', 'value' => 'Inspeksi Harian'],
            ['label' => 'Waktu Inspeksi', 'auto' => 'now'],
        ],
        'submitLabel' => 'Kirim Laporan Inspeksi',
        'questions' => [
            ['id' => 'sec_data', 'type' => 'section', 'title' => 'Data Pemeriksaan Clear Desk & Screen', 'help' => 'Formulir Log Kepatuhan Keamanan Fisik IT • Standar ISO/IEC 27001:2022 A.7.7', 'chip' => 'ISO 27001'],
            ['id' => 'shift', 'type' => 'select', 'title' => 'Shift Operasional', 'required' => false, 'default' => $shifts[0], 'options' => $shifts],
            ['id' => 'lokasi', 'type' => 'short', 'title' => 'LOKASI KOMPUTER', 'required' => true, 'badge' => 'Zona Pelayanan Pasien & Administrasi',
                'placeholder' => 'Contoh: Ruang Rekam Medis Lt. 1 / Meja Pendaftaran UGD / Ruang Poli Anak',
                'chipsLabel' => 'Pilihan Cepat:',
                'chips' => ['UGD & Triase', 'Poli Rawat Jalan', 'Farmasi Sentral', 'Laboratorium', 'Kasir / Billing', 'Ruang Server IT']],
            ['id' => 'mac', 'type' => 'short', 'title' => 'MAC ADDRESS', 'required' => true, 'badge' => 'NIC Hardware', 'mono' => true, 'format' => 'mac', 'maxlength' => 17,
                'help' => 'Format MAC Address 12 digit heksadesimal dengan pemisah titik dua.', 'placeholder' => '00:1A:2B:3C:4D:5E'],
            ['id' => 'ip', 'type' => 'short', 'title' => 'IP ADDRESS', 'required' => true, 'badge' => 'VLAN HIS Botania', 'mono' => true, 'format' => 'ipv4',
                'help' => 'Alamat IP lokal terminal kerja (Subnet 192.168.10.0/24).', 'placeholder' => '192.168.10.45'],
            ['id' => 'os', 'type' => 'radio', 'title' => 'SISTEM OPERASI', 'required' => true, 'default' => 'Windows 11 Pro',
                'options' => [
                    ['value' => 'Windows 11 Pro', 'label' => 'Windows 11 Pro', 'note' => 'Standar IT Hospital'],
                    ['value' => 'Windows 10 Enterprise', 'label' => 'Windows 10 Enterprise', 'note' => 'Legacy Workstation'],
                    ['value' => 'Linux Workstation', 'label' => 'Linux / Ubuntu Workstation', 'note' => 'Lab & PACS Thin Client'],
                    ['value' => 'macOS', 'label' => 'macOS Terminal', 'note' => 'Direksi / Manajemen'],
                    ['value' => 'Other', 'label' => 'Sistem Operasi Lainnya', 'note' => 'Embedded Alat Medis / Mesin Antrean'],
                ]],
            ['id' => 'sec_audit', 'type' => 'section', 'title' => 'Status Kepatuhan Audit Fisik', 'help' => 'Tentukan status evaluasi', 'chip' => ''],
            ['id' => 'audit', 'type' => 'grid', 'title' => 'Hasil pemeriksaan per aspek', 'required' => true, 'default' => 'compliant',
                'columns' => [['value' => 'compliant', 'label' => 'Patuh'], ['value' => 'non-compliant', 'label' => 'Temuan']],
                'rows' => [
                    ['id' => 'chk_desk', 'label' => 'Kepatuhan Clear Desk (Meja Bebas Dokumen Sensitif)', 'note' => 'Dokumen rahasia pasien, rekam medis fisik, formulir resep, dan password bertuliskan sticky note tidak tergeletak sembarangan saat meja ditinggalkan.'],
                    ['id' => 'chk_screen', 'label' => 'Kepatuhan Clear Screen (Penguncian Layar Monitor)', 'note' => 'Layar komputer terkunci otomatis (Win + L / Inactivity screen-lock timeout ≤ 3-5 menit) saat ditinggalkan oleh staf unit kerja.'],
                    ['id' => 'chk_media', 'label' => 'Keamanan Media Penyimpanan Eksternal', 'note' => 'Flashdisk, hard disk eksternal, atau token USB tidak tercolok di workstation tanpa izin resmi IT Security RS Awal Bros.'],
                ]],
            ['id' => 'foto', 'type' => 'file', 'title' => 'UNGGAH BUKTI FOTO MEJA / LAYAR (OPSIONAL)', 'required' => false, 'badge' => 'JPG, PNG, atau PDF (maks. 10 MB)',
                'maxMb' => 10, 'capture' => 'environment', 'accept' => '.jpg,.jpeg,.png,.pdf',
                'dropText' => 'Tarik & letakkan foto kondisi meja kerja atau layar komputer',
                'formats' => 'Atau klik tombol di bawah untuk memilih file dari perangkat Anda'],
            ['id' => 'catatan_temuan', 'type' => 'paragraph', 'title' => 'CATATAN TEMUAN / TINDAK LANJUT', 'required' => false, 'maxlength' => 2000,
                'placeholder' => 'Contoh: Diberikan kartu peringatan kepatuhan kepada staf loket 3; sticky note kredensial segera diamankan; sistem lock screen diatur ulang ke 3 menit.'],
            ['id' => 'deklarasi', 'type' => 'confirm', 'required' => true,
                'title' => 'Saya menyatakan bahwa pemeriksaan Clear Desk and Clear Screen ini telah diverifikasi sesuai kondisi fisik riil di unit kerja dan mematuhi regulasi ISO 27001 IT RS Awal Bros Botania.'],
        ],
    ],

    'antrian-online' => [
        'title' => 'Form Monitoring Antrian Online BPJS',
        'description' => 'Pencatatan harian dan verifikasi sinkronisasi pengiriman data cron antrian online serta cron ketersediaan bed per jam MJKN Mobile JKN BPJS Kesehatan.',
        'meta' => [
            ['label' => 'Periode Laporan', 'value' => 'Inspeksi Harian'],
            ['label' => 'Waktu & Tanggal', 'auto' => 'now'],
        ],
        'submitLabel' => 'Kirim Laporan Antrian',
        'questions' => [
            ['id' => 'sec_antrian', 'type' => 'section', 'title' => 'Data Verifikasi Antrian Online BPJS', 'help' => 'Formulir Log Operasional Bridging SIMRS & BPJS • Monitoring sinkronisasi harian', 'chip' => 'CRON AUDIT'],
            ['id' => 'cron_antrian', 'type' => 'radio', 'title' => 'CRON WAKTU ANTRIAN ONLINE', 'required' => true, 'default' => 'TERKIRIM',
                'help' => 'Status eksekusi cron job pengiriman waktu tunggu task ID antrian poli/farmasi ke server BPJS Kesehatan.',
                'options' => [
                    ['value' => 'TERKIRIM', 'label' => 'TERKIRIM', 'badge' => 'NORMAL', 'note' => 'Berhasil kirim log data antrian ke web service BPJS tanpa kegagalan.'],
                    ['value' => 'TIDAK_TERKIRIM', 'label' => 'TIDAK TERKIRIM', 'badge' => 'KRITIS', 'tone' => 'red', 'note' => 'Gagal terkirim / timed out / service bridging terputus dari server pusat.'],
                ]],
            ['id' => 'cron_bed', 'type' => 'radio', 'title' => 'CRON BED PERJAM MJKN', 'required' => true, 'default' => 'TERKIRIM',
                'help' => 'Status pengiriman pembaruan otomatis kuota ketersediaan tempat tidur (Bed Management System) per jam ke Mobile JKN.',
                'options' => [
                    ['value' => 'TERKIRIM', 'label' => 'TERKIRIM', 'badge' => 'NORMAL', 'note' => 'Data sinkronisasi bed per jam sukses diperbarui di sistem MJKN.'],
                    ['value' => 'TIDAK_TERKIRIM', 'label' => 'TIDAK TERKIRIM', 'badge' => 'KRITIS', 'tone' => 'red', 'note' => 'Cron gagal dieksekusi / data bed mismatch / kuota tidak terdorong.'],
                ]],
            ['id' => 'catatan', 'type' => 'paragraph', 'title' => 'CATATAN KENDALA / TINDAK LANJUT IT', 'required' => false, 'rows' => 4, 'maxlength' => 3000,
                'help' => 'Isi catatan bila ada cron yang tidak terkirim.',
                'requiredWhen' => [['q' => 'cron_antrian', 'value' => 'TIDAK_TERKIRIM'], ['q' => 'cron_bed', 'value' => 'TIDAK_TERKIRIM']],
                'placeholder' => 'Tuliskan kendala cron dan tindak lanjut yang dilakukan...'],
            ['id' => 'konfirmasi', 'type' => 'confirm', 'required' => true,
                'title' => 'Saya menyatakan bahwa pemeriksaan sinkronisasi Antrian Online BPJS ini telah diverifikasi sesuai kondisi log server riil RS Awal Bros Botania.'],
        ],
    ],
];
