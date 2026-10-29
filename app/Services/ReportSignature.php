<?php
declare(strict_types=1);

namespace App\Services;

/**
 * The signature block of a form's PDF and Cetak export, set per form on its recap page ("Tanda tangan") and
 * kept in forms.report_signature_json. Left side: who made the report (an empty name = the account that
 * exports); right side: who acknowledges it (an empty name = a blank line to sign on). Every text is one line.
 */
final class ReportSignature
{
    public const FIELDS = ['left_title', 'left_role', 'left_name', 'right_title', 'right_role', 'right_name'];
    public const MAX = 80;

    /** @return array<string, string> */
    public static function defaults(): array
    {
        return [
            'left_title' => 'Dibuat oleh,',
            'left_role' => '',
            'left_name' => '',
            'right_title' => 'Diketahui oleh,',
            'right_role' => 'Kepala Instalasi IT',
            'right_name' => (string) (config('report')['head_of_it_name'] ?? ''),
        ];
    }

    /** The saved texts of a form over the defaults. @return array<string, string> */
    public static function fromJson(?string $json): array
    {
        $saved = $json !== null ? json_decode($json, true) : null;
        $values = self::defaults();
        foreach (self::FIELDS as $field) {
            if (is_array($saved) && is_string($saved[$field] ?? null)) {
                $values[$field] = $saved[$field];
            }
        }
        return $values;
    }

    /** @return array{values: array<string, string>, errors: array<string, string>} */
    public static function check(mixed $input): array
    {
        $input = is_array($input) ? $input : [];
        $values = [];
        $errors = [];
        foreach (self::FIELDS as $field) {
            $raw = is_string($input[$field] ?? null) ? $input[$field] : '';
            $text = trim((string) preg_replace('/\s+/u', ' ', (string) preg_replace('/[\x00-\x1f\x7f]/u', ' ', $raw)));
            if (mb_strlen($text) > self::MAX) {
                $errors[$field] = 'Maksimal ' . self::MAX . ' karakter.';
            }
            $values[$field] = mb_substr($text, 0, self::MAX);
        }
        return ['values' => $values, 'errors' => $errors];
    }
}
