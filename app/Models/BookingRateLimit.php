<?php

namespace App\Models;

class BookingRateLimit extends Model
{
    public static function record(string $ip, string $action): void
    {
        $stmt = self::db()->prepare(
            'INSERT INTO booking_rate_limits (ip_address, action) VALUES (:ip, :action)'
        );
        $stmt->execute(['ip' => $ip, 'action' => $action]);
    }

    public static function countRecent(string $ip, string $action, int $withinMinutes): int
    {
        $stmt = self::db()->prepare(
            'SELECT COUNT(*) AS total FROM booking_rate_limits
             WHERE ip_address = :ip AND action = :action
               AND created_at >= (NOW() - INTERVAL :minutes MINUTE)'
        );
        $stmt->bindValue('ip', $ip);
        $stmt->bindValue('action', $action);
        $stmt->bindValue('minutes', $withinMinutes, \PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetch()['total'];
    }

    public static function pruneOld(): void
    {
        self::db()->exec('DELETE FROM booking_rate_limits WHERE created_at < (NOW() - INTERVAL 1 DAY)');
    }
}
