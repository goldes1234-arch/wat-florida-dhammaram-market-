<?php

namespace App\Core;

class Lang
{
    private static array $strings = [];
    private static string $locale = 'th';
    private static bool $loaded = false;

    public static function setLocale(string $locale): void
    {
        self::$locale = in_array($locale, ['th', 'en'], true) ? $locale : 'th';
        self::$loaded = false;
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    private static function ensureLoaded(): void
    {
        if (self::$loaded) {
            return;
        }
        $file = BASE_PATH . '/resources/lang/' . self::$locale . '.php';
        self::$strings = is_file($file) ? require $file : [];
        self::$loaded = true;
    }

    public static function get(string $key, array $replace = []): string
    {
        self::ensureLoaded();
        $value = self::$strings[$key] ?? $key;

        foreach ($replace as $search => $value2) {
            $value = str_replace(':' . $search, (string) $value2, $value);
        }

        return $value;
    }
}
