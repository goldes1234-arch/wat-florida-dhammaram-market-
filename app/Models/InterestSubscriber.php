<?php

namespace App\Models;

class InterestSubscriber extends Model
{
    public static function create(int $eventId, ?string $email, ?string $phone): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO interest_subscribers (event_id, email, phone) VALUES (:event_id, :email, :phone)'
        );
        $stmt->execute(['event_id' => $eventId, 'email' => $email ?: null, 'phone' => $phone ?: null]);
        return (int) self::db()->lastInsertId();
    }

    public static function forEvent(int $eventId): array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM interest_subscribers WHERE event_id = :event_id ORDER BY created_at DESC'
        );
        $stmt->execute(['event_id' => $eventId]);
        return $stmt->fetchAll();
    }

    public static function notNotifiedForEvent(int $eventId): array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM interest_subscribers WHERE event_id = :event_id AND notified_at IS NULL'
        );
        $stmt->execute(['event_id' => $eventId]);
        return $stmt->fetchAll();
    }

    public static function markNotified(int $id): void
    {
        self::db()->prepare('UPDATE interest_subscribers SET notified_at = NOW() WHERE id = :id')
            ->execute(['id' => $id]);
    }
}
