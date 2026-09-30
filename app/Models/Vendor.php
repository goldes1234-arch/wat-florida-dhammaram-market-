<?php

namespace App\Models;

class Vendor extends Model
{
    /** All vendors with their confirmed-booking count, for the list/search page. */
    public static function allWithBookingCounts(?string $search = null): array
    {
        $sql = 'SELECT vendors.*, COUNT(bookings.id) AS booking_count
                FROM vendors
                LEFT JOIN bookings ON bookings.vendor_id = vendors.id AND bookings.status = "booked"';
        $params = [];
        if ($search) {
            $sql .= ' WHERE vendors.name LIKE :search OR vendors.phone LIKE :search OR vendors.email LIKE :search';
            $params['search'] = '%' . $search . '%';
        }
        $sql .= ' GROUP BY vendors.id ORDER BY vendors.name';

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM vendors WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByPhone(string $phone): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM vendors WHERE phone = :phone ORDER BY id LIMIT 1');
        $stmt->execute(['phone' => $phone]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Matches by digits only, ignoring dashes/spaces admins may have typed into
     * the stored phone number — used by the LINE webhook, which only has whatever
     * digit string a vendor happened to type into the chat.
     */
    public static function findByDigitsOnlyPhone(string $digits): ?array
    {
        $stmt = self::db()->prepare(
            "SELECT * FROM vendors
             WHERE REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '+', '') = :digits
             ORDER BY id LIMIT 1"
        );
        $stmt->execute(['digits' => $digits]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $name, string $phone, ?string $email, ?string $notes = null): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO vendors (name, phone, email, notes) VALUES (:name, :phone, :email, :notes)'
        );
        $stmt->execute(['name' => $name, 'phone' => $phone, 'email' => $email ?: null, 'notes' => $notes ?: null]);
        return (int) self::db()->lastInsertId();
    }

    /** Reuses an existing vendor with this phone number if there is one, otherwise creates a new one. */
    public static function findOrCreateByContact(string $name, string $phone, ?string $email): int
    {
        $existing = self::findByPhone($phone);
        if ($existing) {
            return (int) $existing['id'];
        }
        return self::create($name, $phone, $email);
    }

    public static function update(int $id, string $name, string $phone, ?string $email, ?string $notes): void
    {
        $stmt = self::db()->prepare(
            'UPDATE vendors SET name = :name, phone = :phone, email = :email, notes = :notes WHERE id = :id'
        );
        $stmt->execute(['name' => $name, 'phone' => $phone, 'email' => $email ?: null, 'notes' => $notes ?: null, 'id' => $id]);
    }

    public static function delete(int $id): void
    {
        self::db()->prepare('DELETE FROM vendors WHERE id = :id')->execute(['id' => $id]);
    }

    public static function findByLineUserId(string $lineUserId): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM vendors WHERE line_user_id = :line_user_id');
        $stmt->execute(['line_user_id' => $lineUserId]);
        return $stmt->fetch() ?: null;
    }

    public static function linkLine(int $id, string $lineUserId): void
    {
        self::db()->prepare('UPDATE vendors SET line_user_id = :line_user_id WHERE id = :id')
            ->execute(['line_user_id' => $lineUserId, 'id' => $id]);
    }

    public static function unlinkLine(int $id): void
    {
        self::db()->prepare('UPDATE vendors SET line_user_id = NULL WHERE id = :id')->execute(['id' => $id]);
    }

    /** Vendors with a linked LINE account, for the targeted-message page. */
    public static function linkedToLine(): array
    {
        $stmt = self::db()->query(
            'SELECT id, name, phone FROM vendors WHERE line_user_id IS NOT NULL ORDER BY name'
        );
        return $stmt->fetchAll();
    }

    /** Ranks vendors by confirmed-booking revenue, for the reports page. */
    public static function topByRevenue(int $limit = 10): array
    {
        $stmt = self::db()->prepare(
            'SELECT vendors.id, vendors.name, vendors.phone,
                    COUNT(bookings.id) AS booking_count,
                    COALESCE(SUM(bookings.price_at_booking), 0) AS revenue
             FROM vendors
             JOIN bookings ON bookings.vendor_id = vendors.id AND bookings.status = "booked"
             GROUP BY vendors.id
             ORDER BY revenue DESC
             LIMIT ' . (int) $limit
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Confirmed booking history for a vendor — what actually happened, not attempted/cancelled reservations. */
    public static function bookingHistory(int $id): array
    {
        $stmt = self::db()->prepare(
            'SELECT bookings.*, lots.code AS lot_code, events.name_th AS event_name_th,
                    events.name_en AS event_name_en, events.start_date AS event_start_date, events.slug AS event_slug
             FROM bookings
             JOIN lots ON lots.id = bookings.lot_id
             JOIN events ON events.id = bookings.event_id
             WHERE bookings.vendor_id = :id
             ORDER BY events.start_date DESC'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }
}
