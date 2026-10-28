<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Checks of the dashboard dialogs on the server (the same rules and messages as dashboard.js):
 * "Tambah" Form tab / "Edit Kartu", Kategori & Tautan tab / "Edit Tautan", and a new category.
 * Each check returns ['values' => cleaned values, 'errors' => [field => message]], keyed by the
 * dialog's field names so the browser can show each message under its field.
 */
final class CardInput
{
    public const URL_MESSAGE = 'Gunakan alamat lengkap yang diawali http:// atau https://';
    public const IMAGE_MESSAGE = 'Isi URL gambar yang diawali https://';

    /**
     * An address a card may open: http or https with a host, no spaces or control characters, at
     * most 500 characters. javascript:, data:, file: and relative addresses are refused.
     * Card images must be https (the Content-Security-Policy allows https images only).
     */
    public static function safeUrl(mixed $value, bool $httpsOnly = false): bool
    {
        if (!is_string($value) || $value === '' || strlen($value) > 500 || preg_match('/[\x00-\x20\x7f\\\\]/', $value) === 1) {
            return false;
        }
        if (preg_match($httpsOnly ? '#^https://#i' : '#^https?://#i', $value) !== 1) {
            return false;
        }
        $parts = parse_url($value);
        return is_array($parts) && ($parts['host'] ?? '') !== '';
    }

    /** One line of text: trimmed, runs of white space (and control characters) become one space. */
    public static function line(mixed $value): string
    {
        return is_string($value) ? trim((string) preg_replace('/[\s\x00-\x1f\x7f]+/u', ' ', $value)) : '';
    }

    /** Several lines: trimmed, control characters other than line breaks and tabs removed. */
    public static function lines(mixed $value): string
    {
        return is_string($value) ? trim((string) preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/u', '', str_replace("\r\n", "\n", $value))) : '';
    }

    /** "Tambah" Form tab and "Edit Kartu". @param list<string> $icons @param list<string> $colors */
    public static function formCard(array $input, array $icons, array $colors): array
    {
        $values = [
            'title' => self::line($input['title'] ?? ''),
            'description' => self::line($input['description'] ?? ''),
            'keterangan' => self::lines($input['keterangan'] ?? ''),
            'icon' => is_string($input['icon'] ?? null) ? $input['icon'] : '',
            'color' => is_string($input['color'] ?? null) ? $input['color'] : '',
        ];
        $errors = [];
        if ($values['title'] === '') {
            $errors['title'] = 'Judul Form wajib diisi.';
        } elseif (mb_strlen($values['title']) > 60) {
            $errors['title'] = 'Judul Form maksimal 60 karakter.';
        }
        if (mb_strlen($values['description']) > 60) {
            $errors['description'] = 'Deskripsi maksimal 60 karakter.';
        }
        if (mb_strlen($values['keterangan']) > 500) {
            $errors['keterangan'] = 'Keterangan maksimal 500 karakter.';
        }
        if (!in_array($values['icon'], $icons, true)) {
            $errors['icon'] = 'Pilih icon dari daftar.';
        }
        if (!in_array($values['color'], $colors, true)) {
            $errors['color'] = 'Pilih warna.';
        }
        return ['values' => $values, 'errors' => $errors];
    }

    /**
     * Kategori & Tautan tab and "Edit Tautan" (the category itself is checked by the controller).
     * icon_source 'list' keeps the list icon, 'url' shows the https image (the list icon stays as its
     * fallback when the image cannot be loaded).
     */
    public static function link(array $input, array $icons): array
    {
        $source = ($input['icon_source'] ?? 'list') === 'url' ? 'url' : 'list';
        $values = [
            'title' => self::line($input['title'] ?? ''),
            'description' => self::line($input['description'] ?? ''),
            'url' => is_string($input['url'] ?? null) ? trim($input['url']) : '',
            'icon' => is_string($input['icon'] ?? null) && $input['icon'] !== '' ? $input['icon'] : 'link',
            'image_url' => $source === 'url' && is_string($input['icon_url'] ?? null) ? trim($input['icon_url']) : null,
        ];
        $errors = [];
        if ($values['title'] === '') {
            $errors['title'] = 'Judul wajib diisi.';
        } elseif (mb_strlen($values['title']) > 40) {
            $errors['title'] = 'Judul maksimal 40 karakter.';
        }
        if (mb_strlen($values['description']) > 60) {
            $errors['description'] = 'Deskripsi maksimal 60 karakter.';
        }
        if (!self::safeUrl($values['url'])) {
            $errors['url'] = self::URL_MESSAGE;
        }
        if (!in_array($values['icon'], $icons, true)) {
            $errors['icon'] = 'Pilih icon dari daftar.';
        }
        if ($source === 'url' && !self::safeUrl($values['image_url'], httpsOnly: true)) {
            $errors['icon_url'] = self::IMAGE_MESSAGE;
        }
        return ['values' => $values, 'errors' => $errors];
    }

    /** A new link category: upper-case name, unique (ignoring case). @param list<string> $taken */
    public static function category(array $input, array $icons, array $taken): array
    {
        $title = mb_strtoupper(self::line($input['title'] ?? ''));
        $icon = is_string($input['icon'] ?? null) ? $input['icon'] : '';
        $errors = [];
        if ($title === '') {
            $errors['category_title'] = 'Nama kategori wajib diisi.';
        } elseif (mb_strlen($title) > 32) {
            $errors['category_title'] = 'Nama kategori maksimal 32 karakter.';
        } elseif (in_array($title, array_map('mb_strtoupper', $taken), true)) {
            $errors['category_title'] = 'Nama kategori sudah dipakai.';
        }
        if (!in_array($icon, $icons, true)) {
            $errors['category_icon'] = 'Pilih icon kategori.';
        }
        return ['values' => ['title' => $title, 'icon' => $icon], 'errors' => $errors];
    }
}
