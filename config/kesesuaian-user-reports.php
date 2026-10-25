<?php
declare(strict_types=1);

// Supplied reference content. Operational records will come from a Model during integration.
return [
    'source' => 'reference',
    'default_period' => '2026-10',
    'per_page' => 7,
    'periods' => ['2026-10' => 'Oktober 2026', '2026-09' => 'September 2026', '2026-08' => 'Agustus 2026', '2026-07' => 'Juli 2026'],
    'units' => ['rawat-inap' => 'Rawat Inap', 'rawat-jalan' => 'Rawat Jalan', 'farmasi' => 'Farmasi', 'ugd' => 'UGD', 'bedah' => 'Instalasi Bedah Sentral', 'lab' => 'Laboratorium', 'radiologi' => 'Radiologi', 'kasir' => 'Keuangan & Kasir'],
    'records' => [
        ['id' => 1, 'at' => '2026-10-15T10:18:00+07:00', 'shift' => 'Shift Pagi', 'username' => 'dr.rizky_spjp', 'name' => 'dr. Rizky Kurniawan, Sp.JP', 'unit' => 'rawat-jalan', 'unit_label' => 'Poli Spesialis Jantung (Rajal)', 'compliant' => true, 'notes' => 'Hak akses modul Poli Jantung & E-Prescription terkonfirmasi sesuai SPO klinis.', 'inspector' => 'IT Botania', 'inspector_role' => 'Superadmin'],
        ['id' => 2, 'at' => '2026-10-15T09:45:00+07:00', 'shift' => 'Shift Pagi', 'username' => 'nur.sri_ranap', 'name' => 'Sri Wahyuni, S.Kep', 'unit' => 'rawat-inap', 'unit_label' => 'Rawat Inap Lt. 3 (Bougenville)', 'compliant' => true, 'notes' => 'Sesuai otorisasi dinas perawat pelaksana ranap, role CPPT & asuhan keperawatan valid.', 'inspector' => 'IT Botania', 'inspector_role' => 'Superadmin'],
        ['id' => 3, 'at' => '2026-10-15T08:30:00+07:00', 'shift' => 'Shift Pagi', 'username' => 'farmasi_lantai2', 'name' => 'Staff Farmasi (Apt. Dian M.)', 'unit' => 'farmasi', 'unit_label' => 'Depo Farmasi Rajal', 'compliant' => false, 'notes' => 'Ditemukan hak verifikasi resep rawat inap aktif pada akun depo rajal (perlu revisi role matrix).', 'inspector' => 'Ahmad Faisal', 'inspector_role' => 'Staff IT Support'],
        ['id' => 4, 'at' => '2026-10-14T21:15:00+07:00', 'shift' => 'Shift Malam', 'username' => 'adm.dewi_ugd', 'name' => 'Dewi Sartika', 'unit' => 'ugd', 'unit_label' => 'Pendaftaran & Kasir UGD 24 Jam', 'compliant' => true, 'notes' => 'Otorisasi registrasi pasien darurat & bridging BPJS V-Claim terkonfigurasi tepat.', 'inspector' => 'IT Botania', 'inspector_role' => 'Superadmin'],
        ['id' => 5, 'at' => '2026-10-14T15:40:00+07:00', 'shift' => 'Shift Siang', 'username' => 'dr.hendra_bedah', 'name' => 'dr. Hendra Pratama, Sp.B', 'unit' => 'bedah', 'unit_label' => 'Instalasi Bedah Sentral (IBS)', 'compliant' => false, 'notes' => 'Akun masih memiliki hak akses billing kasir pasca mutasi dari kepanitiaan komite medik.', 'inspector' => 'IT Botania', 'inspector_role' => 'Superadmin'],
        ['id' => 6, 'at' => '2026-10-14T14:10:00+07:00', 'shift' => 'Shift Siang', 'username' => 'lab.analis1', 'name' => 'Bambang S., A.Md.AK', 'unit' => 'lab', 'unit_label' => 'Laboratorium Patologi Klinik', 'compliant' => true, 'notes' => 'Role LIS interkoneksi normal, otorisasi input nilai kritis lab sesuai jenjang klinis analis.', 'inspector' => 'Ahmad Faisal', 'inspector_role' => 'Staff IT Support'],
        ['id' => 7, 'at' => '2026-10-14T11:20:00+07:00', 'shift' => 'Shift Pagi', 'username' => 'kasir.mita', 'name' => 'Mita Lestari, S.E.', 'unit' => 'kasir', 'unit_label' => 'Kasir Sentral Rawat Jalan', 'compliant' => true, 'notes' => 'Otorisasi settlement invoice & kuitansi tunai/non-tunai sesuai limit kasir pelaksana.', 'inspector' => 'IT Botania', 'inspector_role' => 'Superadmin'],
    ],
];
