<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Application error log (PRD NFR-13): one file per day, storage/logs/app-YYYY-MM-DD.log (WIB date), with the
 * time and, for an uncaught exception, its stack trace (public/index.php). Files older than LOG_KEEP_DAYS
 * (default 90) are removed by the nightly job (bin/backup.php). Visitors never see error details outside local.
 */
final class ErrorLog
{
    public const PATTERN = '/^app-(\d{4}-\d{2}-\d{2})\.log$/';

    public static function register(string $environment): void
    {
        $dir = BASE_PATH . '/storage/logs';
        if (is_dir($dir) && is_writable($dir)) {
            ini_set('log_errors', '1');
            ini_set('error_log', $dir . '/app-' . date('Y-m-d') . '.log');
        }
        if ($environment !== 'local' && PHP_SAPI !== 'cli') {
            ini_set('display_errors', '0');
        }
    }

    /**
     * Log files older than $days days on $today (Y-m-d), oldest first. Other files are never touched.
     * @param list<string> $names file names in storage/logs
     * @return list<string>
     */
    public static function expired(array $names, string $today, int $days): array
    {
        $limit = date('Y-m-d', (int) strtotime($today . ' -' . max(1, $days) . ' days'));
        $old = array_values(array_filter($names, static fn (string $name): bool => preg_match(self::PATTERN, $name, $m) === 1 && $m[1] < $limit));
        sort($old);
        return $old;
    }

    /** Removes the expired log files; returns how many. */
    public static function prune(int $days): int
    {
        $dir = BASE_PATH . '/storage/logs';
        $removed = 0;
        foreach (self::expired(array_map('basename', glob($dir . '/app-*.log') ?: []), date('Y-m-d'), $days) as $name) {
            $removed += @unlink($dir . '/' . $name) ? 1 : 0;
        }
        return $removed;
    }
}
