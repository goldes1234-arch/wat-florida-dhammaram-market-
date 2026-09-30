<?php

namespace App\Controllers\Public;

use App\Core\Request;
use App\Models\Setting;
use App\Models\Vendor;
use App\Services\LineService;

/**
 * Receives events from the temple's LINE Official Account (same Messaging API
 * channel as LineService::broadcast()/push()) and links a vendor's LINE account
 * to their vendors row when they message the OA with their phone number — the
 * only way to learn a vendor's LINE user id, since LINE never exposes it to us
 * otherwise. Once linked, admins can push a targeted message to that vendor.
 */
class LineWebhookController
{
    private const PORTAL_LINK_TTL_SECONDS = 900;
    private const PORTAL_KEYWORDS = ['สถานะ', 'ประวัติ', 'status', 'history'];

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
        if ($already) {
            if (!$replyToken) {
                return;
            }

            if (self::isPortalKeyword($text)) {
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

            LineService::reply($replyToken, __('line.already_linked', ['name' => $already['name']]));
            return;
        }

        $phone = preg_replace('/[^0-9]/', '', $text);
        $vendor = $phone !== '' ? Vendor::findByDigitsOnlyPhone($phone) : null;

        if (!$replyToken) {
            return;
        }

        if ($vendor) {
            Vendor::linkLine((int) $vendor['id'], $userId);
            LineService::reply($replyToken, __('line.link_success', ['name' => $vendor['name']]));
        } else {
            LineService::reply($replyToken, __('line.link_not_found'));
        }
    }

    private static function isPortalKeyword(string $text): bool
    {
        return in_array(mb_strtolower(trim($text)), self::PORTAL_KEYWORDS, true);
    }
}
