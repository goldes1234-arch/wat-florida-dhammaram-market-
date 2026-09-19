<?php

namespace App\Models;

class EventPhoto extends Model
{
    public const MAX_PER_EVENT = 6;

    public static function forEvent(int $eventId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM event_photos WHERE event_id = :event_id ORDER BY sort_order, id');
        $stmt->execute(['event_id' => $eventId]);
        return $stmt->fetchAll();
    }

    public static function countForEvent(int $eventId): int
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM event_photos WHERE event_id = :event_id');
        $stmt->execute(['event_id' => $eventId]);
        return (int) $stmt->fetchColumn();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM event_photos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $eventId, string $imagePath): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO event_photos (event_id, image_path) VALUES (:event_id, :image_path)'
        );
        $stmt->execute(['event_id' => $eventId, 'image_path' => $imagePath]);
        return (int) self::db()->lastInsertId();
    }

    public static function delete(int $id): void
    {
        self::db()->prepare('DELETE FROM event_photos WHERE id = :id')->execute(['id' => $id]);
    }
}
