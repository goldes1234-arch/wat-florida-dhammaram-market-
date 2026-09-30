<?php

namespace App\Controllers\Public;

use App\Core\App;
use App\Core\Request;
use App\Services\BackupService;
use App\Services\EventReminderService;
use App\Services\MigrationService;
use App\Services\ReservationService;

/**
 * Unauthenticated-by-session, token-authenticated endpoints so real hosting's
 * cron feature (which can't hold a login session) — or, for postDeploy(), the
 * hosting panel's own "run this after every deploy" hook — can trigger these
 * without a login session. Both share BACKUP_CRON_SECRET from .env.
 *
 * backup() also releases any expired vendor-reservation lots and sends
 * upcoming-event reminders on the same daily run — piggybacking on this
 * existing cron hit means neither needs a separate hosting cron job of its own.
 */
class CronController
{
    public function backup(Request $request): void
    {
        header('Content-Type: text/plain');

        if (!$this->checkToken($request)) {
            return;
        }

        $released = ReservationService::releaseExpired();
        $reminded = EventReminderService::sendDueReminders();

        [$ok, $result] = BackupService::create();
        if ($ok) {
            echo 'OK: ' . $result . ' | reservations released: ' . $released . ' | events reminded: ' . $reminded;
        } else {
            http_response_code(500);
            echo 'FAILED: ' . $result . ' | reservations released: ' . $released . ' | events reminded: ' . $reminded;
        }
    }

    /**
     * Wired into Plesk's (or any host's) "run after deploy" action so a push to
     * main can never again go live with code that expects a database column the
     * production DB doesn't have yet — see MigrationService. Also clears
     * OPcache, since a stale compiled copy of the old PHP files is the other
     * usual cause of "I deployed but the old behavior is still there".
     */
    public function postDeploy(Request $request): void
    {
        header('Content-Type: text/plain');

        if (!$this->checkToken($request)) {
            return;
        }

        $ranMigrations = MigrationService::runPending();

        $opcacheCleared = function_exists('opcache_reset') && opcache_reset();

        echo 'OK: migrations applied: ' . count($ranMigrations)
            . ($ranMigrations ? ' (' . implode(', ', $ranMigrations) . ')' : '')
            . ' | opcache cleared: ' . ($opcacheCleared ? 'yes' : 'no');
    }

    private function checkToken(Request $request): bool
    {
        $secret = App::config('backup.cron_secret');
        $given = (string) ($request->query['token'] ?? '');

        if (!$secret || !hash_equals($secret, $given)) {
            http_response_code(403);
            echo 'Forbidden';
            return false;
        }

        return true;
    }
}
