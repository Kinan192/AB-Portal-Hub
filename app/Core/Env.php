<?php
declare(strict_types=1);

namespace App\Core;

final class Env
{
    /** @var array<string, string> the values of this request */
    private static array $values = [];

    /**
     * Load KEY=VALUE pairs from a .env file. Variables already set in the real
     * environment take precedence, so server configuration can override the file.
     * The values are kept for this request only (read them with get()): putenv() would share them across the
     * threads of Apache on Windows, where PHP clears them when another request ends.
     */
    public static function load(string $file): void
    {
        if (!is_file($file) || !is_readable($file)) {
            return;
        }
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (str_starts_with($line, 'export ')) {
                $line = ltrim(substr($line, 7));
            }
            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $key = trim($key);
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key) !== 1) {
                continue;
            }
            $real = getenv($key);
            $value = $real !== false ? $real : self::parseValue(trim($value));
            self::$values[$key] = $value;
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    /** A setting: the real environment when set, else the .env file; false when neither has it. */
    public static function get(string $key): string|false
    {
        $real = getenv($key);
        return $real !== false ? $real : (self::$values[$key] ?? false);
    }

    private static function parseValue(string $value): string
    {
        $quote = $value[0] ?? '';
        if (($quote === '"' || $quote === "'") && strlen($value) >= 2 && str_ends_with($value, $quote)) {
            return substr($value, 1, -1);
        }
        // Unquoted values may carry a trailing " # comment".
        return rtrim((string) preg_replace('/\s+#.*$/', '', $value));
    }
}
