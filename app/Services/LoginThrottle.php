<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

/**
 * Login rate limit (PRD SEC-3): 5 failed attempts within 15 minutes for one username from one IP lock
 * that pair for 15 minutes, even with the right password. A wider limit of 20 failures per IP (any
 * username) slows password spraying. Attempts live in login_attempts; times are UTC.
 */
final class LoginThrottle
{
    public const MAX_FAILURES = 5;
    public const MAX_IP_FAILURES = 20;
    public const WINDOW_SECONDS = 900;

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** Seconds until the next attempt is allowed, 0 when login may proceed. */
    public function lockedFor(string $username, string $ip, int $now): int
    {
        $since = gmdate('Y-m-d H:i:s', $now - self::WINDOW_SECONDS);
        $pair = $this->pdo->prepare(
            'SELECT attempted_at FROM login_attempts WHERE username = ? AND ip = ? AND succeeded = 0 AND attempted_at > ?
               AND attempted_at > COALESCE((SELECT MAX(attempted_at) FROM login_attempts WHERE username = ? AND ip = ? AND succeeded = 1), \'1970-01-01\')
             ORDER BY attempted_at'
        );
        $pair->execute([$username, $ip, $since, $username, $ip]);
        $byIp = $this->pdo->prepare('SELECT attempted_at FROM login_attempts WHERE ip = ? AND succeeded = 0 AND attempted_at > ? ORDER BY attempted_at');
        $byIp->execute([$ip, $since]);
        $toTimes = static fn (array $rows): array => array_map(static fn (string $at): int => (int) strtotime($at . ' UTC'), $rows);
        return max(
            self::wait($toTimes($pair->fetchAll(PDO::FETCH_COLUMN)), self::MAX_FAILURES, $now),
            self::wait($toTimes($byIp->fetchAll(PDO::FETCH_COLUMN)), self::MAX_IP_FAILURES, $now),
        );
    }

    /**
     * Pure rule (see tests): with $limit or more failures inside the window, the lock lasts until the
     * oldest of the last $limit failures leaves the window. @param list<int> $failures unix times, ascending
     */
    public static function wait(array $failures, int $limit, int $now): int
    {
        $recent = array_values(array_filter($failures, static fn (int $at): bool => $at > $now - self::WINDOW_SECONDS && $at <= $now));
        if (count($recent) < $limit) {
            return 0;
        }
        $oldest = $recent[count($recent) - $limit];
        return max(1, $oldest + self::WINDOW_SECONDS - $now);
    }

    public function record(string $username, string $ip, bool $succeeded): void
    {
        $this->pdo->prepare('INSERT INTO login_attempts (username, ip, succeeded, attempted_at) VALUES (?, ?, ?, UTC_TIMESTAMP(3))')
            ->execute([mb_substr($username, 0, 100), mb_substr($ip, 0, 45), $succeeded ? 1 : 0]);
        if ($succeeded) {
            // Old rows are only needed for the window above; keep 90 days for review.
            $this->pdo->prepare('DELETE FROM login_attempts WHERE attempted_at < UTC_TIMESTAMP() - INTERVAL 90 DAY')->execute();
        }
    }
}
