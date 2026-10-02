<?php

namespace App\Controllers\Public;

use App\Core\Flash;
use App\Core\Request;
use App\Core\View;
use App\Models\Vendor;
use App\Services\NotificationService;

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
            'token' => $token,
        ], 'public');
    }

    /** A portable (GDPR-style) export of everything the system holds on this vendor. */
    public function exportData(Request $request, string $token): void
    {
        $vendor = Vendor::findByValidPortalTokenHash(hash('sha256', $token));
        if (!$vendor) {
            redirect('vendor/portal/' . $token);
        }

        $data = [
            'exported_at' => date('c'),
            'profile' => [
                'name' => $vendor['name'],
                'phone' => $vendor['phone'],
                'email' => $vendor['email'],
                'notes' => $vendor['notes'],
                'line_linked' => !empty($vendor['line_user_id']),
                'created_at' => $vendor['created_at'],
            ],
            'booking_history' => array_map(static fn (array $b) => [
                'booking_code' => $b['booking_code'],
                'event' => $b['event_name_th'],
                'lot' => $b['lot_code'],
                'status' => $b['status'],
                'price' => $b['price_at_booking'],
                'currency' => $b['currency_code'],
                'event_date' => $b['event_start_date'],
                'booked_at' => $b['created_at'],
            ], Vendor::bookingHistory((int) $vendor['id'])),
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="my-data-' . date('Y-m-d') . '.json"');
        header('Content-Length: ' . strlen($json));
        echo $json;
    }

    /**
     * The vendor asking for their data to be deleted — never acted on automatically here;
     * just flags the row and alerts admins, who review and action it manually (booking/
     * payment history may need to be kept regardless of the request, which only an admin
     * weighing the specific situation can judge).
     */
    public function requestDeletion(Request $request, string $token): void
    {
        $vendor = Vendor::findByValidPortalTokenHash(hash('sha256', $token));
        if (!$vendor) {
            redirect('vendor/portal/' . $token);
        }

        if (empty($vendor['deletion_requested_at'])) {
            Vendor::requestDeletion((int) $vendor['id']);
            NotificationService::sendAdminVendorDeletionRequestAlert($vendor);
        }

        Flash::success(__('vendor_portal.request_deletion_success'));
        redirect('vendor/portal/' . $token);
    }
}
