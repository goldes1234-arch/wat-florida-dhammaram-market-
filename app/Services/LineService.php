<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Minimal hand-rolled LINE Messaging API client — no SDK. broadcast() sends to
 * everyone who has added the temple's LINE Official Account as a friend (used
 * for admin/staff alerts); push() sends to one specific vendor who has linked
 * their LINE account (see LineWebhookController) for targeted messages. Both
 * are only active once a Channel Access Token is entered in Settings; silently
 * do nothing otherwise.
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

        return self::post('https://api.line.me/v2/bot/message/broadcast', $token, [
            'messages' => [
                ['type' => 'text', 'text' => mb_substr($message, 0, 5000)],
            ],
        ]);
    }

    /** Sends to one specific LINE user (vendors.line_user_id) — the targeted counterpart to broadcast(). */
    public static function push(string $userId, string $message): bool
    {
        $token = Setting::get()['line_oa_channel_access_token'] ?? '';
        if (!$token) {
            return false;
        }

        return self::post('https://api.line.me/v2/bot/message/push', $token, [
            'to' => $userId,
            'messages' => [
                ['type' => 'text', 'text' => mb_substr($message, 0, 5000)],
            ],
        ]);
    }

    /** Replies within a webhook handler using its replyToken — free, and the only way to answer a linking attempt. */
    public static function reply(string $replyToken, string $message): bool
    {
        $token = Setting::get()['line_oa_channel_access_token'] ?? '';
        if (!$token) {
            return false;
        }

        return self::post('https://api.line.me/v2/bot/message/reply', $token, [
            'replyToken' => $replyToken,
            'messages' => [
                ['type' => 'text', 'text' => mb_substr($message, 0, 5000)],
            ],
        ]);
    }

    /** Verifies the X-Line-Signature header on an incoming /line/webhook request. */
    public static function verifySignature(string $rawBody, string $signatureHeader, string $channelSecret): bool
    {
        if (!$channelSecret || !$signatureHeader) {
            return false;
        }
        $expected = base64_encode(hash_hmac('sha256', $rawBody, $channelSecret, true));
        return hash_equals($expected, $signatureHeader);
    }

    private static function post(string $url, string $token, array $body): bool
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_POSTFIELDS => json_encode($body),
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            error_log('LINE API call failed: ' . $curlError);
            return false;
        }
        if ($status >= 400) {
            error_log('LINE API error (' . $status . '): ' . $response);
            return false;
        }

        return true;
    }
}
