<?php
declare(strict_types=1);

namespace App\Services;

/**
 * The question list of a dynamic form as "Edit Isi Form" sends it (public/assets/js/form-editor.js), checked
 * and cleaned before it becomes a new form version. Only known answer types and settings are kept; texts
 * are trimmed and limited; conditions (showIf, requiredWhen) that point at a question that no longer exists
 * are dropped (the fill-in page shows such a question always). A preselected answer ("default") is never kept
 * (C2: every answer starts empty). Nothing here is trusted for HTML: the pages escape every text when drawing.
 */
final class FormDefinition
{
    /** Answer types of a dynamic form (editable in the editor). */
    public const TYPES = ['short', 'paragraph', 'radio', 'checkbox', 'select', 'file', 'date', 'time', 'scale', 'number', 'rating'];
    /** The declaration checkbox ("Pernyataan"), kept as its own answer type. */
    public const FIXED = ['confirm'];
    /** Items without an answer. */
    public const BLOCKS = ['section', 'title', 'image'];
    public const MAX_ITEMS = 200;
    private const CHOICE = ['radio', 'checkbox', 'select'];
    private const ID = '/^[A-Za-z0-9_-]{1,64}$/';
    private const FILE_TYPES = ['pdf', 'jpg', 'jpeg', 'png', 'csv', 'xlsx'];

    /**
     * @return array{definition: array{title: string, description: string, questions: list<array>}, errors: array<string, string>}
     *   errors by 'title', 'description' or 'questions' (the message names the item number)
     */
    public static function check(mixed $form): array
    {
        $form = is_array($form) ? $form : [];
        $errors = [];
        $title = self::text($form['title'] ?? '', 150);
        if ($title === '') {
            $errors['title'] = 'Isi judul form.';
        } elseif (mb_strlen(self::raw($form['title'])) > 150) {
            $errors['title'] = 'Judul form maksimal 150 karakter.';
        }
        $description = self::text($form['description'] ?? '', 1000, true);
        $items = is_array($form['questions'] ?? null) ? array_values($form['questions']) : [];
        if (count($items) > self::MAX_ITEMS) {
            $errors['questions'] = 'Maksimal ' . self::MAX_ITEMS . ' item per form.';
            $items = array_slice($items, 0, self::MAX_ITEMS);
        }
        $questions = [];
        $ids = [];
        foreach ($items as $i => $item) {
            $clean = self::item(is_array($item) ? $item : [], $i + 1, $ids);
            if (is_string($clean)) {
                $errors['questions'] ??= $clean;
                continue;
            }
            $ids[$clean['id']] = $clean;
            $questions[] = $clean;
        }
        // Conditions may only point at an earlier question with that answer; others are dropped.
        $seen = [];
        foreach ($questions as &$q) {
            if (isset($q['showIf']) && !self::ruleOk($q['showIf'], $seen)) {
                unset($q['showIf']);
            }
            if (isset($q['requiredWhen'])) {
                $q['requiredWhen'] = array_values(array_filter($q['requiredWhen'], static fn (array $rule): bool => self::ruleOk($rule, $seen)));
                if ($q['requiredWhen'] === []) {
                    unset($q['requiredWhen']);
                }
            }
            $seen[$q['id']] = $q;
        }
        unset($q);

        $definition = ['title' => $title, 'description' => $description, 'questions' => $questions];
        foreach (['submitLabel', 'resetLabel'] as $key) {
            if (self::text($form[$key] ?? '', 60) !== '') {
                $definition[$key] = self::text($form[$key], 60);
            }
        }
        return ['definition' => $definition, 'errors' => $errors];
    }

    /** The answer items of a definition (no section, title or image). @return list<array> */
    public static function answerItems(array $definition): array
    {
        return array_values(array_filter($definition['questions'] ?? [], static fn ($q): bool => is_array($q) && !in_array($q['type'] ?? '', self::BLOCKS, true)));
    }

    /** One item, cleaned, or the message of its first problem ("Item 3: …"). */
    private static function item(array $q, int $number, array $ids): array|string
    {
        $prefix = 'Item ' . $number . ': ';
        $type = is_string($q['type'] ?? null) ? $q['type'] : '';
        if (!in_array($type, [...self::TYPES, ...self::FIXED, ...self::BLOCKS], true)) {
            return $prefix . 'tipe pertanyaan tidak dikenal.';
        }
        $id = is_string($q['id'] ?? null) ? $q['id'] : '';
        if (preg_match(self::ID, $id) !== 1 || isset($ids[$id])) {
            return $prefix . 'kode item tidak valid atau ganda. Muat ulang editor.';
        }
        $out = ['id' => $id, 'type' => $type, 'title' => self::text($q['title'] ?? '', 500, $type === 'confirm')];
        if ($out['title'] === '' && $type !== 'image') {
            return $prefix . 'isi teks pertanyaan, judul, atau bagian.';
        }
        $help = self::text($q['help'] ?? '', 1000, true);
        if ($help !== '') {
            $out['help'] = $help;
        }
        $image = self::raw($q['image'] ?? '');
        if ($image !== '' || $type === 'image') {
            if (!self::url($image)) {
                return $prefix . 'URL gambar harus diawali http:// atau https://.';
            }
            $out['image'] = $image;
        }
        if ($type === 'section') {
            $chip = self::text($q['chip'] ?? '', 40);
            return $out + ['chip' => $chip];
        }
        if (in_array($type, self::BLOCKS, true)) {
            return $out;
        }
        $out['required'] = ($q['required'] ?? false) === true;
        if (isset($q['showIf']) && is_array($q['showIf'])) {
            $rule = self::rule($q['showIf']);
            if ($rule !== null) {
                $out['showIf'] = $rule;
            }
        }
        if (isset($q['requiredWhen']) && is_array($q['requiredWhen'])) {
            $rules = array_values(array_filter(array_map(static fn ($r) => is_array($r) ? self::rule($r) : null, array_slice($q['requiredWhen'], 0, 20))));
            if ($rules !== []) {
                $out['requiredWhen'] = $rules;
            }
        }

        switch ($type) {
            case 'short':
            case 'paragraph':
                $max = self::int($q['maxlength'] ?? null, 1, $type === 'short' ? 1000 : 5000);
                if ($max !== null) {
                    $out['maxlength'] = $max;
                }
                if ($type === 'paragraph' && ($rows = self::int($q['rows'] ?? null, 1, 20)) !== null) {
                    $out['rows'] = $rows;
                }
                if ($type === 'short' && self::text($q['unit'] ?? '', 20) !== '') {
                    $out['unit'] = self::text($q['unit'], 20);
                }
                break;
            case 'radio':
            case 'checkbox':
            case 'select':
                $options = self::options($q['options'] ?? null);
                if ($options === []) {
                    return $prefix . 'pertanyaan pilihan perlu minimal satu opsi.';
                }
                $out['options'] = $options;
                if ($type !== 'select' && isset($q['other']) && $q['other'] !== false) {
                    $out['other'] = is_array($q['other'])
                        ? array_filter(['label' => self::text($q['other']['label'] ?? '', 100), 'placeholder' => self::text($q['other']['placeholder'] ?? '', 200)], static fn (string $v): bool => $v !== '') ?: true
                        : true;
                }
                break;
            case 'number':
                foreach (['min', 'max'] as $key) {
                    if (isset($q[$key]) && (is_int($q[$key]) || is_float($q[$key])) && abs((float) $q[$key]) < 1e9) {
                        $out[$key] = $q[$key];
                    }
                }
                if (isset($out['min'], $out['max']) && $out['min'] > $out['max']) {
                    unset($out['min'], $out['max']);
                }
                if (self::text($q['unit'] ?? '', 20) !== '') {
                    $out['unit'] = self::text($q['unit'], 20);
                }
                if (self::text($q['rangeMessage'] ?? '', 200) !== '') {
                    $out['rangeMessage'] = self::text($q['rangeMessage'], 200);
                }
                if (($q['integer'] ?? false) === true) {
                    $out['integer'] = true;
                }
                break;
            case 'scale':
                $out['min'] = ($q['min'] ?? 1) === 0 ? 0 : 1;
                $out['max'] = self::int($q['max'] ?? null, 2, 10) ?? 5;
                $out['minLabel'] = self::text($q['minLabel'] ?? '', 50);
                $out['maxLabel'] = self::text($q['maxLabel'] ?? '', 50);
                break;
            case 'rating':
                $out['max'] = self::int($q['max'] ?? null, 3, 10) ?? 5;
                break;
            case 'file':
                $out['maxMb'] = self::int($q['maxMb'] ?? null, 1, 10) ?? 10;
                $accept = array_values(array_intersect(self::FILE_TYPES, array_map(static fn (string $e): string => strtolower(ltrim(trim($e), '.')), explode(',', self::raw($q['accept'] ?? '')))));
                $out['accept'] = '.' . implode(',.', $accept !== [] ? $accept : ['pdf', 'jpg', 'jpeg', 'png']);
                if (($q['multiple'] ?? false) === true) {
                    $out['multiple'] = true;
                    $out['maxFiles'] = self::int($q['maxFiles'] ?? null, 1, 5) ?? 5;
                }
                if (in_array($q['capture'] ?? null, ['environment', 'user'], true)) {
                    $out['capture'] = $q['capture'];
                }
                break;
        }
        return $out;
    }

    /** Options as strings or {value, label, tone?}; empty labels dropped, values unique (no notes or badges). */
    private static function options(mixed $list): array
    {
        $options = [];
        $values = [];
        foreach (array_slice(is_array($list) ? $list : [], 0, 100) as $o) {
            if (is_string($o)) {
                $label = self::text($o, 200);
                if ($label !== '' && !isset($values[$label])) {
                    $values[$label] = true;
                    $options[] = $label;
                }
            } elseif (is_array($o) && is_string($o['value'] ?? null) && self::text($o['label'] ?? '', 200) !== '') {
                $value = self::text($o['value'], 100);
                if ($value === '' || isset($values[$value])) {
                    continue;
                }
                $values[$value] = true;
                $clean = ['value' => $value, 'label' => self::text($o['label'], 200)];
                if (($o['tone'] ?? null) === 'red') {
                    $clean['tone'] = 'red';
                }
                $options[] = $clean;
            }
        }
        return $options;
    }

    /** {q, value} or {q, values} with short texts, or null. */
    private static function rule(array $rule): ?array
    {
        if (!is_string($rule['q'] ?? null) || preg_match(self::ID, $rule['q']) !== 1) {
            return null;
        }
        if (is_array($rule['values'] ?? null)) {
            $values = array_values(array_filter(array_map(static fn ($v) => is_string($v) ? self::text($v, 200) : '', array_slice($rule['values'], 0, 20)), static fn (string $v): bool => $v !== ''));
            return $values !== [] ? ['q' => $rule['q'], 'values' => $values] : null;
        }
        return is_string($rule['value'] ?? null) && self::text($rule['value'], 200) !== '' ? ['q' => $rule['q'], 'value' => self::text($rule['value'], 200)] : null;
    }

    /** A rule is kept when its question comes earlier and has a matching option (or is not a choice). */
    private static function ruleOk(array $rule, array $earlier): bool
    {
        $source = $earlier[$rule['q']] ?? null;
        if ($source === null) {
            return false;
        }
        if (!in_array($source['type'], self::CHOICE, true)) {
            return true;
        }
        $values = array_map(static fn ($o): string => is_string($o) ? $o : $o['value'], $source['options']);
        return array_intersect($rule['values'] ?? [$rule['value']], $values) !== [];
    }

    private static function url(string $value): bool
    {
        if (mb_strlen($value) > 2048 || preg_match('/[\s<>"]/', $value) === 1) {
            return false;
        }
        $parts = parse_url($value);
        return is_array($parts) && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) && ($parts['host'] ?? '') !== '';
    }

    private static function raw(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    /** Trimmed text without control characters, cut at $max characters; one line unless $multiline. */
    private static function text(mixed $value, int $max, bool $multiline = false): string
    {
        if (!is_string($value)) {
            return '';
        }
        $value = str_replace("\r\n", "\n", $value);
        $value = (string) preg_replace($multiline ? '/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/u' : '/[\x00-\x1f\x7f]+/u', $multiline ? '' : ' ', $value);
        $value = trim($multiline ? $value : (string) preg_replace('/\s+/u', ' ', $value));
        return mb_substr($value, 0, $max);
    }

    private static function int(mixed $value, int $min, int $max): ?int
    {
        if (is_float($value) && floor($value) === $value) {
            $value = (int) $value;
        }
        return is_int($value) && $value >= $min && $value <= $max ? $value : null;
    }
}
