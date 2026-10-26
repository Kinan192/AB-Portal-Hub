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
];
