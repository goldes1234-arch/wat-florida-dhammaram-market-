<?php

namespace App\Core;

class Flash
{
    public static function set(string $type, string $message): void
    {
        $_SESSION['_flash'][$type][] = $message;
    }

    public static function success(string $message): void
    {
        self::set('success', $message);
    }

    public static function error(string $message): void
    {
        self::set('error', $message);
    }

    /** Reads and clears all flash messages for this render. */
    public static function consume(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $messages;
    }

    /** Keeps old form input across a redirect (e.g. after validation failure). */
    public static function setOld(array $input): void
    {
        $_SESSION['_old'] = $input;
    }

    public static function old(string $key, string $default = ''): string
    {
        return (string) ($_SESSION['_old'][$key] ?? $default);
    }

    public static function consumeOld(): void
    {
        unset($_SESSION['_old']);
    }
}
