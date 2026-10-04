<?php

namespace App\Support;

class Validator
{
    public static function required($value): bool
    {
        return trim((string) $value) !== '';
    }

    public static function email(string $value): bool
    {
        return $value === '' || filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function date(string $value): bool
    {
        return $value !== '' && strtotime($value) !== false;
    }

    public static function number($value): bool
    {
        return is_numeric($value);
    }

    public static function positiveNumber($value): bool
    {
        return is_numeric($value) && (float) $value >= 0;
    }

    public static function inList($value, array $allowed): bool
    {
        return in_array($value, $allowed, true);
    }

    /** An absolute http(s) URL — blank is allowed. Rejects javascript:, data:, mailto: and friends, which FILTER_VALIDATE_URL lets through. */
    public static function httpUrl(string $value): bool
    {
        if ($value === '') {
            return true;
        }
        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        return filter_var($value, FILTER_VALIDATE_URL) !== false && in_array($scheme, ['http', 'https'], true);
    }

    /** A phone number people can dial: digits with optional +, spaces, dashes, dots, parentheses — blank is allowed. */
    public static function dialable(string $value): bool
    {
        if ($value === '') {
            return true;
        }
        return (bool) preg_match('/^\+?[0-9][0-9\s\-.()]{5,24}$/', $value);
    }
}
