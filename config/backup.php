<?php
declare(strict_types=1);

// Nightly backup and restore (B9, PRD NFR-4, NFR-5, decision Q19): bin/backup.php and bin/restore.php.
return [
    // Backup sets are written here; keep it outside the project folder (and outside public/).
    'dir' => rtrim((string) (App\Core\Env::get('BACKUP_DIR') ?: dirname(BASE_PATH) . DIRECTORY_SEPARATOR . 'AB-Portal-Hub-backups'), '\\/'),
    // Optional second location on another machine or NAS (NFR-5), e.g. \\nas\backup\abportal. Empty = none.
    'copy_dir' => rtrim((string) (App\Core\Env::get('BACKUP_COPY_DIR') ?: ''), '\\/'),
    'keep' => max(1, (int) (App\Core\Env::get('BACKUP_KEEP') ?: 30)),
    // Optional AES-256 key (base64 of 32 bytes; make one with: php bin/backup.php key). Empty = not encrypted.
    'key' => trim((string) (App\Core\Env::get('BACKUP_ENCRYPTION_KEY') ?: '')),
    // Folder of mysqldump and mysql, e.g. C:\xampp\mysql\bin. Empty = found on PATH.
    'mysql_bin' => rtrim((string) (App\Core\Env::get('MYSQL_BIN_DIR') ?: ''), '\\/'),
    // Uploaded files (FR-F11) that go into every backup.
    'files' => BASE_PATH . '/storage/app/private',
    // Application logs older than this are removed by the nightly run (NFR-13).
    'log_keep_days' => max(1, (int) (App\Core\Env::get('LOG_KEEP_DAYS') ?: 90)),
    // Warning in the log when the backup disk is this full (NFR-13).
    'disk_warn_percent' => 80,
];
