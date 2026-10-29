<?php
declare(strict_types=1);

// Exports of the recap pages (PRD §9, B8): Excel, PDF and Cetak.
return [
    'hospital' => 'RS Awal Bros Botania',
    'unit' => 'Instalasi Teknologi Informasi',
    // Signature block "Diketahui oleh, Kepala Instalasi IT" (decision Q15); empty = a blank line to write on.
<<<<<<< HEAD
    'head_of_it_name' => trim((string) (App\Core\Env::get('REPORT_HEAD_OF_IT_NAME') ?: '')),
=======
    'head_of_it_name' => trim((string) (getenv('REPORT_HEAD_OF_IT_NAME') ?: '')),
>>>>>>> 899d036f59ce2361fefc2c1a8249a7b80cbc5bb5
    // Logo in the PDF header (decision Q15: the existing SVG).
    'logo' => BASE_PATH . '/public/assets/images/brand/rs-awal-bros.svg',
    // FR-E12: rows per export.
    'limits' => ['xlsx' => 50000, 'pdf' => 2000, 'cetak' => 2000],
];
