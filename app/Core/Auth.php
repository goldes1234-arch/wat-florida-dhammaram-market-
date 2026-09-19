<?php

namespace App\Core;

use App\Models\AdminUser;

class Auth
{
    private static ?array $userCache = null;
    private static bool $resolved = false;

    public static function attempt(string $email, string $password): bool
    {
        $user = AdminUser::findByEmail($email);
        if (!$user || !$user['is_active']) {
            return false;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        Session::regenerate();
        Session::put('admin_id', (int) $user['id']);
        AdminUser::touchLogin((int) $user['id']);
        self::$userCache = $user;
        self::$resolved = true;

        return true;
    }

    public static function logout(): void
    {
        Session::forget('admin_id');
        self::$userCache = null;
        self::$resolved = true;
        Session::regenerate();
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$userCache;
        }
        self::$resolved = true;

        $id = Session::get('admin_id');
        if (!$id) {
            return self::$userCache = null;
        }

        $user = AdminUser::find((int) $id);
        if (!$user || !$user['is_active']) {
            return self::$userCache = null;
        }

        return self::$userCache = $user;
    }

    public static function isSuperAdmin(): bool
    {
        $user = self::user();
        return $user !== null && $user['role'] === 'super_admin';
    }

    /** The checkin role is restricted to /admin/checkin only — everything else in /admin redirects it there. */
    public static function isCheckinOnly(): bool
    {
        $user = self::user();
        return $user !== null && $user['role'] === 'checkin';
    }
}
