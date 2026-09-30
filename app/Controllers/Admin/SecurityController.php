<?php

namespace App\Controllers\Admin;

use App\Core\ActivityLog;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Totp;
use App\Core\View;
use App\Models\AdminUser;

/**
 * Self-service two-factor auth for the CURRENT admin's own account only — every
 * action here reads Auth::user()'s own id, never one from the URL, so there's
 * no way for one admin to touch another's 2FA. See Auth::attempt()/
 * pendingTwoFactorUser() for how this gates the login flow once enabled.
 */
class SecurityController
{
    public function index(Request $request): void
    {
        View::render('admin/security/index', [
            'title' => __('security.title'),
            'active' => 'security',
            'user' => Auth::user(),
        ], 'admin');
    }

    /**
     * Shows the QR/manual-key + confirm form. Reuses an existing not-yet-confirmed
     * secret instead of generating a new one each visit, so refreshing this page
     * doesn't invalidate a code the admin already scanned into their app.
     */
    public function enrollStart(Request $request): void
    {
        $user = Auth::user();
        if (!empty($user['totp_enabled'])) {
            redirect('admin/security');
        }

        $secret = $user['totp_secret'] ?: Totp::generateSecret();
        if ($secret !== $user['totp_secret']) {
            AdminUser::setPendingTotpSecret((int) $user['id'], $secret);
        }

        View::render('admin/security/enroll', [
            'title' => __('security.enroll_title'),
            'active' => 'security',
            'secret' => $secret,
            'qrUri' => Totp::provisioningUri($secret, $user['email'], __('common.app_name')),
        ], 'admin');
    }

    public function enrollConfirm(Request $request): void
    {
        $user = Auth::user();
        $code = $request->trimmed('code');
        $secret = (string) ($user['totp_secret'] ?? '');

        if (!$secret || !Totp::verify($secret, $code)) {
            Flash::error(__('security.invalid_code'));
            redirect('admin/security/enroll');
        }

        $backupCodes = $this->generateBackupCodes();
        $hashes = array_map(fn (string $c) => hash('sha256', $c), $backupCodes);
        AdminUser::enableTotp((int) $user['id'], $hashes);

        ActivityLog::record('security.2fa_enabled', 'admin_user', (int) $user['id'], __('activity.2fa_enabled', ['name' => $user['name']]));

        View::render('admin/security/backup_codes', [
            'title' => __('security.backup_codes_title'),
            'active' => 'security',
            'codes' => $backupCodes,
        ], 'admin');
    }

    public function disable(Request $request): void
    {
        $user = Auth::user();
        AdminUser::disableTotp((int) $user['id']);

        ActivityLog::record('security.2fa_disabled', 'admin_user', (int) $user['id'], __('activity.2fa_disabled', ['name' => $user['name']]));

        Flash::success(__('security.disabled_success'));
        redirect('admin/security');
    }

    /** @return string[] plain-text codes (8 uppercase hex chars each) — caller hashes before storing. */
    private function generateBackupCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }
        return $codes;
    }
}
