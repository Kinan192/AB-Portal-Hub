<?php
declare(strict_types=1);

namespace App\Services;

use ZipArchive;

/**
 * Checks of one uploaded file (PRD FR-F11), in this order: upload error and size, is_uploaded_file, the
 * extension against the field's allow-list, the file's first bytes (a renamed .exe is not a PDF), the
 * fileinfo MIME type when that extension is enabled, and for images getimagesize() (at most 6000 px).
 * SVG, HTML and Office macro files are never accepted.
 */
final class FileUpload
{
    public const TYPES = [
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'csv' => 'text/csv',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];
    /** MIME types fileinfo may report for each kind (CSV is plain text, XLSX a zip). */
    private const FINFO = [
        'pdf' => ['application/pdf'],
        'png' => ['image/png'],
        'jpg' => ['image/jpeg', 'image/pjpeg'],
        'csv' => ['text/plain', 'text/csv', 'application/csv', 'text/x-comma-separated-values', 'application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
    ];

    /**
     * $file is one entry of $_FILES. Returns ['empty' => true] when nothing was chosen, ['error' => message]
     * when the file is refused, or ['ext' => …, 'mime' => …] when it passes.
     * @param list<string> $extensions allowed extensions, lower case
     * @param callable(string): bool|null $isUploaded is_uploaded_file() (replaceable in tests)
     */
    public static function check(array $file, array $extensions, int $maxBytes, ?callable $isUploaded = null): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        $limit = self::megabytes($maxBytes);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return ['empty' => true];
        }
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            // Over upload_max_filesize of php.ini: name that limit when it is the smaller one.
            $server = $error === UPLOAD_ERR_INI_SIZE ? self::iniBytes((string) ini_get('upload_max_filesize')) : 0;
            return ['error' => $server > 0 && $server < $maxBytes
                ? 'Ukuran file melebihi batas server (' . self::megabytes($server) . ').'
                : "Ukuran file melebihi {$limit}."];
        }
        if ($error !== UPLOAD_ERR_OK) {
            return ['error' => 'File gagal diunggah. Coba lagi.'];
        }
        $path = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        if ($size > $maxBytes || ($path !== '' && is_file($path) && filesize($path) > $maxBytes)) {
            return ['error' => "Ukuran file melebihi {$limit}."];
        }
        if ($size <= 0) {
            return ['error' => 'File kosong. Pilih file lain.'];
        }
        if (!($isUploaded ?? 'is_uploaded_file')($path)) {
            return ['error' => 'File gagal diunggah. Coba lagi.'];
        }
        $unsupported = 'Format file tidak didukung. Gunakan ' . self::names($extensions) . '.';
        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, $extensions, true) || !isset(self::TYPES[$ext])) {
            return ['error' => $unsupported];
        }
        $kind = $ext === 'jpeg' ? 'jpg' : $ext;
        if (self::sniff($path) !== $kind) {
            return ['error' => $unsupported];
        }
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo !== false ? (string) finfo_file($finfo, $path) : '';
            if ($mime !== '' && !in_array($mime, self::FINFO[$kind], true)) {
                return ['error' => $unsupported];
            }
        }
        if ($kind === 'png' || $kind === 'jpg') {
            $image = @getimagesize($path);
            if ($image === false) {
                return ['error' => $unsupported];
            }
            if ($image[0] > 6000 || $image[1] > 6000) {
                return ['error' => 'Ukuran gambar maksimal 6000 × 6000 piksel.'];
            }
        }
        return ['ext' => $ext, 'mime' => self::TYPES[$ext]];
    }

    /** What the first bytes say a file is: pdf, png, jpg, xlsx, csv, or '' (anything else). */
    public static function sniff(string $path): string
    {
        $head = (string) @file_get_contents($path, false, null, 0, 16);
        if (str_starts_with($head, '%PDF-')) {
            return 'pdf';
        }
        if (str_starts_with($head, "\x89PNG\r\n\x1a\n")) {
            return 'png';
        }
        if (str_starts_with($head, "\xFF\xD8\xFF")) {
            return 'jpg';
        }
        if (str_starts_with($head, "PK\x03\x04")) {
            return self::isWorkbook($path) ? 'xlsx' : '';
        }
        // CSV: UTF-8 text without NUL bytes (an .exe, image or zip always has them or invalid UTF-8).
        $text = (string) @file_get_contents($path);
        if ($text !== '' && !str_contains($text, "\0") && mb_check_encoding($text, 'UTF-8')) {
            return 'csv';
        }
        return '';
    }

    /** "2M" → 2097152 (php.ini sizes). */
    public static function iniBytes(string $value): int
    {
        $value = trim($value);
        $number = (int) $value;
        return match (strtoupper(substr($value, -1))) {
            'G' => $number * 1073741824,
            'M' => $number * 1048576,
            'K' => $number * 1024,
            default => $number,
        };
    }

    /** 10485760 → "10 MB". */
    public static function megabytes(int $bytes): string
    {
        return rtrim(rtrim(number_format($bytes / 1048576, 1, ',', ''), '0'), ',') . ' MB';
    }

    /** An .xlsx is a zip with [Content_Types].xml and xl/workbook.xml (a macro workbook is .xlsm, refused by extension). */
    private static function isWorkbook(string $path): bool
    {
        if (class_exists(ZipArchive::class)) {
            $zip = new ZipArchive();
            if ($zip->open($path, ZipArchive::RDONLY) !== true) {
                return false;
            }
            $ok = $zip->locateName('[Content_Types].xml') !== false && $zip->locateName('xl/workbook.xml') !== false
                && $zip->locateName('xl/vbaProject.bin') === false;
            $zip->close();
            return $ok;
        }
        // Without the zip extension: the names are stored as plain text in the zip directory.
        $bytes = (string) @file_get_contents($path);
        return str_contains($bytes, '[Content_Types].xml') && str_contains($bytes, 'xl/workbook.xml') && !str_contains($bytes, 'vbaProject.bin');
    }

    /** ['pdf', 'csv', 'jpg', 'jpeg'] → "PDF, CSV, atau JPG"; ['jpg', 'png'] → "JPG atau PNG". */
    private static function names(array $extensions): string
    {
        $names = array_values(array_unique(array_map(static fn (string $e): string => strtoupper($e === 'jpeg' ? 'jpg' : $e), $extensions)));
        $last = array_pop($names);
        return match (count($names)) {
            0 => (string) $last,
            1 => $names[0] . ' atau ' . $last,
            default => implode(', ', $names) . ', atau ' . $last,
        };
    }
}
