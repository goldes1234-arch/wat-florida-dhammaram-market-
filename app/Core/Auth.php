<?php

namespace App\Core;

use App\Models\AdminUser;

class Auth
{
    private static ?array $userCache = null;
    private static bool $resolved = false;

    private const PENDING_2FA_TTL_SECONDS = 300;

    /**
     * Checks email/password only. Returns 'invalid', or 'needs_2fa' when the
     * account has 2FA enabled (password alone isn't enough — see
     * pendingTwoFactorUser()/completeTwoFactorLogin(), which AuthController's
     * verify-2fa step calls once the TOTP code checks out), or 'success' when
     * the account has no 2FA and login is already complete.
     */
    public static function attempt(string $email, string $password): string
    {
        $user = AdminUser::findByEmail($email);
        if (!$user || !$user['is_active']) {
            return 'invalid';
        }
        if (!password_verify($password, $user['password_hash'])) {
            return 'invalid';
        }

        if (!empty($user['totp_enabled'])) {
            Session::regenerate();
            Session::put('pending_2fa_admin_id', (int) $user['id']);
            Session::put('pending_2fa_at', time());
            return 'needs_2fa';
        }

        self::completeLogin($user);
        return 'success';
    }

    /** The account mid-login (password verified, TOTP not yet), or null if there isn't one or it expired. */
    public static function pendingTwoFactorUser(): ?array
    {
        $id = Session::get('pending_2fa_admin_id');
        $at = Session::get('pending_2fa_at');

        if (!$id || !$at || (time() - (int) $at) > self::PENDING_2FA_TTL_SECONDS) {
            Session::forget('pending_2fa_admin_id');
            Session::forget('pending_2fa_at');
            return null;
        }

        $user = AdminUser::find((int) $id);
        return ($user && $user['is_active']) ? $user : null;
    }

    /** Finishes a login that was waiting on pendingTwoFactorUser() — call only after verifying the TOTP/backup code. */
    public static function completeTwoFactorLogin(): bool
    {
        $user = self::pendingTwoFactorUser();
        if (!$user) {
            return false;
        }

        self::completeLogin($user);
        return true;
    }

    private static function completeLogin(array $user): void
    {
        Session::regenerate();
        Session::put('admin_id', (int) $user['id']);
        Session::forget('pending_2fa_admin_id');
        Session::forget('pending_2fa_at');
        AdminUser::touchLogin((int) $user['id']);
        self::$userCache = $user;
        self::$resolved = true;
    }

    public static function logout(): void
    {
        Session::forget('admin_id');
        Session::forget('pending_2fa_admin_id');
        Session::forget('pending_2fa_at');
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

    /**
     * Finance sees money (dashboard, reports, bookings, refunds) but not event/lot/
     * vendor management or anything super_admin-only — see the 'not_finance' and
     * 'finance_or_super_admin' router middleware for where that's enforced.
     */
    public static function isFinance(): bool
    {
        $user = self::user();
        return $user !== null && $user['role'] === 'finance';
    }
}
