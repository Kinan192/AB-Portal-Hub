<?php
declare(strict_types=1);

// Reference rows from the supplied HTML. Replace this array with Model results at integration.
return [
    'source' => 'reference',
    'default_period' => '2026-10',
    'per_page' => 7,
    'periods' => ['2026-10' => 'Oktober 2026', '2026-09' => 'September 2026', '2026-08' => 'Agustus 2026', '2026-07' => 'Juli 2026'],
    'records' => [
        ['id' => 'MON-SRV-20261015-004', 'at' => '2026-10-15T10:25:00+07:00', 'shift' => 'Shift Pagi', 'rack' => 'rack-1', 'status' => 'baik', 'cpu' => 'BAIK', 'monitor' => 'Normal / Jernih', 'environment' => 'AC: 19.5°C • UPS: 100% (Load 34%)', 'notes' => 'Semua LED disk hijau berkedip normal. Fan redundancy optimal.', 'inspector' => 'IT Botania', 'initials' => 'IB', 'nik' => '2021-088', 'verification' => 'Disetujui Ka. IT'],
        ['id' => 'MON-SRV-20261014-003', 'at' => '2026-10-14T20:10:00+07:00', 'shift' => 'Shift Malam', 'rack' => 'rack-2', 'status' => 'baik', 'cpu' => 'BAIK', 'monitor' => 'Normal / Jernih', 'environment' => 'AC: 19.8°C • UPS: 100%', 'notes' => 'Core Switch Cisco Catalyst normal, throughput antrean BPJS lancar.', 'inspector' => 'Rian Y.', 'initials' => 'RY', 'nik' => '2023-014', 'verification' => 'Disetujui Ka. IT'],
        ['id' => 'MON-SRV-20261014-002', 'at' => '2026-10-14T09:30:00+07:00', 'shift' => 'Shift Pagi', 'rack' => 'rack-1', 'status' => 'baik', 'cpu' => 'BAIK', 'monitor' => 'Normal / Jernih', 'environment' => 'AC: 20.0°C • UPS: 100%', 'notes' => 'Storage SAN Synology sinkron backup database harian RSAB.', 'inspector' => 'IT Botania', 'initials' => 'IB', 'nik' => '2021-088', 'verification' => 'Disetujui Ka. IT'],
        ['id' => 'MON-SRV-20261013-002', 'at' => '2026-10-13T21:05:00+07:00', 'shift' => 'Shift Malam', 'rack' => 'rack-2', 'status' => 'baik', 'cpu' => 'BAIK', 'monitor' => 'Normal / Jernih', 'environment' => 'AC: 19.6°C • UPS: 100%', 'notes' => 'Pembersihan debu filter rak depan & cek indikator PSU redundant.', 'inspector' => 'Fajar A.', 'initials' => 'FA', 'nik' => '2022-045', 'verification' => 'Disetujui Ka. IT'],
        ['id' => 'MON-SRV-20261008-001', 'at' => '2026-10-08T11:15:00+07:00', 'shift' => 'Shift Pagi • Catatan Maintenance', 'rack' => 'rack-2', 'status' => 'warning', 'cpu' => 'PERINGATAN (RESOLVED)', 'monitor' => 'Normal / Jernih', 'environment' => 'LED Kuning Fan Module 2', 'notes' => 'Kipas chassis berdebu, dilakukan reslot & pembersihan. Status kembali hijau normal pukul 12:00 WIB.', 'inspector' => 'IT Botania', 'initials' => 'IB', 'nik' => '2021-088', 'verification' => 'Disetujui Ka. IT'],
        ['id' => 'MON-SRV-20261005-001', 'at' => '2026-10-05T09:40:00+07:00', 'shift' => 'Shift Pagi', 'rack' => 'rack-1', 'status' => 'baik', 'cpu' => 'BAIK', 'monitor' => 'Normal / Jernih', 'environment' => 'AC: 19.4°C • UPS: 100%', 'notes' => 'Patch cable terpasang rapi dengan velcro label, indikator switch link 10Gbps aktif.', 'inspector' => 'IT Botania', 'initials' => 'IB', 'nik' => '2021-088', 'verification' => 'Disetujui Ka. IT'],
        ['id' => 'MON-SRV-20261001-001', 'at' => '2026-10-01T09:15:00+07:00', 'shift' => 'Shift Pagi • Awal Bulan', 'rack' => 'rack-1', 'status' => 'baik', 'cpu' => 'BAIK', 'monitor' => 'Normal / Jernih', 'environment' => 'AC: 19.7°C • UPS: 100%', 'notes' => 'Inspeksi komprehensif bulanan awal kuartal. Seluruh mesin virtual vSphere berjalan optimal.', 'inspector' => 'IT Botania', 'initials' => 'IB', 'nik' => '2021-088', 'verification' => 'Disetujui Ka. IT'],
    ],
];
