<?php

namespace App\Services;

class CurrencyService
{
    private const SYMBOLS = [
        'THB' => '฿',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
        'SGD' => 'S$',
        'CNY' => '¥',
        'AUD' => 'A$',
    ];

    public static function symbol(string $code): string
    {
        return self::SYMBOLS[$code] ?? $code . ' ';
    }

    public static function format(float $amount, ?string $code = null): string
    {
        $code = $code ?: (\App\Models\Setting::get()['currency_code'] ?? 'THB');
        $decimals = $code === 'JPY' ? 0 : 2;

        return self::symbol($code) . number_format($amount, $decimals);
    }

    public static function options(): array
    {
        return array_keys(self::SYMBOLS);
    }

    /** Stripe amounts are integers in the currency's smallest unit (e.g. satang, cents). */
    public static function smallestUnitMultiplier(string $code): int
    {
        return $code === 'JPY' ? 1 : 100;
    }
}
