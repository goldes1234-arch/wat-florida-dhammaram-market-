<?php

namespace App\Models;

class Report extends Model
{
    /** Lot/revenue summary for one event, or across all events when $eventId is null. */
    public static function summary(?int $eventId): array
    {
        $sql = "SELECT COUNT(*) AS total_lots,
                       COALESCE(SUM(status = 'booked'), 0) AS booked_lots,
                       COALESCE(SUM(CASE WHEN status = 'booked' THEN price ELSE 0 END), 0) AS revenue
                FROM lots WHERE deleted_at IS NULL";
        $params = [];
        if ($eventId) {
            $sql .= ' AND event_id = :event_id';
            $params['event_id'] = $eventId;
        }

        $row = self::db()->prepare($sql);
        $row->execute($params);
        $row = $row->fetch();

        $totalLots = (int) $row['total_lots'];
        $bookedLots = (int) $row['booked_lots'];
        $revenue = (float) $row['revenue'];

        return [
            'total_lots' => $totalLots,
            'booked_lots' => $bookedLots,
            'revenue' => $revenue,
            'sell_through_pct' => $totalLots > 0 ? round($bookedLots / $totalLots * 100, 1) : 0.0,
            'avg_price' => $bookedLots > 0 ? $revenue / $bookedLots : 0.0,
        ];
    }

    /**
     * Bookings received per day (status booked or pending_payment — i.e. any real
     * booking attempt, not a rejected/cancelled one) so admins can see how fast an
     * event is selling. Days with zero activity are omitted; the view fills gaps.
     */
    public static function dailyTrend(?int $eventId): array
    {
        $sql = "SELECT DATE(created_at) AS d, COUNT(*) AS bookings,
                       COALESCE(SUM(CASE WHEN status = 'booked' THEN price_at_booking ELSE 0 END), 0) AS revenue
                FROM bookings
                WHERE status IN ('booked', 'pending_payment')";
        $params = [];
        if ($eventId) {
            $sql .= ' AND event_id = :event_id';
            $params['event_id'] = $eventId;
        }
        $sql .= ' GROUP BY DATE(created_at) ORDER BY d ASC';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Confirmed revenue grouped by zone (lots with no zone are grouped under a NULL key). */
    public static function revenueByZone(?int $eventId): array
    {
        $sql = "SELECT zones.id AS zone_id, zones.name AS zone_name,
                       COUNT(*) AS booking_count,
                       COALESCE(SUM(bookings.price_at_booking), 0) AS revenue
                FROM bookings
                JOIN lots ON lots.id = bookings.lot_id
                LEFT JOIN zones ON zones.id = lots.zone_id
                WHERE bookings.status = 'booked'";
        $params = [];
        if ($eventId) {
            $sql .= ' AND bookings.event_id = :event_id';
            $params['event_id'] = $eventId;
        }
        $sql .= ' GROUP BY zones.id, zones.name ORDER BY revenue DESC';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** One row per (non-deleted) event, for the cross-event comparison table. */
    public static function eventComparison(): array
    {
        $stmt = self::db()->query(
            "SELECT events.id, events.name_th, events.name_en, events.start_date,
                    COUNT(lots.id) AS total_lots,
                    COALESCE(SUM(lots.status = 'booked'), 0) AS booked_lots,
                    COALESCE(SUM(CASE WHEN lots.status = 'booked' THEN lots.price ELSE 0 END), 0) AS revenue
             FROM events
             LEFT JOIN lots ON lots.event_id = events.id AND lots.deleted_at IS NULL
             WHERE events.deleted_at IS NULL
             GROUP BY events.id
             ORDER BY events.start_date DESC"
        );
        return $stmt->fetchAll();
    }
}
