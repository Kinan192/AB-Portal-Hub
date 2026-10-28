<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Models\Attachment;

/**
 * GET /berkas/{id}: an uploaded file, only for a signed-in session (RequireLogin) and only while it is not
 * deleted (PRD FR-F11). The type comes from the database; images and PDF open in the browser, every other
 * type (CSV, XLSX) is downloaded. The original name is sent only as the download name.
 */
final class FileController
{
    private const INLINE = ['application/pdf', 'image/png', 'image/jpeg'];

    public function show(array $params): void
    {
        $attachment = (new Attachment(Database::connection()))->find((int) $params['id']);
        $path = $attachment !== null ? Attachment::path($attachment) : null;
        if ($attachment === null || $path === null) {
            http_response_code(404);
            View::render('errors/design-404', ['pageTitle' => 'Berkas tidak ditemukan'], 'design');
            return;
        }
        $inline = in_array($attachment['mime_type'], self::INLINE, true);
        $name = (string) $attachment['original_name'];
        $ascii = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name) ?: 'berkas';
        header_remove('Content-Security-Policy');
        // Images open in a sandbox with nothing else allowed. Chrome's PDF viewer shows a blank page under
        // a sandbox policy, so a PDF goes without it (its scripts run only inside the browser's PDF viewer).
        if ($attachment['mime_type'] !== 'application/pdf') {
            header('Content-Security-Policy: sandbox; default-src \'none\'; img-src \'self\'; style-src \'unsafe-inline\'');
        }
        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($name));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($path);
    }
}
