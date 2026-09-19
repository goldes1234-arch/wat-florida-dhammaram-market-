<?php

namespace App\Models;

class WaitlistEntry extends Model
{
    public static function create(int $eventId, string $name, string $phone, ?string $email): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO waitlist_entries (event_id, name, phone, email) VALUES (:event_id, :name, :phone, :email)'
        );
        $stmt->execute(['event_id' => $eventId, 'name' => $name, 'phone' => $phone, 'email' => $email ?: null]);
        return (int) self::db()->lastInsertId();
    }

    public static function forEvent(int $eventId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM waitlist_entries WHERE event_id = :event_id ORDER BY created_at');
        $stmt->execute(['event_id' => $eventId]);
        return $stmt->fetchAll();
    }

    public static function notNotifiedForEvent(int $eventId): array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM waitlist_entries WHERE event_id = :event_id AND notified_at IS NULL ORDER BY created_at'
        );
        $stmt->execute(['event_id' => $eventId]);
        return $stmt->fetchAll();
    }

    public static function markNotified(int $id): void
    {
        self::db()->prepare('UPDATE waitlist_entries SET notified_at = NOW() WHERE id = :id')->execute(['id' => $id]);
    }
}
