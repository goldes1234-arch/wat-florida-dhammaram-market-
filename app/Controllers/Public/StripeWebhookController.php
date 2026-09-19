<?php

namespace App\Controllers\Public;

use App\Core\Request;
use App\Models\Booking;
use App\Models\Setting;
use App\Services\BookingService;
use App\Services\StripeService;

class StripeWebhookController
{
    public function handle(Request $request): void
    {
        $payload = (string) file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $webhookSecret = Setting::get()['stripe_webhook_secret'] ?? '';

        if (!$webhookSecret || !StripeService::verifyWebhookSignature($payload, $sigHeader, $webhookSecret)) {
            http_response_code(400);
            echo 'Invalid signature';
            return;
        }

        $event = json_decode($payload, true);
        if (!is_array($event)) {
            http_response_code(400);
            return;
        }

        if (($event['type'] ?? '') === 'checkout.session.completed') {
            $sessionId = $event['data']['object']['id'] ?? '';
            if ($sessionId) {
                $booking = Booking::findByStripeSession($sessionId);
                if ($booking && $booking['status'] === 'pending_payment') {
                    BookingService::confirm((int) $booking['id'], 'system', null, __('booking.default_note_confirm') . ' (Stripe webhook)');
                }
            }
        }

        http_response_code(200);
        echo 'ok';
    }
}
