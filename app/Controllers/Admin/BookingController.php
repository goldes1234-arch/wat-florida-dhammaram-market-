<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
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

        View::render('admin/bookings/index', [
            'title' => __('booking.list_title'),
            'active' => 'bookings',
            'bookings' => Booking::forAdmin($eventId !== '' ? (int) $eventId : null, $status !== '' ? $status : null, $sort),
            'events' => Event::allForAdmin(),
            'selectedEvent' => $eventId,
            'selectedStatus' => $status,
            'selectedSort' => $sort,
        ], 'admin');
    }

    public function export(Request $request): void
    {
        $eventId = $request->query['event_id'] ?? '';
        $status = $request->query['status'] ?? '';
        $sort = ($request->query['sort'] ?? '') === 'asc' ? 'asc' : 'desc';

        $bookings = Booking::forAdmin($eventId !== '' ? (int) $eventId : null, $status !== '' ? $status : null, $sort);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="bookings-' . date('Y-m-d-His') . '.csv"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Thai text opens correctly in Excel
        fputcsv($out, [
            __('booking.code'), __('event.singular'), __('lot.singular'), __('booking.booker_name'),
            __('booking.booker_phone'), __('booking.booker_email'), __('booking.payment_method'),
            __('common.status'), __('lot.price'), __('common.date'),
        ]);
        foreach ($bookings as $b) {
            fputcsv($out, [
                $b['booking_code'], $b['event_name_th'], $b['lot_code'], $b['booker_name'],
                $b['booker_phone'], $b['booker_email'], payment_method_label($b['payment_method']),
                booking_status_label($b['status']), $b['price_at_booking'], $b['created_at'],
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

        View::render('admin/bookings/show', [
            'title' => __('booking.detail_title'),
            'active' => 'bookings',
            'booking' => $booking,
            'logs' => BookingStatusLog::forBooking((int) $id),
        ], 'admin');
    }

    public function confirm(Request $request, string $id): void
    {
        $note = $request->trimmed('note') ?: __('booking.default_note_confirm');
        $result = BookingService::confirm((int) $id, 'admin', Auth::user()['id'] ?? null, $note);
        $this->respond($result, 'booking.confirm_success', $id);
    }

    public function reject(Request $request, string $id): void
    {
        $note = $request->trimmed('note') ?: __('booking.default_note_reject');
        $result = BookingService::reject((int) $id, Auth::user()['id'] ?? null, $note);
        $this->respond($result, 'booking.reject_success', $id);
    }

    public function cancel(Request $request, string $id): void
    {
        $note = $request->trimmed('note') ?: __('booking.default_note_cancel_admin');
        $result = BookingService::cancel((int) $id, 'admin', Auth::user()['id'] ?? null, $note);
        $this->respond($result, 'booking.cancel_success', $id);
    }

    public function destroySelected(Request $request): void
    {
        $ids = $request->post['ids'] ?? [];
        $result = Booking::deleteMany(is_array($ids) ? $ids : []);
        $this->respondDelete($request, $result);
    }

    public function destroyAll(Request $request): void
    {
        $eventId = $request->post['event_id'] ?? '';
        $status = $request->post['status'] ?? '';
        $result = Booking::deleteAllSafe($eventId !== '' ? (int) $eventId : null, $status !== '' ? $status : null);
        $this->respondDelete($request, $result);
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
        redirect('admin/bookings/' . $id);
    }
}
