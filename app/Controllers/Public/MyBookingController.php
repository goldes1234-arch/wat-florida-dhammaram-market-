<?php

namespace App\Controllers\Public;

use App\Core\Flash;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\View;
use App\Models\Booking;
use App\Models\Setting;
use App\Services\BookingService;

class MyBookingController
{
    public function lookupForm(Request $request): void
    {
        View::render('public/my_booking/lookup', [
            'title' => __('public.search_title'),
        ], 'public');
    }

    public function search(Request $request): void
    {
        $limitPerHour = (int) (Setting::get()['booking_rate_limit_per_hour'] ?? 5) * 3;
        if (RateLimiter::tooMany($request->ip(), 'lookup', $limitPerHour)) {
            Flash::error(__('booking.rate_limited'));
            redirect('my-booking');
        }

        $code = strtoupper($request->trimmed('booking_code'));
        if ($code !== '') {
            $booking = Booking::findByCode($code);
            if ($booking) {
                redirect('my-booking/' . $booking['booking_code']);
            }
            Flash::error(__('public.search_not_found'));
            redirect('my-booking');
        }

        $phone = $request->trimmed('phone');
        $email = $request->trimmed('email');
        if ($phone === '' || $email === '') {
            Flash::error(__('public.search_hint'));
            redirect('my-booking');
        }

        $bookings = Booking::findByPhoneAndEmail($phone, $email);
        if (!$bookings) {
            Flash::error(__('public.search_not_found'));
            redirect('my-booking');
        }
        if (count($bookings) === 1) {
            redirect('my-booking/' . $bookings[0]['booking_code']);
        }

        View::render('public/my_booking/results', [
            'title' => __('public.search_title'),
            'bookings' => $bookings,
        ], 'public');
    }

    public function show(Request $request, string $code): void
    {
        $booking = Booking::findByCode(strtoupper($code));
        if (!$booking) {
            Flash::error(__('public.search_not_found'));
            redirect('my-booking');
        }

        View::render('public/my_booking/show', [
            'title' => __('public.booking_status'),
            'booking' => $booking,
            'canCancel' => BookingService::canGuestCancel($booking),
            'settings' => Setting::get(),
        ], 'public');
    }

    public function cancel(Request $request, string $code): void
    {
        $booking = Booking::findByCode(strtoupper($code));
        if (!$booking) {
            redirect('my-booking');
        }

        if (!BookingService::canGuestCancel($booking)) {
            Flash::error(__('public.cannot_cancel'));
            redirect('my-booking/' . $booking['booking_code']);
        }

        $result = BookingService::cancel((int) $booking['id'], 'guest', null, __('booking.default_note_cancel_guest'));
        if ($result['success']) {
            Flash::success(__('public.cancel_success'));
        } else {
            Flash::error($result['error']);
        }
        redirect('my-booking/' . $booking['booking_code']);
    }
}
