<?php

namespace App\Models;

class Zone extends Model
{
    public static function forEvent(int $eventId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM zones WHERE event_id = :event_id ORDER BY sort_order, name');
        $stmt->execute(['event_id' => $eventId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM zones WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Guards against a lot being assigned a zone that belongs to a different event. */
    public static function belongsToEvent(int $zoneId, int $eventId): bool
    {
        $stmt = self::db()->prepare('SELECT id FROM zones WHERE id = :id AND event_id = :event_id');
        $stmt->execute(['id' => $zoneId, 'event_id' => $eventId]);
        return (bool) $stmt->fetch();
    }

    public static function create(int $eventId, string $name, float $defaultPrice, int $sortOrder = 0): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO zones (event_id, name, default_price, sort_order) VALUES (:event_id, :name, :price, :sort)'
        );
        $stmt->execute(['event_id' => $eventId, 'name' => $name, 'price' => $defaultPrice, 'sort' => $sortOrder]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, string $name, float $defaultPrice): void
    {
        $stmt = self::db()->prepare('UPDATE zones SET name = :name, default_price = :price WHERE id = :id');
        $stmt->execute(['name' => $name, 'price' => $defaultPrice, 'id' => $id]);
    }

    public static function delete(int $id): void
    {
        self::db()->prepare('DELETE FROM zones WHERE id = :id')->execute(['id' => $id]);
    }

    public static function eventIdsWithZones(): array
    {
        $stmt = self::db()->query('SELECT DISTINCT event_id FROM zones');
        return array_map('intval', array_column($stmt->fetchAll(), 'event_id'));
    }

    /**
     * Copies every zone from the source event into the target event, skipping any
     * whose name already exists there (uq_zone_name_per_event would reject it anyway).
     */
    public static function copyFromEvent(int $sourceEventId, int $targetEventId): array
    {
        $existing = array_column(self::forEvent($targetEventId), null, 'name');
        $copied = 0;
        $skipped = 0;

        foreach (self::forEvent($sourceEventId) as $zone) {
            if (isset($existing[$zone['name']])) {
                $skipped++;
                continue;
            }
            self::create($targetEventId, $zone['name'], (float) $zone['default_price']);
            $copied++;
        }

        return ['copied' => $copied, 'skipped' => $skipped];
    }
}
