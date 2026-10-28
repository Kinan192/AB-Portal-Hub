<?php
declare(strict_types=1);

namespace App\Services;

use App\Helpers\Format;
use DateTimeImmutable;

/**
 * Answers of a dynamic form (B6): the server checks of a submitted form against the questions of its version
 * (the same rules and messages as public/assets/js/form-dinamis.js), and how a stored answer is shown in the
 * recap and the detail. Hidden questions (showIf) are not checked and not stored; requiredWhen makes a
 * question required when one of the listed answers is chosen.
 *
 * Input names (multipart): <id> for one value, <id>[] for checkboxes, <id>_lainnya for the "Lainnya" text,
 * files as <id> or <id>[] with <id>_hapus[] for kept files to drop when editing.
 * Stored answers: text, a number, option values (the "Lainnya" text instead of "__other"), a list for
 * checkboxes, and [{id, name}] for files.
 */
final class FormAnswers
{
    private const BLOCKS = ['section', 'title', 'image'];

    /**
     * @param list<array> $items questions of the version (blocks are skipped)
     * @param array<string, list<array>> $files uploads per question id (entries of $_FILES, one file each)
     * @param array<string, mixed> $existing answers of the entry being changed (for its kept files)
     * @return array{answers: array<string, mixed>, errors: array<string, string>, uploads: array<string, list<array>>, removed: list<int>}
     */
    public static function check(array $items, array $input, array $files, array $existing = [], ?callable $isUploaded = null): array
    {
        $questions = array_values(array_filter($items, static fn ($q): bool => is_array($q) && !in_array($q['type'] ?? '', self::BLOCKS, true)));
        $byId = [];
        foreach ($questions as $q) {
            $byId[$q['id']] = $q;
        }
        // Raw choices first: conditions read them.
        $raw = [];
        foreach ($questions as $q) {
            $raw[$q['id']] = $input[$q['id']] ?? null;
        }
        $answers = [];
        $errors = [];
        $uploads = [];
        $removed = [];
        foreach ($questions as $q) {
            if (!self::visible($q, $byId, $raw)) {
                continue;
            }
            $required = self::required($q, $byId, $raw);
            if ($q['type'] === 'file') {
                $result = self::files($q, $files[$q['id']] ?? [], $existing[$q['id']] ?? [], $input[$q['id'] . '_hapus'] ?? [], $required, $isUploaded);
                if (isset($result['error'])) {
                    $errors[$q['id']] = $result['error'];
                    continue;
                }
                $uploads[$q['id']] = $result['uploads'];
                $removed = [...$removed, ...$result['removed']];
                if ($result['kept'] !== []) {
                    $answers[$q['id']] = $result['kept'];
                }
                continue;
            }
            [$value, $message] = self::value($q, $input, $required);
            if ($message !== '') {
                $errors[$q['id']] = $message;
            } elseif ($value !== null) {
                $answers[$q['id']] = $value;
            }
        }
        return ['answers' => $answers, 'errors' => $errors, 'uploads' => $uploads, 'removed' => $removed];
    }

    /** showIf: shown when its source question is shown and has the answer (a missing source = always shown). */
    public static function visible(array $q, array $byId, array $raw, int $depth = 0): bool
    {
        $rule = $q['showIf'] ?? null;
        if (!is_array($rule) || !isset($byId[$rule['q']]) || $depth > 10) {
            return true;
        }
        return self::visible($byId[$rule['q']], $byId, $raw, $depth + 1) && self::matches($rule, $raw[$rule['q']] ?? null);
    }

    /** One shown answer: ['text', 'chip'?, 'tone'?, 'rows'?, 'files'?], or null when empty. */
    public static function display(array $q, mixed $value): ?array
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }
        switch ($q['type']) {
            case 'radio':
            case 'select':
            case 'checkbox':
                $values = is_array($value) ? $value : [$value];
                $labels = [];
                $red = false;
                foreach ($values as $v) {
                    $option = self::option($q, (string) $v);
                    $labels[] = $option !== null ? self::label($option) : (string) $v;
                    $red = $red || (is_array($option) && ($option['tone'] ?? null) === 'red');
                }
                $text = implode(', ', $labels);
                return $red ? ['text' => $text, 'chip' => $text, 'tone' => 'bad'] : ['text' => $text];
            case 'number':
                $text = rtrim(rtrim(number_format((float) $value, 6, ',', '.'), '0'), ',');
                return ['text' => $text . (isset($q['unit']) ? ' ' . $q['unit'] : '')];
            case 'date':
                return ['text' => Format::date((string) $value)];
            case 'rating':
                return ['text' => $value . ' dari ' . ($q['max'] ?? 5)];
            case 'file':
                $list = is_array($value) ? array_values(array_filter($value, 'is_array')) : [];
                return $list === [] ? null : ['text' => implode(', ', array_column($list, 'name')), 'files' => $list];
            case 'confirm':
                return ['text' => 'Dicentang'];
            case 'short':
                return ['text' => $value . (isset($q['unit']) ? ' ' . $q['unit'] : '')];
            default:
                return ['text' => (string) $value];
        }
    }

    /** True when a shown answer needs a look: an option marked red. */
    public static function attention(array $items, array $answers): bool
    {
        foreach ($items as $q) {
            if (!is_array($q) || !array_key_exists($q['id'] ?? '', $answers) || $q['type'] === 'confirm') {
                continue;
            }
            $shown = self::display($q, $answers[$q['id']]);
            if (in_array($shown['tone'] ?? '', ['warn', 'bad'], true)) {
                return true;
            }
        }
        return false;
    }

    /** The readable answers in one line, for the recap search. */
    public static function searchText(array $items, array $answers): string
    {
        $parts = [];
        foreach ($items as $q) {
            if (is_array($q) && array_key_exists($q['id'] ?? '', $answers)) {
                $shown = self::display($q, $answers[$q['id']]);
                if ($shown !== null) {
                    $parts[] = $shown['text'] . (isset($shown['rows']) ? ' ' . implode(' ', array_map(static fn (array $r): string => $r['label'] . ' ' . $r['value'], $shown['rows'])) : '');
                }
            }
        }
        return mb_substr(implode(' · ', $parts), 0, 20000);
    }

    /** @return array{0: mixed, 1: string} the stored value (null when empty) and the message ('' = fine) */
    private static function value(array $q, array $input, bool $required): array
    {
        $id = $q['id'];
        $in = $input[$id] ?? null;
        $empty = static fn (string $message = 'Pertanyaan ini wajib diisi.'): array => [null, $required ? $message : ''];
        switch ($q['type']) {
            case 'short':
            case 'paragraph':
                if (!is_string($in) && $in !== null) {
                    return [null, 'Jawaban tidak valid.'];
                }
                $text = self::text((string) $in, $q['type'] === 'paragraph');
                if ($text === '') {
                    return $empty();
                }
                $max = (int) ($q['maxlength'] ?? ($q['type'] === 'short' ? 1000 : 5000));
                if (mb_strlen($text) > $max) {
                    return [null, 'Jawaban maksimal ' . number_format($max, 0, ',', '.') . ' karakter.'];
                }
                return [$text, ''];
            case 'radio':
            case 'select':
                if (!is_string($in) || trim($in) === '') {
                    return $empty($q['type'] === 'select' ? 'Pilih salah satu jawaban.' : 'Pertanyaan ini wajib diisi.');
                }
                if ($in === '__other' && $q['type'] === 'radio' && isset($q['other'])) {
                    return self::other($input[$id . '_lainnya'] ?? null);
                }
                return self::option($q, $in) !== null ? [$in, ''] : [null, 'Pilih salah satu jawaban.'];
            case 'checkbox':
                $picked = is_array($in) ? array_values(array_unique(array_filter($in, 'is_string'))) : [];
                if ($picked === []) {
                    return $empty();
                }
                $values = [];
                foreach ($picked as $v) {
                    if ($v === '__other' && isset($q['other'])) {
                        [$other, $message] = self::other($input[$id . '_lainnya'] ?? null);
                        if ($message !== '') {
                            return [null, $message];
                        }
                        $values[] = $other;
                    } elseif (self::option($q, $v) !== null) {
                        $values[] = $v;
                    } else {
                        return [null, 'Pilih jawaban dari daftar.'];
                    }
                }
                return [$values, ''];
            case 'number':
                if (!is_string($in) || trim($in) === '') {
                    return $empty();
                }
                $text = trim($in);
                if (!empty($q['integer']) ? preg_match('/^\d{1,9}$/', $text) !== 1 : preg_match('/^-?\d{1,9}([.,]\d{1,6})?$/', $text) !== 1) {
                    return [null, !empty($q['integer']) ? ($q['rangeMessage'] ?? 'Isi dengan angka bulat.') : 'Isi dengan angka, mis. 21,5.'];
                }
                $number = (float) str_replace(',', '.', $text);
                if ((isset($q['min']) && $number < $q['min']) || (isset($q['max']) && $number > $q['max'])) {
                    return [null, $q['rangeMessage'] ?? 'Isi angka antara ' . ($q['min'] ?? '…') . ' dan ' . ($q['max'] ?? '…') . '.'];
                }
                return [floor($number) === $number && abs($number) < PHP_INT_MAX ? (int) $number : $number, ''];
            case 'date':
                if (!is_string($in) || $in === '') {
                    return $empty();
                }
                return self::moment($in, 'Y-m-d') ? [$in, ''] : [null, 'Tanggal tidak valid.'];
            case 'time':
                if (!is_string($in) || $in === '') {
                    return $empty();
                }
                return self::moment($in, 'H:i') ? [$in, ''] : [null, 'Jam tidak valid.'];
            case 'scale':
            case 'rating':
                if (!is_string($in) || $in === '') {
                    return $empty();
                }
                $min = $q['type'] === 'scale' ? (int) ($q['min'] ?? 1) : 1;
                $max = (int) ($q['max'] ?? 5);
                return ctype_digit($in) && (int) $in >= $min && (int) $in <= $max ? [(int) $in, ''] : [null, 'Pilih nilai dari ' . $min . ' sampai ' . $max . '.'];
            case 'confirm':
                return in_array($in, ['1', 'Ya', 'on', 'true'], true) ? ['Ya', ''] : $empty('Centang pernyataan untuk mengirim.');
            default:
                return [null, ''];
        }
    }

    /**
     * Files of one question: new uploads (checked), files kept from the entry, and those dropped.
     * @return array{uploads: list<array>, kept: list<array>, removed: list<int>}|array{error: string}
     */
    private static function files(array $q, array $files, mixed $existing, mixed $drop, bool $required, ?callable $isUploaded): array
    {
        $extensions = array_values(array_filter(array_map(static fn (string $e): string => strtolower(ltrim(trim($e), '.')), explode(',', (string) ($q['accept'] ?? '.pdf,.jpg,.jpeg,.png')))));
        $max = (int) ($q['maxMb'] ?? 10) * 1048576;
        $limit = !empty($q['multiple']) ? (int) ($q['maxFiles'] ?? 5) : 1;
        $dropIds = array_map('intval', is_array($drop) ? $drop : []);
        $existing = is_array($existing) ? array_values(array_filter($existing, 'is_array')) : [];
        $uploads = [];
        foreach ($files as $file) {
            $result = FileUpload::check($file, $extensions, $max, $isUploaded);
            if (!empty($result['empty'])) {
                continue;
            }
            if (isset($result['error'])) {
                return ['error' => $result['error']];
            }
            $uploads[] = ['tmp' => (string) $file['tmp_name'], 'name' => basename(str_replace('\\', '/', (string) $file['name'])), 'ext' => $result['ext'], 'mime' => $result['mime']];
        }
        // One file: a new upload replaces the kept one.
        $kept = $limit === 1 && $uploads !== [] ? [] : array_values(array_filter($existing, static fn (array $f): bool => !in_array((int) ($f['id'] ?? 0), $dropIds, true)));
        $removed = array_values(array_diff(array_map(static fn (array $f): int => (int) ($f['id'] ?? 0), $existing), array_map(static fn (array $f): int => (int) ($f['id'] ?? 0), $kept)));
        if (count($kept) + count($uploads) > $limit) {
            return ['error' => $limit === 1 ? 'Unggah 1 file saja.' : 'Maksimal ' . $limit . ' file.'];
        }
        if ($required && $kept === [] && $uploads === []) {
            return ['error' => 'Unggah berkas bukti.'];
        }
        return ['uploads' => $uploads, 'kept' => $kept, 'removed' => $removed];
    }

    private static function required(array $q, array $byId, array $raw): bool
    {
        if (!empty($q['required'])) {
            return true;
        }
        foreach ($q['requiredWhen'] ?? [] as $rule) {
            if (isset($byId[$rule['q']]) && self::visible($byId[$rule['q']], $byId, $raw) && self::matches($rule, $raw[$rule['q']] ?? null)) {
                return true;
            }
        }
        return false;
    }

    private static function matches(array $rule, mixed $value): bool
    {
        $wanted = $rule['values'] ?? [$rule['value'] ?? null];
        $values = is_array($value) ? $value : [$value];
        return array_intersect(array_filter($values, 'is_string'), array_filter($wanted, 'is_string')) !== [];
    }

    /** @return array{0: ?string, 1: string} */
    private static function other(mixed $text): array
    {
        $text = is_string($text) ? self::text($text, false) : '';
        if ($text === '') {
            return [null, 'Tuliskan jawaban untuk pilihan lainnya.'];
        }
        return mb_strlen($text) > 500 ? [null, 'Jawaban lainnya maksimal 500 karakter.'] : [$text, ''];
    }

    /** The option with this value (a string option is its own value), or null. */
    private static function option(array $q, string $value): string|array|null
    {
        foreach ($q['options'] ?? [] as $option) {
            if ((is_string($option) ? $option : ($option['value'] ?? null)) === $value) {
                return $option;
            }
        }
        return null;
    }

    private static function label(string|array $option): string
    {
        return is_string($option) ? $option : (string) ($option['label'] ?? '');
    }

    private static function moment(string $value, string $format): bool
    {
        $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
        return $date !== false && $date->format($format) === $value && (int) $date->format('Y') >= 1970;
    }

    private static function text(string $value, bool $multiline): string
    {
        $value = str_replace("\r\n", "\n", $value);
        $value = (string) preg_replace($multiline ? '/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/u' : '/[\x00-\x1f\x7f]+/u', $multiline ? '' : ' ', $value);
        return trim($multiline ? $value : (string) preg_replace('/\s+/u', ' ', $value));
    }
}
