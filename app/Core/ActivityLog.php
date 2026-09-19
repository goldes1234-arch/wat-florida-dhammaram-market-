<?php

namespace App\Core;

use App\Models\AdminActivityLog;

/**
 * One-line audit trail for consequential admin actions (deletions, account
 * changes) — distinct from booking_status_logs, which only tracks booking
 * transitions. Call from a controller right after the action succeeds.
 */
class ActivityLog
{
    public static function record(string $action, string $subjectType, ?int $subjectId, string $description): void
    {
        $admin = Auth::user();

        AdminActivityLog::create([
            'admin_id' => $admin['id'] ?? null,
            'admin_name' => $admin['name'] ?? null,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
