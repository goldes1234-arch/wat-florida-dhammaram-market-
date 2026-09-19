<?php

namespace App\Core;

use App\Models\BookingRateLimit;

class RateLimiter
{
    /**
     * Records an attempt and returns true if the caller is now OVER the allowed
     * rate for this action within the last hour (i.e. should be blocked).
     */
    public static function tooMany(string $ip, string $action, int $maxPerHour): bool
    {
        BookingRateLimit::record($ip, $action);
        BookingRateLimit::pruneOld();

        return BookingRateLimit::countRecent($ip, $action, 60) > $maxPerHour;
    }
}
