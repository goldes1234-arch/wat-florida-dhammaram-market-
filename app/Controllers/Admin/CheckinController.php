<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Request;
use App\Core\View;
use App\Models\Booking;

class CheckinController
{
    public function lookupForm(Request $request): void
    {
        View::render('admin/checkin/lookup', [
            'title' => __('checkin.title'),
            'active' => 'checkin',
        ], 'admin');
    }

    public function search(Request $request): void
    {
        $searchBy = $request->trimmed('search_by', 'code') ?: 'code';
        $query = $request->trimmed('query');

        if ($query === '') {
            Flash::error(__('checkin.enter_search'));
            redirect('admin/checkin');
        }

        if ($searchBy === 'code') {
            $booking = Booking::findByCode(strtoupper($query));
            if (!$booking) {
                Flash::error(__('public.search_not_found'));
                redirect('admin/checkin');
            }
            redirect('admin/checkin/' . $booking['booking_code']);
        }

        $results = Booking::searchByField($searchBy, $query);

        if (!$results) {
            Flash::error(__('public.search_not_found'));
            redirect('admin/checkin');
        }

        if (count($results) === 1) {
            redirect('admin/checkin/' . $results[0]['booking_code']);
        }

        View::render('admin/checkin/results', [
            'title' => __('checkin.title'),
            'active' => 'checkin',
            'bookings' => $results,
        ], 'admin');
    }

    public function show(Request $request, string $code): void
    {
        $booking = Booking::findByCode(strtoupper($code));
        if (!$booking) {
            Flash::error(__('public.search_not_found'));
            redirect('admin/checkin');
        }

        View::render('admin/checkin/show', [
            'title' => __('checkin.title'),
            'active' => 'checkin',
            'booking' => $booking,
        ], 'admin');
    }

    public function confirm(Request $request, string $code): void
    {
        $booking = Booking::findByCode(strtoupper($code));
        if (!$booking) {
            redirect('admin/checkin');
        }

        if ($booking['status'] !== 'booked') {
            Flash::error(__('checkin.not_confirmed_yet'));
            redirect('admin/checkin/' . $booking['booking_code']);
        } elseif ($booking['checked_in_at']) {
            Flash::error(__('checkin.already_checked_in'));
        } else {
            Booking::checkIn((int) $booking['id'], Auth::user()['id'] ?? null);
            Flash::success(__('checkin.success'));
        }

        redirect('admin/checkin/' . $booking['booking_code']);
    }
}
