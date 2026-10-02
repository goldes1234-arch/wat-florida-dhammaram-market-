<?php

namespace App\Controllers\Public;

use App\Core\Database;
use App\Core\Request;

/**
 * Unauthenticated uptime-monitor endpoint (UptimeRobot, Plesk monitoring, etc.) — only
 * confirms the app can reach its database, reveals nothing else, so it's safe to hit
 * from outside without a token the way /cron/* needs one.
 */
class HealthController
{
    public function check(Request $request): void
    {
        header('Content-Type: application/json');

        try {
            Database::connection()->query('SELECT 1');
            $dbOk = true;
        } catch (\Throwable $e) {
            $dbOk = false;
        }

        http_response_code($dbOk ? 200 : 503);
        echo json_encode([
            'status' => $dbOk ? 'ok' : 'error',
            'db' => $dbOk ? 'ok' : 'error',
            'time' => date('c'),
        ]);
    }
}
