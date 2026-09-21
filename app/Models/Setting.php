<?php

namespace App\Models;

class Setting extends Model
{
    private static ?array $cache = null;

    public static function get(bool $fresh = false): array
    {
        if (self::$cache === null || $fresh) {
            $stmt = self::db()->query('SELECT * FROM settings WHERE id = 1');
            self::$cache = $stmt->fetch() ?: [];
        }
        return self::$cache;
    }

    public static function update(array $data): void
    {
        $columns = [
            'org_name', 'org_address', 'org_phone', 'org_email', 'logo_path', 'hero_banner_image',
            'facebook_url', 'line_oa_id', 'google_maps_url', 'website_url', 'youtube_url',
            'bank_name', 'bank_account_name', 'bank_account_number', 'promptpay_id',
            'currency_code', 'default_locale', 'cancellation_cutoff_days', 'booking_rate_limit_per_hour',
            'stripe_publishable_key', 'stripe_secret_key', 'stripe_webhook_secret',
            'stripe_pass_fee_to_customer', 'stripe_fee_percent', 'stripe_fee_fixed',
            'line_oa_channel_access_token',
            'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password',
            'smtp_from_email', 'smtp_from_name',
        ];

        $set = [];
        $params = [];
        foreach ($columns as $col) {
            if (array_key_exists($col, $data)) {
                $set[] = "$col = :$col";
                $params[$col] = $data[$col];
            }
        }

        if (!$set) {
            return;
        }

        $sql = 'UPDATE settings SET ' . implode(', ', $set) . ' WHERE id = 1';
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);

        self::$cache = null;
    }
}
