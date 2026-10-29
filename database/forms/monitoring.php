<?php
declare(strict_types=1);

// The six monitoring forms of the portal as ordinary dynamic forms (D4): created by migration
// 2026_11_06_0011 on an existing dashboard and by the dashboard seed on a new one. After that they are
// edited like any other form ("Edit Isi Form"); this list is not read again.
// Each item: slug, card (title, description, icon, color), form (FormDefinition input), schedule.

$daily = ['type' => 'daily'];
$red = static fn (string $value, string $label): array => ['value' => $value, 'label' => $label, 'tone' => 'red'];
$ok = static fn (string $value, string $label): array => ['value' => $value, 'label' => $label];
$check = static fn (string $id, string $title): array => ['id' => $id, 'type' => 'radio', 'title' => $title, 'required' => true,
    'options' => [$ok('patuh', 'Patuh'), $red('temuan', 'Temuan')]];
$anyFinding = [['q' => 'chk_desk', 'value' => 'temuan'], ['q' => 'chk_screen', 'value' => 'temuan'], ['q' => 'chk_media', 'value' => 'temuan']];

return [
    [
        'slug' => 'hardware-server',
        'card' => ['title' => 'Hardware Server IT', 'description' => 'Status & pemeliharaan server IT', 'icon' => 'server', 'color' => 'emerald'],
        'form' => [
            'title' => 'Form Monitoring Hardware Server IT',
            'description' => 'Kondisi fisik hardware server dan perangkat pendukung di ruang server.',
            'questions' => [
                ['id' => 'rak_server', 'type' => 'radio', 'title' => 'Rak server', 'required' => true, 'options' => ['RAK SERVER 1', 'RAK SERVER 2']],
                ['id' => 'kondisi_cpu', 'type' => 'radio', 'title' => 'Kondisi CPU', 'required' => true,
                    'options' => [$ok('BAIK', 'Baik'), $red('TIDAK BAIK', 'Tidak baik')], 'other' => true],
                ['id' => 'kondisi_monitor', 'type' => 'radio', 'title' => 'Kondisi monitor central server', 'required' => true,
                    'options' => [$ok('BAIK', 'Baik'), $red('TIDAK BAIK', 'Tidak baik')], 'other' => true],
                ['id' => 'catatan_hardware', 'type' => 'paragraph', 'title' => 'Kondisi hardware dan lain-lain', 'required' => false, 'maxlength' => 3000, 'rows' => 4,
                    'help' => 'UPS, AC server, kabel, PDU, switch.'],
            ],
        ],
        'schedule' => $daily,
    ],
    [
        'slug' => 'kesesuaian-user',
        'card' => ['title' => 'Kesesuaian User', 'description' => 'Verifikasi & kesesuaian akses user', 'icon' => 'user-check', 'color' => 'teal'],
        'form' => [
            'title' => 'Form Kesesuaian User (HIS)',
            'description' => 'Sampel kesesuaian hak akses user HIS.',
            'questions' => [
                ['id' => 'username', 'type' => 'short', 'title' => 'Username HIS', 'required' => true, 'maxlength' => 100],
                ['id' => 'kesesuaian', 'type' => 'radio', 'title' => 'Username sesuai regulasi?', 'required' => true,
                    'options' => [$ok('sesuai', 'Sesuai regulasi'), $red('tidak_sesuai', 'Tidak sesuai regulasi')]],
                ['id' => 'alasan', 'type' => 'select', 'title' => 'Alasan ketidaksesuaian', 'required' => true,
                    'showIf' => ['q' => 'kesesuaian', 'value' => 'tidak_sesuai'],
                    'options' => [
                        $ok('mutasi', 'Karyawan mutasi unit, hak akses lama masih aktif'),
                        $ok('sharing_account', 'Akun dipakai bersama'),
                        $ok('over_privileged', 'Hak akses berlebih'),
                        $ok('resign', 'Karyawan resign/nonaktif masih bisa login'),
                        $ok('lainnya', 'Lainnya'),
                    ]],
                ['id' => 'catatan_tindak_lanjut', 'type' => 'paragraph', 'title' => 'Catatan tindak lanjut', 'required' => false, 'maxlength' => 1000, 'rows' => 2,
                    'showIf' => ['q' => 'kesesuaian', 'value' => 'tidak_sesuai'],
                    'requiredWhen' => [['q' => 'alasan', 'value' => 'lainnya']]],
                ['id' => 'tanggal_login', 'type' => 'date', 'title' => 'Tanggal login', 'required' => true],
                ['id' => 'jam_login', 'type' => 'time', 'title' => 'Jam login', 'required' => true],
                ['id' => 'konfirmasi', 'type' => 'confirm', 'required' => true,
                    'title' => 'Saya mengonfirmasi pengecekan sampel hak akses ini sudah dilakukan melalui log atau sesi aktif HIS.'],
            ],
        ],
        'schedule' => $daily,
    ],
    [
        'slug' => 'suhu-server',
        'card' => ['title' => 'Rata-rata Suhu Server Perbulan', 'description' => 'Tren suhu server bulanan', 'icon' => 'thermometer', 'color' => 'amber'],
        'form' => [
            'title' => 'Form Monitoring Rata-rata Suhu Server Perbulan',
            'description' => 'Rata-rata suhu dan kelembapan ruang dan rak server dalam satu bulan.',
            'questions' => [
                ['id' => 'lokasi', 'type' => 'radio', 'title' => 'Lokasi sensor', 'required' => true, 'options' => [
                    $ok('dc-a', 'Ruang Server Utama (Data Center A)'),
                    $ok('rack-1', 'Rak Server 1 (Core Switch & Storage)'),
                    $ok('rack-2', 'Rak Server 2 (Virtualization & DB)'),
                    $ok('ups-room', 'Ruang UPS & Backup Power'),
                ]],
                ['id' => 'tanggal', 'type' => 'date', 'title' => 'Tanggal pengambilan data', 'required' => true],
                ['id' => 'suhu', 'type' => 'number', 'title' => 'Rata-rata suhu', 'required' => true, 'unit' => '°C', 'min' => 0, 'max' => 60,
                    'help' => 'Normal 18–24 °C.'],
                ['id' => 'kelembapan', 'type' => 'number', 'title' => 'Rata-rata kelembapan', 'required' => false, 'unit' => '% RH', 'min' => 0, 'max' => 100,
                    'help' => 'Normal 45–60 % RH.'],
                ['id' => 'berkas', 'type' => 'file', 'title' => 'Berkas laporan sensor', 'required' => true, 'maxMb' => 10, 'accept' => '.pdf,.csv,.xlsx,.jpg,.jpeg,.png'],
                ['id' => 'catatan_pac', 'type' => 'paragraph', 'title' => 'Catatan AC & pendingin ruangan (PAC)', 'required' => false, 'maxlength' => 2000],
                ['id' => 'pernyataan', 'type' => 'confirm', 'required' => true,
                    'title' => 'Saya menyatakan data suhu ini diambil dari data logger sensor resmi.'],
            ],
        ],
        'schedule' => ['type' => 'monthly', 'month_day' => 1],
    ],
    [
        'slug' => 'cctv',
        'card' => ['title' => 'Monitoring CCTV', 'description' => 'Pemeriksaan harian kamera CCTV', 'icon' => 'video', 'color' => 'sky'],
        'form' => [
            'title' => 'Form Monitoring CCTV',
            'description' => 'Status kamera, jam, dan penyimpanan NVR.',
            'questions' => [
                ['id' => 'ada_rusak', 'type' => 'radio', 'title' => 'Ada CCTV yang rusak?', 'required' => true,
                    'options' => [$ok('tidak', 'Tidak, semua normal'), $red('ya', 'Ya')]],
                ['id' => 'zona', 'type' => 'checkbox', 'title' => 'Zona CCTV yang rusak', 'required' => true,
                    'showIf' => ['q' => 'ada_rusak', 'value' => 'ya'],
                    'options' => ['UGD & Triase', 'Rawat Inap Lt. 1-4', 'Farmasi Sentral & Kasir', 'Poliklinik Rawat Jalan', 'Area Parkir & Drop Zone'], 'other' => true],
                ['id' => 'masalah', 'type' => 'checkbox', 'title' => 'Masalah', 'required' => true,
                    'showIf' => ['q' => 'ada_rusak', 'value' => 'ya'],
                    'options' => ['Offline / putus jaringan', 'Buram / kaca berembun', 'Sudut bergeser', 'Gagal merekam'], 'other' => true],
                ['id' => 'cctv_rusak', 'type' => 'paragraph', 'title' => 'Nama kamera dan IP yang rusak', 'required' => true, 'maxlength' => 2000, 'rows' => 3,
                    'showIf' => ['q' => 'ada_rusak', 'value' => 'ya']],
                ['id' => 'ntp_status', 'type' => 'radio', 'title' => 'Kesesuaian jam CCTV', 'required' => true,
                    'options' => [$ok('sesuai', 'Sesuai'), $red('tidak_sesuai', 'Tidak sesuai / ada selisih')], 'other' => true],
                ['id' => 'storage_health', 'type' => 'radio', 'title' => 'Status NVR / storage', 'required' => true,
                    'options' => [$ok('normal', 'Normal'), $red('kritis', 'Kapasitas kritis (> 90%)')]],
                ['id' => 'retensi', 'type' => 'number', 'title' => 'Retensi rekaman', 'required' => true, 'unit' => 'hari', 'min' => 0, 'max' => 3650, 'integer' => true,
                    'help' => 'Minimal 30 hari.'],
                ['id' => 'bukti', 'type' => 'file', 'title' => 'Bukti tampilan CCTV', 'required' => false, 'maxMb' => 10, 'accept' => '.jpg,.jpeg,.png,.pdf', 'multiple' => true, 'maxFiles' => 5],
                ['id' => 'catatan', 'type' => 'paragraph', 'title' => 'Catatan tindak lanjut', 'required' => false, 'maxlength' => 2000, 'rows' => 2],
                ['id' => 'konfirmasi', 'type' => 'confirm', 'required' => true,
                    'title' => 'Saya menyatakan data pemeriksaan CCTV ini sesuai kondisi perangkat di lapangan.'],
            ],
        ],
        'schedule' => ['type' => 'daily', 'deadline' => '14:00', 'reminder' => true],
    ],
    [
        'slug' => 'clear-desk',
        'card' => ['title' => 'Clear Desk and Screen', 'description' => 'Inspeksi & kepatuhan clean desk', 'icon' => 'monitor', 'color' => 'slate'],
        'form' => [
            'title' => 'Form Monitoring Clear Desk & Clear Screen',
            'description' => 'Kepatuhan meja kerja dan penguncian layar per komputer.',
            'questions' => [
                ['id' => 'sec_komputer', 'type' => 'section', 'title' => 'Komputer'],
                ['id' => 'lokasi', 'type' => 'short', 'title' => 'Lokasi komputer', 'required' => true, 'maxlength' => 150],
                ['id' => 'ip', 'type' => 'short', 'title' => 'IP address', 'required' => true, 'maxlength' => 45],
                ['id' => 'mac', 'type' => 'short', 'title' => 'MAC address', 'required' => false, 'maxlength' => 17],
                ['id' => 'os', 'type' => 'radio', 'title' => 'Sistem operasi', 'required' => true,
                    'options' => ['Windows 11', 'Windows 10', 'Windows 7/8', 'Linux', 'macOS'], 'other' => true],
                ['id' => 'sec_hasil', 'type' => 'section', 'title' => 'Hasil pemeriksaan'],
                $check('chk_desk', 'Clear desk (meja bebas dokumen sensitif)'),
                $check('chk_screen', 'Clear screen (layar terkunci saat ditinggal)'),
                $check('chk_media', 'Media penyimpanan eksternal tidak tertinggal'),
                ['id' => 'kategori', 'type' => 'checkbox', 'title' => 'Kategori temuan', 'required' => false, 'requiredWhen' => $anyFinding,
                    'options' => ['Password tertulis/terlihat', 'Layar tidak terkunci', 'Dokumen sensitif di meja', 'Media penyimpanan tertinggal'], 'other' => true],
                ['id' => 'tindak_lanjut', 'type' => 'radio', 'title' => 'Tindak lanjut', 'required' => false, 'requiredWhen' => $anyFinding,
                    'options' => ['Ditegur di tempat', 'Dilaporkan ke atasan unit'], 'other' => true],
                ['id' => 'foto', 'type' => 'file', 'title' => 'Foto meja / layar', 'required' => false, 'maxMb' => 10, 'accept' => '.jpg,.jpeg,.png', 'capture' => 'environment'],
                ['id' => 'catatan_temuan', 'type' => 'paragraph', 'title' => 'Catatan', 'required' => false, 'maxlength' => 2000],
                ['id' => 'deklarasi', 'type' => 'confirm', 'required' => true,
                    'title' => 'Saya menyatakan pemeriksaan ini sesuai kondisi di unit kerja.'],
            ],
        ],
        'schedule' => $daily,
    ],
    [
        'slug' => 'antrian-online',
        'card' => ['title' => 'Antrian Online', 'description' => 'Status cron antrian & bed MJKN', 'icon' => 'ticket', 'color' => 'cyan'],
        'form' => [
            'title' => 'Form Monitoring Antrian Online BPJS',
            'description' => 'Pengiriman cron antrian online dan bed per jam ke BPJS / Mobile JKN.',
            'questions' => [
                ['id' => 'cron_antrian', 'type' => 'radio', 'title' => 'Cron waktu antrian online', 'required' => true,
                    'options' => [$ok('TERKIRIM', 'Terkirim'), $red('TIDAK_TERKIRIM', 'Tidak terkirim')]],
                ['id' => 'cron_bed', 'type' => 'radio', 'title' => 'Cron bed per jam MJKN', 'required' => true,
                    'options' => [$ok('TERKIRIM', 'Terkirim'), $red('TIDAK_TERKIRIM', 'Tidak terkirim')]],
                ['id' => 'catatan', 'type' => 'paragraph', 'title' => 'Kendala dan tindak lanjut', 'required' => false, 'maxlength' => 3000, 'rows' => 4,
                    'requiredWhen' => [['q' => 'cron_antrian', 'value' => 'TIDAK_TERKIRIM'], ['q' => 'cron_bed', 'value' => 'TIDAK_TERKIRIM']]],
                ['id' => 'konfirmasi', 'type' => 'confirm', 'required' => true,
                    'title' => 'Saya menyatakan pemeriksaan ini sesuai log server.'],
            ],
        ],
        'schedule' => $daily,
    ],
];
