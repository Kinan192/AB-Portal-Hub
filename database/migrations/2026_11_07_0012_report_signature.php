<?php
declare(strict_types=1);

// D5: the signature block of a form's PDF and Cetak export ("Dibuat oleh" / "Diketahui oleh") is set on its
// recap page (app/Services/ReportSignature.php). One new nullable column; NULL = the default texts.
// Undo (`php bin/migrate.php down`): the column is dropped, so saved signature texts are lost.

return [
    'up' => static function (PDO $pdo): void {
        $exists = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'forms' AND COLUMN_NAME = 'report_signature_json'")->fetchColumn();
        if ((int) $exists === 0) {
            $pdo->exec('ALTER TABLE forms ADD COLUMN report_signature_json TEXT NULL');
        }
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('ALTER TABLE forms DROP COLUMN report_signature_json');
    },
];
