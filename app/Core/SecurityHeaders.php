<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Response headers for every page (PRD SEC-8, SEC-10). Scripts and styles only from this site (the views
 * have no inline <script> or style attribute); images may come from https sites (link-card icons).
 * HSTS is sent only when the portal runs on HTTPS.
 */
final class SecurityHeaders
{
    public const CSP = "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' https: data:; font-src 'self'; "
        . "connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'";

    public static function send(): void
    {
        header_remove('X-Powered-By');
        header('Content-Security-Policy: ' . self::CSP);
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: same-origin');
        header('Permissions-Policy: camera=(self), microphone=(), geolocation=(), payment=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cache-Control: no-store');
        if (self::https()) {
            header('Strict-Transport-Security: max-age=31536000');
        }
    }

    public static function https(): bool
    {
        $https = (string) ($_SERVER['HTTPS'] ?? '');
        return ($https !== '' && $https !== 'off') || str_starts_with((string) config('app')['url'], 'https://');
    }
}
