<?php

namespace App\Core;

use App\Models\StaffEventAccess;

/**
 * Optional per-staff event restriction. A 'staff' admin with zero rows in
 * staff_event_access (the default — see StaffController's event-access UI)
 * can see/manage every event, exactly as before this feature existed;
 * assigning at least one event switches that account to an allow-list of
 * only those events. super_admin is never restricted, and 'checkin' never
 * reaches event-scoped routes in the first place (see the staff_or_admin
 * middleware), so neither role is affected by this at all.
 */
class EventAccess
{
    public static function allowed(int $eventId): bool
    {
        $assigned = self::assignedEventIds();
        return $assigned === null || in_array($eventId, $assigned, true);
    }

    /** Filters a list of rows (each with an 'id' key that is an event id) down to what this staff can see. */
    public static function filterEvents(array $rows, string $idKey = 'id'): array
    {
        $assigned = self::assignedEventIds();
        if ($assigned === null) {
            return $rows;
        }

        return array_values(array_filter($rows, fn (array $row) => in_array((int) $row[$idKey], $assigned, true)));
    }

    /** Null means "unrestricted" (super_admin, or a staff account with no assignments yet). */
    public static function assignedEventIds(): ?array
    {
        $user = Auth::user();
        if (!$user || $user['role'] !== 'staff') {
            return null;
        }

        $assigned = StaffEventAccess::eventIdsFor((int) $user['id']);
        return $assigned ?: null;
    }

    public static function isRestricted(): bool
    {
        return self::assignedEventIds() !== null;
    }
}
