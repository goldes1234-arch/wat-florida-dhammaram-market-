<?php

namespace App\Controllers\Public;

use App\Core\Flash;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Upload;
use App\Core\View;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Lot;
use App\Models\Setting;
use App\Services\BookingService;
use App\Services\EventStatusService;
use App\Services\NotificationService;
use App\Services\StripeService;
use App\Support\Validator;

class BookingController
{
    public function create(Request $request, string $slug, string $lotId): void
    {
        $event = Event::findBySlug($slug);
        if (!$event) {
            redirect('');
        }

        if (EventStatusService::compute($event) !== EventStatusService::OPEN) {
            Flash::error(__('booking.event_not_open'));
            redirect('events/' . $slug);
        }

        $lot = Lot::find((int) $lotId);
        if (!$lot || (int) $lot['event_id'] !== (int) $event['id']) {
            redirect('events/' . $slug);
        }
        if ($lot['status'] !== 'available') {
            Flash::error(__('booking.lot_taken'));
            redirect('events/' . $slug);
        }

        $stripeFeePassThrough = StripeService::isEnabled() && StripeService::passesFeeToCustomer();

        View::render('public/booking/create', [
            'title' => __('public.book_this_lot'),
            'event' => $event,
            'lot' => $lot,
            'stripeEnabled' => StripeService::isEnabled(),
            'stripeFeePassThrough' => $stripeFeePassThrough,
            'stripeFeeAmount' => $stripeFeePassThrough ? StripeService::calculatePassThroughFee((float) $lot['price']) : 0,
        ], 'public');
    }

    public function store(Request $request, string $slug, string $lotId): void
    {
        $event = Event::findBySlug($slug);
        if (!$event) {
            redirect('');
        }

        if (EventStatusService::compute($event) !== EventStatusService::OPEN) {
            Flash::error(__('booking.event_not_open'));
            redirect('events/' . $slug);
        }

        // Honeypot: bots tend to fill every field; real visitors never see this one.
        if ($request->trimmed('website') !== '') {
            Flash::error(__('booking.honeypot_triggered'));
            redirect('events/' . $slug);
        }

        $settings = Setting::get();
        if (RateLimiter::tooMany($request->ip(), 'booking', (int) $settings['booking_rate_limit_per_hour'])) {
            Flash::error(__('booking.rate_limited'));
            redirect('events/' . $slug);
        }

        $name = $request->trimmed('booker_name');
        $phone = $request->trimmed('booker_phone');
        $email = $request->trimmed('booker_email');
        $method = $request->trimmed('payment_method');

        $errors = [];
        if (!Validator::required($name)) {
            $errors[] = __('validation.required', ['field' => __('booking.booker_name')]);
        }
        if (!Validator::required($phone)) {
            $errors[] = __('validation.required', ['field' => __('booking.booker_phone')]);
        }
        if ($email !== '' && !Validator::email($email)) {
            $errors[] = __('validation.invalid_email');
        }

        $allowedMethods = ['onsite_cash'];
        if (StripeService::isEnabled()) {
            $allowedMethods[] = 'stripe';
        }
        if (!in_array($method, $allowedMethods, true)) {
            $errors[] = __('validation.generic_error');
        }

        if ($errors) {
            Flash::error(implode(' ', $errors));
            redirect('events/' . $slug . '/book/' . $lotId);
        }

        $result = BookingService::attemptBooking((int) $lotId, (int) $event['id'], [
            'booker_name' => $name,
            'booker_phone' => $phone,
            'booker_email' => $email ?: null,
            'payment_method' => $method,
            'currency_code' => $settings['currency_code'],
        ]);

        if (!$result['success']) {
            Flash::error($result['error']);
            redirect('events/' . $slug);
        }

        $shopPhotoFile = $request->file('shop_photo');
        if ($shopPhotoFile) {
            $error = null;
            $path = Upload::storeImage($shopPhotoFile, 'shops/' . $event['id'], $error);
            if ($path) {
                Booking::setShopPhoto((int) $result['booking_id'], $path);
            }
            // A bad photo upload never blocks the booking itself — the lot is already
            // locked, so we just skip the photo rather than surface an error here.
        }

        $booking = Booking::find($result['booking_id']);
        NotificationService::sendBookingConfirmation($booking, $result['lot'], $event);
        NotificationService::sendAdminBookingAlert($booking, $result['lot'], $event);

        if ($method === 'stripe') {
            $session = StripeService::createCheckoutSession($booking, $result['lot'], $event);
            if ($session && !empty($session['id']) && !empty($session['url'])) {
                Booking::setStripeSession((int) $booking['id'], $session['id']);
                header('Location: ' . $session['url']);
                exit;
            }
            // Stripe session creation failed — booking stays pending_payment; the guest
            // can still be confirmed manually by an admin or retry from "find my booking".
            Flash::error(__('booking.stripe_unavailable'));
        }

        redirect('booking/' . $result['booking_code'] . '/confirmation');
    }

    public function confirmation(Request $request, string $code): void
    {
        $booking = Booking::findByCode(strtoupper($code));
        if (!$booking) {
            redirect('');
        }

        View::render('public/booking/confirmation', [
            'title' => __('public.confirmation_title'),
            'booking' => $booking,
            'settings' => Setting::get(),
        ], 'public');
    }

    public function receipt(Request $request, string $code): void
    {
        $booking = Booking::findByCode(strtoupper($code));
        if (!$booking) {
            redirect('');
        }

        View::render('public/booking/receipt', [
            'title' => __('public.receipt_title'),
            'booking' => $booking,
            'settings' => Setting::get(),
        ]);
    }

    public function stripeReturn(Request $request, string $code): void
    {
        $booking = Booking::findByCode(strtoupper($code));
        if (!$booking) {
            redirect('');
        }

        $sessionId = $request->query['session_id'] ?? '';
        if ($sessionId && $booking['status'] === 'pending_payment') {
            $session = StripeService::retrieveSession($sessionId);
            if ($session && ($session['payment_status'] ?? '') === 'paid') {
                BookingService::confirm((int) $booking['id'], 'system', null, __('booking.default_note_confirm') . ' (Stripe)');
                $booking = Booking::findByCode(strtoupper($code));
            }
        }

        View::render('public/booking/stripe_return', [
            'title' => __('public.stripe_success_title'),
            'booking' => $booking,
        ], 'public');
    }

    public function stripeCancelled(Request $request, string $code): void
    {
        $booking = Booking::findByCode(strtoupper($code));
        if (!$booking) {
            redirect('');
        }

        View::render('public/booking/stripe_cancelled', [
            'title' => __('public.stripe_cancelled_title'),
            'booking' => $booking,
        ], 'public');
    }
}
