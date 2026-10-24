<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function config(string $name): array
{
    static $loaded = [];
    return $loaded[$name] ??= require BASE_PATH . '/config/' . $name . '.php';
}

function asset(string $path): string
{
    return config('app')['url'] . '/assets/' . ltrim($path, '/');
}

function date_wib(): string
{
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $days[(int) date('w')] . ', ' . date('j') . ' ' . $months[(int) date('n')] . ' ' . date('Y');
}

function icon(string $name, string $class = 'size-5'): string
{
    static $paths = [
        'server' => '<rect x="3" y="3" width="18" height="7" rx="2"/><rect x="3" y="14" width="18" height="7" rx="2"/><path d="M7 6.5h.01M7 17.5h.01"/>',
        'user-check' => '<path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2m13-11 2 2 4-4"/><circle cx="9.5" cy="7" r="4"/>',
        'thermometer' => '<path d="M14 14.8V4a2 2 0 0 0-4 0v10.8a4 4 0 1 0 4 0Z"/><path d="M12 9v8"/>',
        'video' => '<rect x="3" y="6" width="13" height="12" rx="2"/><path d="m16 10 5-3v10l-5-3"/>',
        'monitor' => '<rect x="3" y="4" width="18" height="13" rx="1"/><path d="M8 21h8m-4-4v4"/>',
        'ticket' => '<path d="M21 9V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v3a3 3 0 0 1 0 6v3a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1v-3a3 3 0 0 1 0-6Z"/><path d="M13 5v2m0 4v2m0 4v2"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a18 18 0 0 1 0 18 18 18 0 0 1 0-18Z"/>',
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'hospital' => '<path d="M3 21h18M5 21V7l8-4v18m0-14 6 4v10M9 9h.01M9 13h.01M9 17h.01M16 13h.01M16 17h.01"/>',
        'file' => '<path d="M14 3H5v18h14V8Zm0 0v5h5M8 12h8m-8 4h8"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="1"/><path d="m3 6 9 7 9-7"/>',
        'target' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
        'bot' => '<rect x="4" y="7" width="16" height="13" rx="3"/><path d="M12 3v4M2 12v4m20-4v4M9 12v1m6-1v1m-6 3h6"/><circle cx="12" cy="3" r="1"/>',
        'shield' => '<path d="m12 3 8 3v5c0 5-8 10-8 10S4 16 4 11V6Z"/><path d="m9 12 2 2 4-5"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18m-14 4h3m4 0h3m-10 3h3"/>',
        'chart' => '<path d="M12 3v9h9A9 9 0 1 1 12 3Z"/><path d="M16 3.8a9 9 0 0 1 4.2 4.2H16Z"/>',
        'lock' => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m20 0v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/><circle cx="9" cy="7" r="4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5M8 12h13"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'close' => '<path d="m6 6 12 12M6 18 18 6"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6m0-10h.01"/>',
        'home' => '<path d="m3 10 9-7 9 7v11H3Zm6 11v-8h6v8"/>',
        'network' => '<rect x="8" y="3" width="8" height="5" rx="1"/><rect x="2" y="16" width="6" height="5" rx="1"/><rect x="16" y="16" width="6" height="5" rx="1"/><path d="M12 8v4M5 16v-4h14v4"/>',
        'clipboard' => '<rect x="5" y="5" width="14" height="16" rx="2"/><rect x="9" y="3" width="6" height="4" rx="1"/><circle cx="12" cy="12" r="2"/><path d="M9 18v-1a3 3 0 0 1 6 0v1"/>',
        'location' => '<path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
        'cube' => '<path d="m12 3 9 5v9l-9 5-9-5V8Zm0 10v9M3 8l9 5 9-5M7.5 5.5l9 5V15"/>',
        'warning' => '<path d="m12 3 10 18H2Zm0 6v5m0 3h.01"/>',
        'arrow-left' => '<path d="m10 5-7 7 7 7M3 12h18"/>',
        'bookmark' => '<path d="M6 3h12v18l-6-4-6 4Z"/>',
        'send' => '<path d="m22 2-7 20-4-9-9-4Zm0 0L11 13"/>',
    ];
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['info']) . '</svg>';
}
