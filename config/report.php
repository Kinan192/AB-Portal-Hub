<?php
declare(strict_types=1);

// Exports of the recap pages (PRD §9, B8): Excel, PDF and Cetak.
return [
    'hospital' => 'RS Awal Bros Botania',
    'unit' => 'Instalasi Teknologi Informasi',
    // Signature block "Diketahui oleh, Kepala Instalasi IT" (decision Q15); empty = a blank line to write on.
    'head_of_it_name' => trim((string) (getenv('REPORT_HEAD_OF_IT_NAME') ?: '')),
    // Logo in the PDF header (decision Q15: the existing SVG).
    'logo' => BASE_PATH . '/public/assets/images/brand/rs-awal-bros.svg',
    // FR-E12: rows per export.
    'limits' => ['xlsx' => 50000, 'pdf' => 2000, 'cetak' => 2000],
];
