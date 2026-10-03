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
                    active_booking.shop_photo AS shop_photo, active_booking.items_for_sale AS items_for_sale
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
            'SELECT lots.*, zones.name AS zone_name, events.slug AS event_slug, events.name_th AS event_name_th,
                    events.name_en AS event_name_en, events.start_date AS event_start_date
             FROM lots
             LEFT JOIN zones ON zones.id = lots.zone_id
             JOIN events ON events.id = lots.event_id
             WHERE lots.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByReservedToken(string $token): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT lots.*, events.slug AS event_slug, events.name_th AS event_name_th,
                    events.name_en AS event_name_en, events.start_date AS event_start_date
             FROM lots
             JOIN events ON events.id = lots.event_id
             WHERE lots.reserved_token = :token'
        );
        $stmt->execute(['token' => $token]);
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

    /** Switches a lot's map marker between a round pin and a rotatable rectangle. */
    public static function setMapShape(int $id, string $shape): void
    {
        $stmt = self::db()->prepare('UPDATE lots SET map_shape = :map_shape WHERE id = :id');
        $stmt->execute(['map_shape' => $shape, 'id' => $id]);
    }

    /** Sets a box-shaped marker's tilt, in degrees. */
    public static function setMapRotation(int $id, float $rotation): void
    {
        $stmt = self::db()->prepare('UPDATE lots SET map_rotation = :map_rotation WHERE id = :id');
        $stmt->execute(['map_rotation' => $rotation, 'id' => $id]);
    }

    /** Holds a lot for a regular vendor pending their confirmation — see ReservationService. */
    public static function setReservation(int $id, string $name, string $phone, ?string $email, string $token, int $vendorId): void
    {
        $stmt = self::db()->prepare(
            'UPDATE lots SET status = "reserved", reserved_vendor_name = :name, reserved_vendor_phone = :phone,
                    reserved_vendor_email = :email, reserved_vendor_id = :vendor_id, reserved_token = :token,
                    reserved_at = NOW(), reserved_confirmed_at = NULL
             WHERE id = :id'
        );
        $stmt->execute(['name' => $name, 'phone' => $phone, 'email' => $email, 'vendor_id' => $vendorId, 'token' => $token, 'id' => $id]);
    }

    /** A vendor's reserved lots still waiting on their confirmation, soonest event first. */
    public static function reservedForVendor(int $vendorId): array
    {
        $stmt = self::db()->prepare(
            'SELECT lots.*, events.name_th AS event_name_th, events.name_en AS event_name_en,
                    events.start_date AS event_start_date
             FROM lots
             JOIN events ON events.id = lots.event_id
             WHERE lots.status = "reserved" AND lots.reserved_vendor_id = :vendor_id
               AND lots.reserved_confirmed_at IS NULL AND lots.deleted_at IS NULL
             ORDER BY events.start_date'
        );
        $stmt->execute(['vendor_id' => $vendorId]);
        return $stmt->fetchAll();
    }

    public static function markReservationConfirmed(int $id): void
    {
        self::db()->prepare('UPDATE lots SET reserved_confirmed_at = NOW() WHERE id = :id')->execute(['id' => $id]);
    }

    /** Releases a lot back to 'available', clearing whatever reservation was on it. */
    public static function clearReservation(int $id): void
    {
        $stmt = self::db()->prepare(
            'UPDATE lots SET status = "available", reserved_vendor_name = NULL, reserved_vendor_phone = NULL,
                    reserved_vendor_email = NULL, reserved_vendor_id = NULL, reserved_token = NULL,
                    reserved_at = NULL, reserved_confirmed_at = NULL
             WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    /**
     * Reserved lots whose vendor never confirmed and whose event is now within the
     * confirm-deadline window — what ReservationService::releaseExpired() acts on.
     */
    public static function expiredReservations(int $deadlineDays): array
    {
        $stmt = self::db()->prepare(
            'SELECT lots.*, events.slug AS event_slug, events.name_th AS event_name_th, events.start_date AS event_start_date
             FROM lots
             JOIN events ON events.id = lots.event_id
             WHERE lots.status = "reserved" AND lots.reserved_confirmed_at IS NULL
               AND lots.deleted_at IS NULL
               AND DATE_SUB(events.start_date, INTERVAL :deadline_days DAY) <= CURDATE()'
        );
        $stmt->execute(['deadline_days' => $deadlineDays]);
        return $stmt->fetchAll();
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
     * no active booking tied to them), mirroring the single-lot destroy() guard. Scoped to
     * $eventId so a request can never touch another event's lots, matching every other
     * lot-id-from-the-client action in LotController.
     */
    public static function softDeleteMany(array $ids, int $eventId): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) {
            return ['deleted' => 0, 'skipped' => 0];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = self::db()->prepare(
            "UPDATE lots SET deleted_at = NOW()
             WHERE id IN ($placeholders) AND event_id = ? AND deleted_at IS NULL AND status IN ('available', 'disabled')"
        );
        $stmt->execute([...$ids, $eventId]);
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
        $counts = ['available' => 0, 'pending_payment' => 0, 'booked' => 0, 'disabled' => 0, 'reserved' => 0];
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
        $counts = ['available' => 0, 'pending_payment' => 0, 'booked' => 0, 'disabled' => 0, 'reserved' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }
        return $counts;
    }
}
