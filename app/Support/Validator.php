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
}
