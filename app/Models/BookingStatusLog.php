<?php

namespace App\Models;

class BookingStatusLog extends Model
{
    public static function record(
        int $bookingId,
        ?string $fromStatus,
        string $toStatus,
        string $changedByType,
        ?int $changedByAdminId = null,
        ?string $note = null
    ): void {
        $stmt = self::db()->prepare(
            'INSERT INTO booking_status_logs (booking_id, from_status, to_status, changed_by_type, changed_by_admin_id, note)
             VALUES (:booking_id, :from_status, :to_status, :changed_by_type, :changed_by_admin_id, :note)'
        );
        $stmt->execute([
            'booking_id' => $bookingId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by_type' => $changedByType,
            'changed_by_admin_id' => $changedByAdminId,
            'note' => $note,
        ]);
    }

    public static function forBooking(int $bookingId): array
    {
        $stmt = self::db()->prepare(
            'SELECT booking_status_logs.*, admin_users.name AS admin_name
             FROM booking_status_logs
             LEFT JOIN admin_users ON admin_users.id = booking_status_logs.changed_by_admin_id
             WHERE booking_id = :booking_id
             ORDER BY created_at ASC, id ASC'
        );
        $stmt->execute(['booking_id' => $bookingId]);
        return $stmt->fetchAll();
    }
}
