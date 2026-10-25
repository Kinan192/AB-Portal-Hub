<?php
declare(strict_types=1);

return [
    'reasons' => [
        'mutasi' => 'Karyawan mutasi unit tapi hak akses lama masih aktif',
        'sharing_account' => 'Terindikasi akun bersama (sharing user account)',
        'over_privileged' => 'Hak akses berlebih / bukan wewenangnya (Privilege Escalation)',
        'resign' => 'Karyawan resign / nonaktif masih dapat login',
        'lainnya' => 'Lainnya (perlu audit lanjutan unit terkait)',
    ],
    // Display content from the supplied HTML; replace with verified Model results later.
    'sample_source' => 'reference',
    'recent_samples' => [
        ['username' => 'dr.rizky_spjp', 'unit' => 'Poli Jantung', 'time' => '08:14', 'compliant' => true],
        ['username' => 'nur.sri_ranap', 'unit' => 'Rawat Inap Lt. 3', 'time' => '07:45', 'compliant' => true],
        ['username' => 'farmasi_lantai2', 'unit' => 'Farmasi Rajal', 'time' => '09:20', 'compliant' => false],
    ],
];
