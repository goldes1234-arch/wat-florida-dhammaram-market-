<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Minimal hand-rolled LINE Messaging API client (broadcast push only) — no SDK.
 * Uses "broadcast" (sends to everyone who has added the temple's LINE Official
 * Account as a friend) rather than a targeted push, since that avoids needing to
 * capture a specific admin LINE user id — for a small temple, the admin/staff who
 * follow the OA are exactly who should see these alerts. Only active once a
 * Channel Access Token is entered in Settings; silently does nothing otherwise.
 */
class LineService
{
    public static function isEnabled(): bool
    {
        return !empty(Setting::get()['line_oa_channel_access_token']);
    }

    public static function broadcast(string $message): bool
    {
        $token = Setting::get()['line_oa_channel_access_token'] ?? '';
        if (!$token) {
            return false;
        }

        $ch = curl_init('https://api.line.me/v2/bot/message/broadcast');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'messages' => [
                    ['type' => 'text', 'text' => mb_substr($message, 0, 5000)],
                ],
            ]),
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            error_log('LINE broadcast failed: ' . $curlError);
            return false;
        }
        if ($status >= 400) {
            error_log('LINE broadcast API error (' . $status . '): ' . $response);
            return false;
        }

        return true;
    }
}
