<?php

namespace App\Models;

class Vendor extends Model
{
    /**
     * All vendors with their confirmed-booking count, for the list/search page.
     * $page is null for callers that need the full list (e.g. the lot-assignment
     * picker) — only a non-null page applies LIMIT/OFFSET.
     */
    public static function allWithBookingCounts(?string $search = null, ?int $page = null, int $perPage = 50): array
    {
        $sql = 'SELECT vendors.*, COUNT(bookings.id) AS booking_count
                FROM vendors
                LEFT JOIN bookings ON bookings.vendor_id = vendors.id AND bookings.status = "booked"';
        $params = [];
        if ($search) {
            $sql .= ' WHERE vendors.name LIKE :search1 OR vendors.phone LIKE :search2 OR vendors.email LIKE :search3';
            $like = '%' . $search . '%';
            $params['search1'] = $like;
            $params['search2'] = $like;
            $params['search3'] = $like;
        }
        $sql .= ' GROUP BY vendors.id ORDER BY vendors.name';

        if ($page !== null) {
            $perPage = max(1, $perPage);
            $sql .= ' LIMIT ' . $perPage . ' OFFSET ' . ((max(1, $page) - 1) * $perPage);
        }

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Total vendors matching the search filter — drives the admin list's pagination controls. */
    public static function countWithSearch(?string $search = null): int
    {
        $sql = 'SELECT COUNT(*) FROM vendors';
        $params = [];
        if ($search) {
            $sql .= ' WHERE name LIKE :search1 OR phone LIKE :search2 OR email LIKE :search3';
            $like = '%' . $search . '%';
            $params['search1'] = $like;
            $params['search2'] = $like;
            $params['search3'] = $like;
        }

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
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
    /** Id + name (+ phone) of every vendor, for pick lists. */
    public static function options(): array
    {
        return self::db()->query('SELECT id, name, phone FROM vendors ORDER BY name LIMIT 1000')->fetchAll();
    }

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

    public static function update(int $id, string $name, string $phone, ?string $email, ?string $notes, ?string $itemsForSale = null): void
    {
        $stmt = self::db()->prepare(
            'UPDATE vendors SET name = :name, phone = :phone, email = :email, notes = :notes, items_for_sale = :items WHERE id = :id'
        );
        $stmt->execute([
            'name' => $name, 'phone' => $phone, 'email' => $email ?: null, 'notes' => $notes ?: null,
            'items' => $itemsForSale ?: null, 'id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        self::db()->prepare('DELETE FROM vendors WHERE id = :id')->execute(['id' => $id]);
    }

    /** The vendor (via their self-service portal) asking for their data to be deleted — not
     *  acted on automatically, just flagged for an admin to review (see VendorController). */
    public static function requestDeletion(int $id): void
    {
        self::db()->prepare('UPDATE vendors SET deletion_requested_at = NOW() WHERE id = :id')
            ->execute(['id' => $id]);
    }

    /** Admin acknowledging a deletion request without actually deleting the vendor. */
    public static function clearDeletionRequest(int $id): void
    {
        self::db()->prepare('UPDATE vendors SET deletion_requested_at = NULL WHERE id = :id')
            ->execute(['id' => $id]);
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

    /** Records a LINE account asking to be linked to this vendor, pending admin approval.
     *  A LINE user can only have one open request, so any earlier one by them is dropped. */
    public static function requestLineLink(int $id, string $lineUserId): void
    {
        self::db()->prepare(
            'UPDATE vendors SET line_pending_user_id = NULL, line_link_requested_at = NULL
             WHERE line_pending_user_id = :line_user_id AND id <> :id'
        )->execute(['line_user_id' => $lineUserId, 'id' => $id]);

        self::db()->prepare(
            'UPDATE vendors SET line_pending_user_id = :line_user_id, line_link_requested_at = NOW() WHERE id = :id'
        )->execute(['line_user_id' => $lineUserId, 'id' => $id]);
    }

    public static function clearLineLinkRequest(int $id): void
    {
        self::db()->prepare('UPDATE vendors SET line_pending_user_id = NULL, line_link_requested_at = NULL WHERE id = :id')
            ->execute(['id' => $id]);
    }

    /** Promotes the pending LINE account to the linked one. Returns the LINE user id, or null if none pending. */
    public static function approveLineLink(int $id): ?string
    {
        $vendor = self::find($id);
        $pending = $vendor['line_pending_user_id'] ?? null;
        if (!$pending) {
            return null;
        }

        // line_user_id is unique: if this LINE account is somehow already linked to another vendor, refuse.
        $owner = self::findByLineUserId($pending);
        if ($owner && (int) $owner['id'] !== $id) {
            self::clearLineLinkRequest($id);
            return null;
        }

        self::db()->prepare(
            'UPDATE vendors SET line_user_id = line_pending_user_id, line_pending_user_id = NULL, line_link_requested_at = NULL WHERE id = :id'
        )->execute(['id' => $id]);
        return $pending;
    }

    public static function unlinkLine(int $id): void
    {
        self::db()->prepare('UPDATE vendors SET line_user_id = NULL WHERE id = :id')->execute(['id' => $id]);
    }

    public static function setPortalToken(int $id, string $tokenHash, string $expiresAt): void
    {
        $stmt = self::db()->prepare(
            'UPDATE vendors SET portal_token_hash = :hash, portal_token_expires_at = :expires WHERE id = :id'
        );
        $stmt->execute(['hash' => $tokenHash, 'expires' => $expiresAt, 'id' => $id]);
    }

    public static function findByValidPortalTokenHash(string $tokenHash): ?array
    {
        // PHP-computed :now, not SQL NOW() — see AdminUser::findByValidResetTokenHash()
        // for why (portal_token_expires_at is set from PHP's clock, which can differ
        // from the DB server's local timezone that NOW() would otherwise use).
        $stmt = self::db()->prepare(
            'SELECT * FROM vendors WHERE portal_token_hash = :hash AND portal_token_expires_at > :now'
        );
        $stmt->execute(['hash' => $tokenHash, 'now' => date('Y-m-d H:i:s')]);
        return $stmt->fetch() ?: null;
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
