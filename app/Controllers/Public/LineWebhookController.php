<?php

namespace App\Controllers\Public;

use App\Core\Request;
use App\Models\Setting;
use App\Models\Vendor;
use App\Services\LineService;
use App\Services\NotificationService;
use App\Services\ReservationService;

/**
 * Receives events from the temple's LINE Official Account (same Messaging API
 * channel as LineService::broadcast()/push()) and links a vendor's LINE account
 * to their vendors row: the vendor messages the OA with their phone number (the only
 * way to learn their LINE user id, since LINE never exposes it otherwise), which opens
 * a link request that an admin approves. Once linked, admins can push targeted messages
 * and the vendor can confirm reservations / open their portal from the chat.
 */
class LineWebhookController
{
    private const PORTAL_LINK_TTL_SECONDS = 900;
    private const PORTAL_KEYWORDS = ['สถานะ', 'ประวัติ', 'status', 'history'];
    private const CONFIRM_KEYWORDS = ['ยืนยัน', 'ยืนยันสิทธิ์', 'ยืนยันการจอง', 'confirm'];

    public function handle(Request $request): void
    {
        $payload = (string) file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_X_LINE_SIGNATURE'] ?? '';
        $channelSecret = Setting::get()['line_channel_secret'] ?? '';

        if (!$channelSecret || !LineService::verifySignature($payload, $sigHeader, $channelSecret)) {
            http_response_code(400);
            echo 'Invalid signature';
            return;
        }

        $data = json_decode($payload, true);
        if (!is_array($data)) {
            http_response_code(400);
            return;
        }

        foreach ($data['events'] ?? [] as $event) {
            $this->handleEvent($event);
        }

        http_response_code(200);
        echo 'ok';
    }

    private function handleEvent(array $event): void
    {
        $userId = $event['source']['userId'] ?? null;
        $replyToken = $event['replyToken'] ?? null;
        if (!$userId) {
            return;
        }

        $type = $event['type'] ?? '';
        if ($type === 'follow') {
            if (!Vendor::findByLineUserId($userId) && $replyToken) {
                LineService::reply($replyToken, __('line.link_prompt'));
            }
            return;
        }

        if ($type !== 'message' || ($event['message']['type'] ?? '') !== 'text') {
            return;
        }

        $text = trim((string) ($event['message']['text'] ?? ''));

        $already = Vendor::findByLineUserId($userId);
        $action = self::classify($text, $already !== null);

        // Anything that is not a command or a bare phone number is a human conversation: the
        // admins reply from the LINE Official Account chat, so the bot must not talk over them.
        if ($action === self::ACTION_IGNORE || !$replyToken) {
            return;
        }

        if ($action === self::ACTION_CONFIRM) {
            LineService::reply($replyToken, $this->confirmReservationMessage($already));
            return;
        }

        if ($action === self::ACTION_PORTAL) {
            $token = bin2hex(random_bytes(24));
            Vendor::setPortalToken(
                (int) $already['id'],
                hash('sha256', $token),
                date('Y-m-d H:i:s', time() + self::PORTAL_LINK_TTL_SECONDS)
            );
            LineService::reply($replyToken, __('line.portal_link', [
                'link' => full_url('vendor/portal/' . $token),
                'minutes' => (string) (int) (self::PORTAL_LINK_TTL_SECONDS / 60),
            ]));
            return;
        }

        // ACTION_LINK: a bare phone number from a LINE account that is not linked yet.
        $vendor = Vendor::findByDigitsOnlyPhone(preg_replace('/[^0-9]/', '', $text));
        if ($vendor) {
            // A phone number is easy to learn or guess, so it only starts a request —
            // an admin has to approve it before this LINE account is trusted as the vendor.
            Vendor::requestLineLink((int) $vendor['id'], $userId);
            NotificationService::sendAdminLineLinkRequestAlert($vendor);
            LineService::reply($replyToken, __('line.link_pending'));
        } else {
            LineService::reply($replyToken, __('line.link_not_found'));
        }
    }

    public const ACTION_IGNORE = 'ignore';
    public const ACTION_CONFIRM = 'confirm';
    public const ACTION_PORTAL = 'portal';
    public const ACTION_LINK = 'link';

    /**
     * Decides what the bot does with a text message. Only commands (from a linked vendor) and a
     * message that is nothing but a phone number (from an unlinked account) get an automatic reply;
     * everything else is left for a person to answer in the OA chat.
     */
    public static function classify(string $text, bool $alreadyLinked): string
    {
        if ($alreadyLinked) {
            if (self::isConfirmKeyword($text)) {
                return self::ACTION_CONFIRM;
            }
            if (self::isPortalKeyword($text)) {
                return self::ACTION_PORTAL;
            }
            return self::ACTION_IGNORE;
        }

        return self::looksLikePhoneNumber($text) ? self::ACTION_LINK : self::ACTION_IGNORE;
    }

    /** True for a message that is only a phone number (digits, optionally with spaces, dashes, dots, +, parentheses). */
    public static function looksLikePhoneNumber(string $text): bool
    {
        $compact = preg_replace('/[\s\-.+()]/', '', trim($text));
        return (bool) preg_match('/^[0-9]{7,15}$/', (string) $compact);
    }

    /** Runs the LINE confirmation and returns the chat reply describing what happened. */
    private function confirmReservationMessage(array $vendor): string
    {
        $outcome = ReservationService::confirmViaLine($vendor);

        return match ($outcome['status']) {
            'confirmed' => __('line.confirm_done', [
                'event' => $outcome['lot']['event_name_th'],
                'lot' => $outcome['lot']['code'],
            ]),
            'multiple' => __('line.confirm_multiple', [
                'count' => (string) count($outcome['lots']),
                'links' => implode("\n", array_map(
                    static fn (array $lot) => '• ' . $lot['event_name_th'] . ' — ' . $lot['code'] . "\n" . full_url('reserve/' . $lot['reserved_token']),
                    $outcome['lots']
                )),
            ]),
            'error' => __('line.confirm_failed', ['error' => $outcome['error']]),
            default => __('line.confirm_none'),
        };
    }

    public static function isConfirmKeyword(string $text): bool
    {
        return in_array(mb_strtolower(trim($text)), self::CONFIRM_KEYWORDS, true);
    }

    public static function isPortalKeyword(string $text): bool
    {
        return in_array(mb_strtolower(trim($text)), self::PORTAL_KEYWORDS, true);
    }
}
