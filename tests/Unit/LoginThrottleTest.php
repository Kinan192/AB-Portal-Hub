<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\LoginThrottle;
use PHPUnit\Framework\TestCase;

/** PRD SEC-3: the 6th attempt within 15 minutes after 5 failures is refused. */
final class LoginThrottleTest extends TestCase
{
    private const NOW = 1_800_000_000;

    public function testFourFailuresStillAllowLogin(): void
    {
        $failures = [self::NOW - 240, self::NOW - 180, self::NOW - 120, self::NOW - 60];
        self::assertSame(0, LoginThrottle::wait($failures, LoginThrottle::MAX_FAILURES, self::NOW));
    }

    public function testFifthFailureLocksUntilTheOldestLeavesTheWindow(): void
    {
        $failures = [self::NOW - 300, self::NOW - 240, self::NOW - 180, self::NOW - 120, self::NOW - 60];
        self::assertSame(900 - 300, LoginThrottle::wait($failures, LoginThrottle::MAX_FAILURES, self::NOW));
    }

    public function testFailuresOlderThanFifteenMinutesDoNotCount(): void
    {
        $failures = [self::NOW - 1000, self::NOW - 950, self::NOW - 120, self::NOW - 90, self::NOW - 60];
        self::assertSame(0, LoginThrottle::wait($failures, LoginThrottle::MAX_FAILURES, self::NOW));
    }

    public function testLockLiftsExactlyAfterTheWindow(): void
    {
        $failures = array_map(static fn (int $s): int => self::NOW - $s, [900, 899, 898, 897, 896]);
        // The first failure is exactly 15 minutes old: four remain inside the window.
        self::assertSame(0, LoginThrottle::wait($failures, LoginThrottle::MAX_FAILURES, self::NOW));
        // One second earlier all five are inside: locked for exactly that last second.
        self::assertSame(1, LoginThrottle::wait($failures, LoginThrottle::MAX_FAILURES, self::NOW - 1));
    }

    public function testManyQuickFailuresKeepTheLockAtMostFifteenMinutes(): void
    {
        $failures = array_map(static fn (int $i): int => self::NOW - 10 + $i, range(0, 9));
        $wait = LoginThrottle::wait($failures, LoginThrottle::MAX_FAILURES, self::NOW);
        self::assertGreaterThan(0, $wait);
        self::assertLessThanOrEqual(LoginThrottle::WINDOW_SECONDS, $wait);
    }

    public function testIpLimitIsTwentyFailures(): void
    {
        $failures = array_map(static fn (int $i): int => self::NOW - 600 + $i * 10, range(0, 18));
        self::assertSame(0, LoginThrottle::wait($failures, LoginThrottle::MAX_IP_FAILURES, self::NOW));
        $failures[] = self::NOW - 5;
        self::assertGreaterThan(0, LoginThrottle::wait($failures, LoginThrottle::MAX_IP_FAILURES, self::NOW));
    }
}
