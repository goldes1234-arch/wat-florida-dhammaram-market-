<?php

namespace App\Models;

class Lot extends Model
{
    public static function forEvent(int $eventId): array
    {
        // The LEFT JOIN picks up the current occupant's name (if any) — a lot can only
        // have one pending_payment/booked booking at a time, so this never duplicates rows.
        $stmt = self::db()->prepare(
            'SELECT lots.*, zones.name AS zone_name, active_booking.booker_name AS booker_name,
                    active_booking.shop_photo AS shop_photo
             FROM lots
             LEFT JOIN zones ON zones.id = lots.zone_id
             LEFT JOIN bookings AS active_booking
                ON active_booking.lot_id = lots.id
                AND active_booking.status IN ("pending_payment", "booked")
             WHERE lots.event_id = :event_id AND lots.deleted_at IS NULL
             ORDER BY zones.sort_order, lots.code'
        );
        $stmt->execute(['event_id' => $eventId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT lots.*, zones.name AS zone_name, events.slug AS event_slug, events.name_th AS event_name_th
             FROM lots
             LEFT JOIN zones ON zones.id = lots.zone_id
             JOIN events ON events.id = lots.event_id
             WHERE lots.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Must be called inside an active transaction to actually lock the row. */
    public static function lockForUpdate(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM lots WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function codeExists(int $eventId, string $code, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $stmt = self::db()->prepare('SELECT id FROM lots WHERE event_id = :event_id AND code = :code AND id <> :id');
            $stmt->execute(['event_id' => $eventId, 'code' => $code, 'id' => $excludeId]);
        } else {
            $stmt = self::db()->prepare('SELECT id FROM lots WHERE event_id = :event_id AND code = :code');
            $stmt->execute(['event_id' => $eventId, 'code' => $code]);
        }
        return (bool) $stmt->fetch();
    }

    public static function create(int $eventId, ?int $zoneId, string $code, float $price, ?int $gridRow = null, ?int $gridCol = null): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO lots (event_id, zone_id, code, price, grid_row, grid_col)
             VALUES (:event_id, :zone_id, :code, :price, :grid_row, :grid_col)'
        );
        $stmt->execute([
            'event_id' => $eventId, 'zone_id' => $zoneId, 'code' => $code, 'price' => $price,
            'grid_row' => $gridRow, 'grid_col' => $gridCol,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, ?int $zoneId, string $code, float $price, ?int $gridRow = null, ?int $gridCol = null): void
    {
        $stmt = self::db()->prepare(
            'UPDATE lots SET zone_id = :zone_id, code = :code, price = :price, grid_row = :grid_row, grid_col = :grid_col WHERE id = :id'
        );
        $stmt->execute([
            'zone_id' => $zoneId, 'code' => $code, 'price' => $price,
            'grid_row' => $gridRow, 'grid_col' => $gridCol, 'id' => $id,
        ]);
    }

    public static function setPhoto(int $id, ?string $photo): void
    {
        self::db()->prepare('UPDATE lots SET photo = :photo WHERE id = :id')->execute(['photo' => $photo, 'id' => $id]);
    }

    public static function setStatus(int $id, string $status): void
    {
        $stmt = self::db()->prepare('UPDATE lots SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /** Saves a lot's position on its event's floorplan photo, as a percentage of image width/height. */
    public static function setMapPosition(int $id, float $x, float $y): void
    {
        $stmt = self::db()->prepare('UPDATE lots SET map_x = :map_x, map_y = :map_y WHERE id = :id');
        $stmt->execute(['map_x' => $x, 'map_y' => $y, 'id' => $id]);
    }

    /** Sets the display size of a lot's pin on the photo-coordinate map. */
    public static function setMapSize(int $id, string $size): void
    {
        $stmt = self::db()->prepare('UPDATE lots SET map_size = :map_size WHERE id = :id');
        $stmt->execute(['map_size' => $size, 'id' => $id]);
    }

    /** Lightweight id => status map, used by the public "live" map polling endpoint. */
    public static function statusMapForEvent(int $eventId): array
    {
        $stmt = self::db()->prepare(
            'SELECT id, status FROM lots WHERE event_id = :event_id AND deleted_at IS NULL'
        );
        $stmt->execute(['event_id' => $eventId]);
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(int) $row['id']] = $row['status'];
        }
        return $map;
    }

    public static function softDelete(int $id): void
    {
        self::db()->prepare('UPDATE lots SET deleted_at = NOW() WHERE id = :id')->execute(['id' => $id]);
    }

    /**
     * Soft-deletes lots by id, but only ones that are safe to delete (available/disabled —
     * no active booking tied to them), mirroring the single-lot destroy() guard.
     */
    public static function softDeleteMany(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) {
            return ['deleted' => 0, 'skipped' => 0];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = self::db()->prepare(
            "UPDATE lots SET deleted_at = NOW()
             WHERE id IN ($placeholders) AND deleted_at IS NULL AND status IN ('available', 'disabled')"
        );
        $stmt->execute($ids);
        $deleted = $stmt->rowCount();

        return ['deleted' => $deleted, 'skipped' => count($ids) - $deleted];
    }

    /**
     * Deletes every lot in the event that's safe to delete (available/disabled, i.e. no
     * active booking tied to it) and reports how many were skipped because they're
     * pending_payment/booked — mirrors the single-lot destroy() guard in LotController.
     */
    public static function softDeleteAllForEvent(int $eventId): array
    {
        $stmt = self::db()->prepare(
            "UPDATE lots SET deleted_at = NOW()
             WHERE event_id = :event_id AND deleted_at IS NULL AND status IN ('available', 'disabled')"
        );
        $stmt->execute(['event_id' => $eventId]);
        $deleted = $stmt->rowCount();

        $stmt = self::db()->prepare(
            "SELECT COUNT(*) AS total FROM lots
             WHERE event_id = :event_id AND deleted_at IS NULL AND status IN ('pending_payment', 'booked')"
        );
        $stmt->execute(['event_id' => $eventId]);
        $skipped = (int) $stmt->fetch()['total'];

        return ['deleted' => $deleted, 'skipped' => $skipped];
    }

    public static function statusCountsForEvent(int $eventId): array
    {
        $stmt = self::db()->prepare(
            "SELECT status, COUNT(*) AS total FROM lots
             WHERE event_id = :event_id AND deleted_at IS NULL
             GROUP BY status"
        );
        $stmt->execute(['event_id' => $eventId]);
        $rows = $stmt->fetchAll();
        $counts = ['available' => 0, 'pending_payment' => 0, 'booked' => 0, 'disabled' => 0];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }
        return $counts;
    }

    public static function revenueBooked(): float
    {
        $stmt = self::db()->query(
            "SELECT COALESCE(SUM(price), 0) AS total FROM lots WHERE status = 'booked' AND deleted_at IS NULL"
        );
        return (float) $stmt->fetch()['total'];
    }

    /**
     * Available/total lot counts grouped by event, in one query — used for the public
     * home page's "N lots left" badge on every event card without an N+1 query per card.
     */
    public static function availableCountsByEvent(): array
    {
        $stmt = self::db()->query(
            "SELECT event_id, SUM(status = 'available') AS available, COUNT(*) AS total
             FROM lots WHERE deleted_at IS NULL GROUP BY event_id"
        );
        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[(int) $row['event_id']] = ['available' => (int) $row['available'], 'total' => (int) $row['total']];
        }
        return $counts;
    }

    public static function statusCountsGlobal(): array
    {
        $stmt = self::db()->query(
            "SELECT status, COUNT(*) AS total FROM lots WHERE deleted_at IS NULL GROUP BY status"
        );
        $counts = ['available' => 0, 'pending_payment' => 0, 'booked' => 0, 'disabled' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }
        return $counts;
    }
}
