<?php

namespace App\Controllers\Public;

use App\Core\Request;
use App\Core\View;
use App\Models\Setting;
use App\Services\BookingService;

/** Static legal pages (English only — the temple is in Florida, USA). */
class LegalController
{
    public function privacy(Request $request): void
    {
        View::render('public/legal/privacy', [
            'title' => 'Privacy Policy',
            'settings' => Setting::get(),
        ], 'public');
    }

    public function terms(Request $request): void
    {
        View::render('public/legal/terms', [
            'title' => 'Terms of Use',
            'settings' => Setting::get(),
            'policy' => BookingService::policyDays(),
        ], 'public');
    }
}
