<?php

namespace App\Controllers\Public;

use App\Core\Request;
use App\Core\View;
use App\Models\Vendor;

/**
 * A linked vendor's read-only self-service view of their own booking history —
 * reached only via the short-lived magic link the LINE webhook hands out when
 * they type a keyword like "สถานะ" (see LineWebhookController). No password:
 * the vendor's own LINE account, already verified during linking, is the
 * authentication — this link is just a time-boxed bearer token for it.
 */
class VendorPortalController
{
    public function show(Request $request, string $token): void
    {
        $vendor = Vendor::findByValidPortalTokenHash(hash('sha256', $token));

        View::render('public/vendor_portal/show', [
            'title' => __('vendor_portal.title'),
            'vendor' => $vendor,
            'history' => $vendor ? Vendor::bookingHistory((int) $vendor['id']) : [],
        ], 'public');
    }
}
