<?php
declare(strict_types=1);

namespace App\Services;

/**
 * One-time token of a form page (PRD FR-F3): issued when the page is drawn, accepted once. Sending the
 * same form again (a double tap, or Kirim after the browser's Back button) answers with the entry that
 * was already saved instead of saving a second one. Tokens live in the session (the last 50 per kind).
 */
final class FormToken
{
    private const KEEP = 50;

    public static function issue(string $form): string
    {
        $token = bin2hex(random_bytes(16));
        $issued = $_SESSION['form_tokens'][$form] ?? [];
        $issued[$token] = true;
        $_SESSION['form_tokens'][$form] = array_slice($issued, -self::KEEP, null, true);
        return $token;
    }

    /** 'new' (may be saved), 'used' (already saved: see usedFor()) or 'unknown' (not issued in this session). */
    public static function state(string $form, mixed $token): string
    {
        if (!is_string($token) || preg_match('/^[a-f0-9]{32}$/', $token) !== 1) {
            return 'unknown';
        }
        if (isset($_SESSION['form_tokens_used'][$form][$token])) {
            return 'used';
        }
        return isset($_SESSION['form_tokens'][$form][$token]) ? 'new' : 'unknown';
    }

    /** The answer of the request that used the token (e.g. the entry number). */
    public static function usedFor(string $form, string $token): array
    {
        return $_SESSION['form_tokens_used'][$form][$token] ?? [];
    }

    public static function markUsed(string $form, string $token, array $answer): void
    {
        unset($_SESSION['form_tokens'][$form][$token]);
        $used = $_SESSION['form_tokens_used'][$form] ?? [];
        $used[$token] = $answer;
        $_SESSION['form_tokens_used'][$form] = array_slice($used, -self::KEEP, null, true);
    }
}
