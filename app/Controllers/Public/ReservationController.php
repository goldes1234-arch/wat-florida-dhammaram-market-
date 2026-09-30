<?php

namespace App\Controllers\Public;

use App\Core\Flash;
use App\Core\Request;
use App\Core\View;
use App\Models\Lot;
use App\Services\ReservationService;

class ReservationController
{
    public function show(Request $request, string $token): void
    {
        $lot = Lot::findByReservedToken($token);

        View::render('public/reservation/show', [
            'title' => __('reservation.confirm_title'),
            'lot' => $lot,
            'token' => $token,
        ], 'public');
    }

    public function confirm(Request $request, string $token): void
    {
        $result = ReservationService::confirm($token);

        if (!$result['success']) {
            Flash::error($result['error']);
        } else {
            Flash::success(__('reservation.confirmed_success'));
        }

        redirect('reserve/' . $token);
    }
}
