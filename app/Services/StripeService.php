<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Minimal hand-rolled Stripe REST client (Checkout Sessions only) — no SDK/Composer
 * dependency, since Stripe's API is plain form-encoded HTTP. Only used once the
 * admin has entered a secret key in Settings; until then isEnabled() is false and
 * the public booking form simply doesn't offer the "pay online" option.
 */
class StripeService
{
    public static function isEnabled(): bool
    {
        $settings = Setting::get();
        return !empty($settings['stripe_secret_key']) && empty($settings['stripe_suspended']);
    }

    public static function passesFeeToCustomer(): bool
    {
        return !empty(Setting::get()['stripe_pass_fee_to_customer']);
    }

    /**
     * The surcharge to add on top of $basePrice so that, after Stripe's own cut of
     * the *total* charged, the org still nets exactly $basePrice. Shared by the
     * checkout session builder and the public booking form's price preview so the
     * customer sees the real total before ever reaching Stripe.
     */
    public static function calculatePassThroughFee(float $basePrice): float
    {
        $settings = Setting::get();
        $feePercent = ((float) $settings['stripe_fee_percent']) / 100;
        $feeFixed = (float) $settings['stripe_fee_fixed'];
        $totalCharged = ($basePrice + $feeFixed) / (1 - $feePercent);
        return round($totalCharged - $basePrice, 2);
    }

    public static function createCheckoutSession(array $booking, array $lot, array $event): ?array
    {
        $secretKey = Setting::get()['stripe_secret_key'] ?? '';
        if (!$secretKey) {
            return null;
        }

        $settings = Setting::get();
        $currency = $booking['currency_code'];
        $multiplier = CurrencyService::smallestUnitMultiplier($currency);
        $unitAmount = (int) round($booking['price_at_booking'] * $multiplier);
        $eventName = $event['name_th'] ?: $event['name_en'];

        $lineItems = [[
            'quantity' => 1,
            'price_data' => [
                'currency' => strtolower($currency),
                'unit_amount' => $unitAmount,
                'product_data' => [
                    'name' => $eventName . ' — ' . __('booking.lot_label') . ' ' . $lot['code'],
                ],
            ],
        ]];

        // Gross up so that after Stripe's own cut, the org still nets the full lot
        // price — the fee shows as its own transparent line item rather than being
        // silently folded into the lot's price.
        if (!empty($settings['stripe_pass_fee_to_customer'])) {
            $feeAmount = self::calculatePassThroughFee((float) $booking['price_at_booking']);

            if ($feeAmount > 0) {
                $lineItems[] = [
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => strtolower($currency),
                        'unit_amount' => (int) round($feeAmount * $multiplier),
                        'product_data' => [
                            'name' => __('booking.stripe_fee_line_item'),
                        ],
                    ],
                ];
            }
        }

        $params = [
            'mode' => 'payment',
            'success_url' => full_url("booking/{$booking['booking_code']}/stripe-return") . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => full_url("booking/{$booking['booking_code']}/stripe-cancelled"),
            'client_reference_id' => $booking['booking_code'],
            'line_items' => $lineItems,
            'metadata' => [
                'booking_id' => (string) $booking['id'],
                'booking_code' => $booking['booking_code'],
            ],
            // Force 3D Secure authentication on every card payment (rather than leaving
            // it to Stripe's risk-based "automatic" default) so every transaction gets
            // the extra bank-side challenge and the fraud-liability shift it provides.
            'payment_method_options' => [
                'card' => [
                    'request_three_d_secure' => 'any',
                ],
            ],
        ];

        if (!empty($booking['booker_email'])) {
            $params['customer_email'] = $booking['booker_email'];
        }

        return self::request('POST', 'checkout/sessions', $params, $secretKey);
    }

    public static function retrieveSession(string $sessionId): ?array
    {
        $secretKey = Setting::get()['stripe_secret_key'] ?? '';
        if (!$secretKey) {
            return null;
        }
        return self::request('GET', 'checkout/sessions/' . urlencode($sessionId), [], $secretKey);
    }

    public static function verifyWebhookSignature(string $payload, string $sigHeader, string $webhookSecret, int $tolerance = 300): bool
    {
        $parts = [];
        foreach (explode(',', $sigHeader) as $pair) {
            $kv = array_pad(explode('=', trim($pair), 2), 2, null);
            if ($kv[0] !== null) {
                $parts[$kv[0]][] = $kv[1];
            }
        }

        $timestamp = $parts['t'][0] ?? null;
        $signatures = $parts['v1'] ?? [];
        if (!$timestamp || !$signatures) {
            return false;
        }
        if (abs(time() - (int) $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $webhookSecret);
        foreach ($signatures as $sig) {
            if (hash_equals($expected, (string) $sig)) {
                return true;
            }
        }
        return false;
    }

    private static function request(string $method, string $endpoint, array $params, string $secretKey): ?array
    {
        $url = 'https://api.stripe.com/v1/' . $endpoint;
        $ch = curl_init();
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secretKey],
        ];

        if ($method === 'POST') {
            $options[CURLOPT_URL] = $url;
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($params);
        } else {
            $options[CURLOPT_URL] = $params ? $url . '?' . http_build_query($params) : $url;
        }

        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            error_log('Stripe request failed: ' . $curlError);
            return null;
        }

        $decoded = json_decode($response, true);

        if ($status >= 400) {
            error_log('Stripe API error (' . $status . '): ' . $response);
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }
}
