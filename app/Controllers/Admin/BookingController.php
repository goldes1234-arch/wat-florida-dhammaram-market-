<?php

namespace App\Controllers\Admin;

use App\Core\ActivityLog;
use App\Core\Auth;
use App\Core\EventAccess;
use App\Core\Flash;
use App\Core\Request;
use App\Core\View;
use App\Models\Booking;
use App\Models\BookingStatusLog;
use App\Models\Event;
use App\Services\BookingService;

class BookingController
{
    public function index(Request $request): void
    {
        $eventId = $request->query['event_id'] ?? '';
        $status = $request->query['status'] ?? '';
        $sort = ($request->query['sort'] ?? '') === 'asc' ? 'asc' : 'desc';
        $filterEventId = $eventId !== '' ? (int) $eventId : null;
        $perPage = 50;
        $search = trim((string) ($request->query['q'] ?? ''));

        if ($filterEventId && !EventAccess::allowed($filterEventId)) {
            $filterEventId = null;
            $eventId = '';
        }

        $allowedEventIds = EventAccess::assignedEventIds();
        $statusFilter = $status !== '' ? $status : null;
        $total = Booking::countForAdmin($filterEventId, $statusFilter, $allowedEventIds, $search);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($request->query['page'] ?? 1)), $totalPages);

        View::render('admin/bookings/index', [
            'title' => __('booking.list_title'),
            'active' => 'bookings',
            'bookings' => Booking::forAdmin($filterEventId, $statusFilter, $sort, $allowedEventIds, $page, $perPage, $search),
            'search' => $search,
            'statusCounts' => Booking::statusCountsForAdmin($filterEventId, $allowedEventIds, $search),
            'events' => EventAccess::filterEvents(Event::allForAdmin()),
            'selectedEvent' => $eventId,
            'selectedStatus' => $status,
            'selectedSort' => $sort,
            'page' => $page,
            'totalPages' => $totalPages,
        ], 'admin');
    }

    public function export(Request $request): void
    {
        $eventId = $request->query['event_id'] ?? '';
        $status = $request->query['status'] ?? '';
        $sort = ($request->query['sort'] ?? '') === 'asc' ? 'asc' : 'desc';
        $filterEventId = $eventId !== '' ? (int) $eventId : null;

        if ($filterEventId && !EventAccess::allowed($filterEventId)) {
            $filterEventId = null;
        }

        $bookings = Booking::forAdmin(
            $filterEventId, $status !== '' ? $status : null, $sort, EventAccess::assignedEventIds(),
            null, 50, trim((string) ($request->query['q'] ?? ''))
        );

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="bookings-' . date('Y-m-d-His') . '.csv"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Thai text opens correctly in Excel
        fputcsv($out, [
            __('booking.code'), __('event.singular'), __('lot.singular'), __('booking.booker_name'),
            __('booking.booker_phone'), __('booking.booker_email'), __('booking.payment_method'),
            __('common.status'), __('lot.price'), __('common.date'), __('booking.items_for_sale_label'),
        ]);
        foreach ($bookings as $b) {
            fputcsv($out, [
                $b['booking_code'], $b['event_name_th'], $b['lot_code'], $b['booker_name'],
                $b['booker_phone'], $b['booker_email'], payment_method_label($b['payment_method']),
                booking_status_label($b['status']), $b['price_at_booking'], $b['created_at'], $b['items_for_sale'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    public function show(Request $request, string $id): void
    {
        $booking = Booking::find((int) $id);
        if (!$booking) {
            redirect('admin/bookings');
        }
        $this->denyUnlessAllowed((int) $booking['event_id']);

        View::render('admin/bookings/show', [
            'title' => __('booking.detail_title'),
            'active' => 'bookings',
            'booking' => $booking,
            'logs' => BookingStatusLog::forBooking((int) $id),
        ], 'admin');
    }

    public function confirm(Request $request, string $id): void
    {
        $this->denyUnlessAllowedForBooking((int) $id);

        $note = $request->trimmed('note') ?: __('booking.default_note_confirm');
        $result = BookingService::confirm((int) $id, 'admin', Auth::user()['id'] ?? null, $note);
        $this->respond($result, 'booking.confirm_success', $id);
    }

    public function reject(Request $request, string $id): void
    {
        $this->denyUnlessAllowedForBooking((int) $id);

        $note = $request->trimmed('note') ?: __('booking.default_note_reject');
        $result = BookingService::reject((int) $id, Auth::user()['id'] ?? null, $note);
        $this->respond($result, 'booking.reject_success', $id);
    }

    public function cancel(Request $request, string $id): void
    {
        $this->denyUnlessAllowedForBooking((int) $id);

        $note = $request->trimmed('note') ?: __('booking.default_note_cancel_admin');
        $result = BookingService::cancel((int) $id, 'admin', Auth::user()['id'] ?? null, $note);
        $this->respond($result, 'booking.cancel_success', $id);
    }

    public function refund(Request $request, string $id): void
    {
        $this->denyUnlessAllowedForBooking((int) $id);

        // Refunding past the window is a super_admin-only judgment call (finance can't), and
        // the reason is mandatory — BookingService enforces the reason, this enforces who.
        $overrideReason = null;
        if (($request->post['override_window'] ?? '') === '1') {
            if (!Auth::isSuperAdmin()) {
                Flash::error(__('booking.refund_override_denied'));
                redirect('admin/bookings/' . $id);
            }
            $overrideReason = $request->trimmed('override_reason');
        }

        $result = BookingService::refund((int) $id, Auth::user()['id'] ?? null, $overrideReason);
        if ($result['success'] && $overrideReason !== null) {
            ActivityLog::record('booking.refund_override', 'booking', (int) $id, __('activity.refund_override', ['reason' => $overrideReason]));
        }
        $this->respond($result, 'booking.refund_success', $id);
    }

    public function updateItems(Request $request, string $id): void
    {
        $this->denyUnlessAllowedForBooking((int) $id);

        $items = $request->trimmed('items_for_sale');
        if (mb_strlen($items) > 200) {
            Flash::error(__('booking.items_for_sale_too_long'));
        } else {
            Booking::setItemsForSale((int) $id, $items);
            Flash::success(__('booking.items_for_sale_saved'));
        }
        redirect('admin/bookings/' . $id);
    }

    public function destroySelected(Request $request): void
    {
        $ids = $request->post['ids'] ?? [];
        $result = Booking::deleteMany(is_array($ids) ? $ids : [], EventAccess::assignedEventIds());
        $this->respondDelete($request, $result);
    }

    public function destroyAll(Request $request): void
    {
        $eventId = $request->post['event_id'] ?? '';
        $status = $request->post['status'] ?? '';
        $result = Booking::deleteAllSafe(
            $eventId !== '' ? (int) $eventId : null,
            $status !== '' ? $status : null,
            EventAccess::assignedEventIds()
        );
        $this->respondDelete($request, $result);
    }

    private function denyUnlessAllowed(int $eventId): void
    {
        if (!EventAccess::allowed($eventId)) {
            Flash::error(__('common.access_denied'));
            redirect('admin/bookings');
        }
    }

    private function denyUnlessAllowedForBooking(int $bookingId): void
    {
        $booking = Booking::find($bookingId);
        if ($booking) {
            $this->denyUnlessAllowed((int) $booking['event_id']);
        }
    }

    private function respondDelete(Request $request, array $result): void
    {
        if ($result['deleted'] === 0 && $result['skipped'] === 0) {
            Flash::error(__('booking.delete_none_selected'));
        } elseif ($result['skipped'] > 0) {
            Flash::success(__('booking.deleted_partial', [
                'deleted' => $result['deleted'],
                'skipped' => $result['skipped'],
            ]));
        } else {
            Flash::success(__('booking.deleted_success_count', ['count' => $result['deleted']]));
        }

        $query = [];
        foreach (['event_id', 'status', 'sort'] as $key) {
            if (!empty($request->post[$key])) {
                $query[$key] = $request->post[$key];
            }
        }
        redirect('admin/bookings' . ($query ? '?' . http_build_query($query) : ''));
    }

    private function respond(array $result, string $successKey, string $id): void
    {
        if ($result['success']) {
            Flash::success(__($successKey));
        } else {
            Flash::error($result['error']);
        }

        // One-click actions from the list send the current list URL back so the admin stays where they were.
        $back = (string) ($_POST['return'] ?? '');
        if ($back !== '' && str_starts_with($back, 'admin/bookings') && !str_contains($back, '//') && !str_contains($back, '..')) {
            redirect($back);
        }
        redirect('admin/bookings/' . $id);
    }
}
