<?php
declare(strict_types=1);

// Form content only. Inspector identity and inspection records will come from the backend.
return [
    'unit' => 'Botania',
    'location' => 'Ruang Server IT',
    'period' => 'Inspeksi Harian (Shift Pagi)',
    'racks' => [
        ['value' => 'RAK SERVER 1', 'description' => 'Rack Utama (Server SIMRS, Database Oracle, Active Directory)', 'icon' => 'server', 'detail' => 'Power Redundant OK'],
        ['value' => 'RAK SERVER 2', 'description' => 'Rack Jaringan & Backup (Switch Core, Router Mikrotik, NAS Storage)', 'icon' => 'network', 'detail' => 'UPS Online Sync'],
    ],
    'conditions' => [
        [
            'key' => 'cpu', 'number' => 2, 'title' => 'Kondisi CPU',
            'description' => 'Status operasional unit pemrosesan server fisik, indikator LED, dan kipas pendingin',
            'good' => 'Indikator hijau, kipas normal, suhu terkontrol normal tanpa alarm',
            'bad' => 'Terdengar noise berlebih, amber LED, overheat, atau restart mendadak',
            'good_badge' => 'Normal Operasional', 'bad_badge' => 'Perlu Penanganan',
            'placeholder' => 'Masukkan keterangan kondisi CPU lainnya...',
        ],
        [
            'key' => 'monitor', 'number' => 3, 'title' => 'Kondisi Monitor Central Server',
            'description' => 'Pemeriksaan display monitor utama KVM / central console server di ruang data center',
            'good' => 'Layar menyala jernih, resolusi normal, sinyal HDMI/VGA stabil',
            'bad' => 'Layar blank, bergaris, flickering, kabel kendor, tidak ada output',
            'good_badge' => 'Jernih & Normal', 'bad_badge' => 'Trouble / Rusak',
            'placeholder' => 'Masukkan keterangan kondisi monitor server lainnya...',
        ],
    ],
    'quick_notes' => [
        ['label' => 'UPS Normal (100%)', 'text' => 'UPS berfungsi normal dengan baterai 100%'],
        ['label' => 'Suhu Ruangan 20°C', 'text' => 'Suhu ruangan server terjaga di 20°C'],
        ['label' => 'Kabel Rapi & Aman', 'text' => 'Kabel patch cord dan manajemen PDU tertata rapi'],
        ['label' => 'Switch Normal', 'text' => 'Semua port Switch Catalyst link up tanpa loss'],
    ],
];
