<?php

namespace Tests\Integration;

use App\Core\Database;
use App\Core\RateLimiter;
use Tests\TestCase;

/** Covers the mechanism AuthController::login() relies on to block brute-force login attempts. */
class RateLimiterTest extends TestCase
{
    private const TEST_ACTION = 'test_rate_limiter_action';
    private const TEST_IP_A = '203.0.113.99';
    private const TEST_IP_B = '198.51.100.42';

    public function tearDown(): void
    {
        Database::connection()
            ->prepare('DELETE FROM booking_rate_limits WHERE action = ?')
            ->execute([self::TEST_ACTION]);
    }

    public function testAllowsUpToTheLimitThenBlocks(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $blocked = RateLimiter::tooMany(self::TEST_IP_A, self::TEST_ACTION, 3);
            $this->assertFalse($blocked, "attempt $i should not be blocked yet (limit is 3)");
        }

        $blocked = RateLimiter::tooMany(self::TEST_IP_A, self::TEST_ACTION, 3);
        $this->assertTrue($blocked, 'the 4th attempt should be blocked once the limit is 3');
    }

    public function testDifferentIpsAreTrackedSeparately(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            RateLimiter::tooMany(self::TEST_IP_A, self::TEST_ACTION, 3);
        }

        $blocked = RateLimiter::tooMany(self::TEST_IP_B, self::TEST_ACTION, 3);
        $this->assertFalse($blocked, "one IP's attempts must not count against a different IP");
    }
}
