<?php

namespace App\Controllers\Public;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Request;
use App\Models\AdminUser;

/**
 * No-password login for front-of-house "checkin" staff — a long-lived bearer
 * link (see StaffController::generateCheckinLink()) they bookmark or scan as
 * a QR code once, instead of typing an email/password each event. Scoped
 * tightly to the checkin role: even a leaked link can't reach anything else,
 * since Auth::isCheckinOnly() still gates every other /admin route.
 */
class CheckinLinkController
{
    public function show(Request $request, string $token): void
    {
        $user = AdminUser::findByCheckinLinkTokenHash(hash('sha256', $token));

        if (!$user || !$user['is_active'] || $user['role'] !== 'checkin') {
            Flash::error(__('checkin_link.invalid'));
            redirect('admin/login');
        }

        Auth::loginAs($user);
        redirect('admin/checkin');
    }
}
