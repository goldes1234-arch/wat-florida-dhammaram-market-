<?php

namespace App\Controllers\Public;

use App\Core\App;
use App\Core\Request;
use App\Services\BackupService;
use App\Services\ReservationService;

/**
 * Unauthenticated-by-session, token-authenticated endpoint so real hosting's
 * cron feature (which can't hold a login session) can trigger a backup on a
 * schedule, e.g. via `curl https://yourdomain/cron/backup?token=...` in cPanel's
 * Cron Jobs. Requires BACKUP_CRON_SECRET to be set in .env.
 *
 * Also releases any expired vendor-reservation lots on the same daily run —
 * piggybacking on this existing cron hit means releasing unconfirmed
 * reservations needs no separate hosting cron job of its own.
 */
class CronController
{
    public function backup(Request $request): void
    {
        header('Content-Type: text/plain');

        $secret = App::config('backup.cron_secret');
        $given = (string) ($request->query['token'] ?? '');

        if (!$secret || !hash_equals($secret, $given)) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $released = ReservationService::releaseExpired();

        [$ok, $result] = BackupService::create();
        if ($ok) {
            echo 'OK: ' . $result . ' | reservations released: ' . $released;
        } else {
            http_response_code(500);
            echo 'FAILED: ' . $result . ' | reservations released: ' . $released;
        }
    }
}
