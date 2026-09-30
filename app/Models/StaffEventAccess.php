<?php

namespace App\Models;

use PDO;

class StaffEventAccess extends Model
{
    /** @return int[] */
    public static function eventIdsFor(int $adminId): array
    {
        $stmt = self::db()->prepare('SELECT event_id FROM staff_event_access WHERE admin_id = :admin_id');
        $stmt->execute(['admin_id' => $adminId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /** Replaces this admin's full assignment list — an empty array clears it back to unrestricted. */
    public static function setFor(int $adminId, array $eventIds): void
    {
        $pdo = self::db();
        $pdo->prepare('DELETE FROM staff_event_access WHERE admin_id = :admin_id')->execute(['admin_id' => $adminId]);

        $eventIds = array_unique(array_map('intval', $eventIds));
        if (!$eventIds) {
            return;
        }

        $stmt = $pdo->prepare('INSERT INTO staff_event_access (admin_id, event_id) VALUES (:admin_id, :event_id)');
        foreach ($eventIds as $eventId) {
            $stmt->execute(['admin_id' => $adminId, 'event_id' => $eventId]);
        }
    }
}
