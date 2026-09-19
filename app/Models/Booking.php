<?php

namespace App\Models;

class Booking extends Model
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare(self::baseSelect() . ' WHERE bookings.id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByCode(string $code): ?array
    {
        $stmt = self::db()->prepare(self::baseSelect() . ' WHERE bookings.booking_code = :code');
        $stmt->execute(['code' => $code]);
        return $stmt->fetch() ?: null;
    }

    public static function findByPhoneAndEmail(string $phone, string $email): array
    {
        $stmt = self::db()->prepare(
            self::baseSelect() . ' WHERE bookings.booker_phone = :phone AND bookings.booker_email = :email
             ORDER BY bookings.created_at DESC'
        );
        $stmt->execute(['phone' => $phone, 'email' => $email]);
        return $stmt->fetchAll();
    }

    private static function baseSelect(): string
    {
        return 'SELECT bookings.*, lots.code AS lot_code, events.name_th AS event_name_th,
                        events.name_en AS event_name_en, events.slug AS event_slug,
                        events.start_date AS event_start_date
                 FROM bookings
                 JOIN lots ON lots.id = bookings.lot_id
                 JOIN events ON events.id = bookings.event_id';
    }

    public static function forAdmin(?int $eventId, ?string $status, string $sort = 'desc'): array
    {
        $sql = self::baseSelect() . ' WHERE 1=1';
        $params = [];
        if ($eventId) {
            $sql .= ' AND bookings.event_id = :event_id';
            $params['event_id'] = $eventId;
        }
        if ($status) {
            $sql .= ' AND bookings.status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY bookings.created_at ' . ($sort === 'asc' ? 'ASC' : 'DESC');

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO bookings
                (booking_code, event_id, lot_id, booker_name, booker_phone, booker_email,
                 payment_method, status, price_at_booking, currency_code)
             VALUES
                (:booking_code, :event_id, :lot_id, :booker_name, :booker_phone, :booker_email,
                 :payment_method, :status, :price_at_booking, :currency_code)'
        );
        $stmt->execute([
            'booking_code' => $data['booking_code'],
            'event_id' => $data['event_id'],
            'lot_id' => $data['lot_id'],
            'booker_name' => $data['booker_name'],
            'booker_phone' => $data['booker_phone'],
            'booker_email' => $data['booker_email'] ?: null,
            'payment_method' => $data['payment_method'],
            'status' => $data['status'] ?? 'pending_payment',
            'price_at_booking' => $data['price_at_booking'],
            'currency_code' => $data['currency_code'],
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function updateStatus(int $id, string $status, array $extra = []): void
    {
        $fields = ['status = :status'];
        $params = ['status' => $status, 'id' => $id];

        $allowedExtra = ['confirmed_at', 'cancelled_at', 'cancelled_by', 'admin_note',
            'stripe_checkout_session_id', 'stripe_payment_intent_id'];
        foreach ($allowedExtra as $field) {
            if (array_key_exists($field, $extra)) {
                $fields[] = "$field = :$field";
                $params[$field] = $extra[$field];
            }
        }

        $sql = 'UPDATE bookings SET ' . implode(', ', $fields) . ' WHERE id = :id';
        self::db()->prepare($sql)->execute($params);
    }

    public static function setShopPhoto(int $id, string $path): void
    {
        self::db()->prepare('UPDATE bookings SET shop_photo = :photo WHERE id = :id')->execute(['photo' => $path, 'id' => $id]);
    }

    public static function checkIn(int $id, int $adminId): void
    {
        $stmt = self::db()->prepare('UPDATE bookings SET checked_in_at = NOW(), checked_in_by = :admin_id WHERE id = :id');
        $stmt->execute(['admin_id' => $adminId, 'id' => $id]);
    }

    public static function setStripeSession(int $id, string $sessionId): void
    {
        $stmt = self::db()->prepare('UPDATE bookings SET stripe_checkout_session_id = :sid WHERE id = :id');
        $stmt->execute(['sid' => $sessionId, 'id' => $id]);
    }

    public static function findByStripeSession(string $sessionId): ?array
    {
        $stmt = self::db()->prepare(self::baseSelect() . ' WHERE bookings.stripe_checkout_session_id = :sid');
        $stmt->execute(['sid' => $sessionId]);
        return $stmt->fetch() ?: null;
    }

    /** Used by the admin check-in search (name/phone/email lookup, staff at the door). */
    public static function searchByField(string $field, string $term): array
    {
        $column = match ($field) {
            'name' => 'booker_name',
            'phone' => 'booker_phone',
            'email' => 'booker_email',
            default => null,
        };
        if (!$column) {
            return [];
        }

        $stmt = self::db()->prepare(
            self::baseSelect() . " WHERE bookings.$column LIKE :term ORDER BY bookings.created_at DESC LIMIT 25"
        );
        $stmt->execute(['term' => '%' . $term . '%']);
        return $stmt->fetchAll();
    }

    public static function codeExists(string $code): bool
    {
        $stmt = self::db()->prepare('SELECT id FROM bookings WHERE booking_code = :code');
        $stmt->execute(['code' => $code]);
        return (bool) $stmt->fetch();
    }

    public static function dashboardStats(): array
    {
        $stmt = self::db()->query(
            "SELECT
                SUM(CASE WHEN status = 'pending_payment' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) AS booked,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS today
             FROM bookings"
        );
        return $stmt->fetch();
    }

    public static function recentForAdmin(int $limit = 8): array
    {
        $stmt = self::db()->prepare(self::baseSelect() . ' ORDER BY bookings.created_at DESC LIMIT ' . (int) $limit);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Hard-deletes bookings by id — but only ones in a terminal, non-money-moving
     * status (rejected/cancelled). Anything pending_payment/booked is a real
     * transaction record and must go through cancel/reject first, never a raw delete.
     * booking_status_logs rows cascade-delete automatically (FK ON DELETE CASCADE).
     */
    public static function deleteMany(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) {
            return ['deleted' => 0, 'skipped' => 0];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = self::db()->prepare(
            "DELETE FROM bookings WHERE id IN ($placeholders) AND status IN ('rejected', 'cancelled')"
        );
        $stmt->execute($ids);
        $deleted = $stmt->rowCount();

        return ['deleted' => $deleted, 'skipped' => count($ids) - $deleted];
    }

    public static function deleteAllSafe(?int $eventId, ?string $status): array
    {
        $where = "status IN ('rejected', 'cancelled')";
        $params = [];
        if ($eventId) {
            $where .= ' AND event_id = :event_id';
            $params['event_id'] = $eventId;
        }
        if ($status) {
            $where .= ' AND status = :status';
            $params['status'] = $status;
        }

        $stmt = self::db()->prepare("DELETE FROM bookings WHERE $where");
        $stmt->execute($params);
        $deleted = $stmt->rowCount();

        $skipWhere = "status NOT IN ('rejected', 'cancelled')";
        if ($eventId) {
            $skipWhere .= ' AND event_id = :event_id';
        }
        if ($status) {
            $skipWhere .= ' AND status = :status';
        }
        $stmt = self::db()->prepare("SELECT COUNT(*) AS total FROM bookings WHERE $skipWhere");
        $stmt->execute($params);
        $skipped = (int) $stmt->fetch()['total'];

        return ['deleted' => $deleted, 'skipped' => $skipped];
    }

    public static function bookedCount(): int
    {
        $stmt = self::db()->query("SELECT COUNT(*) AS total FROM bookings WHERE status = 'booked'");
        return (int) $stmt->fetch()['total'];
    }

    public static function revenueByPaymentMethod(): array
    {
        $stmt = self::db()->query(
            "SELECT payment_method, COALESCE(SUM(price_at_booking), 0) AS total
             FROM bookings WHERE status = 'booked' GROUP BY payment_method"
        );
        $totals = ['onsite_cash' => 0.0, 'bank_transfer' => 0.0, 'stripe' => 0.0];
        foreach ($stmt->fetchAll() as $row) {
            $totals[$row['payment_method']] = (float) $row['total'];
        }
        return $totals;
    }
}
