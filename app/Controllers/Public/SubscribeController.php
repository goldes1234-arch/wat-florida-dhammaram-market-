<?php

namespace App\Controllers\Public;

use App\Core\Flash;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Models\Event;
use App\Models\InterestSubscriber;
use App\Models\Setting;
use App\Support\Validator;

class SubscribeController
{
    public function store(Request $request, string $slug): void
    {
        $event = Event::findBySlug($slug);
        if (!$event) {
            redirect('');
        }

        // Honeypot: real visitors never see/fill this field.
        if ($request->trimmed('website') !== '') {
            redirect('events/' . $slug);
        }

        $email = $request->trimmed('email');
        $phone = $request->trimmed('phone');

        if ($email === '' && $phone === '') {
            Flash::error(__('public.notify_missing_contact'));
            redirect('events/' . $slug);
        }
        if ($email !== '' && !Validator::email($email)) {
            Flash::error(__('validation.invalid_email'));
            redirect('events/' . $slug);
        }

        $limitPerHour = (int) (Setting::get()['booking_rate_limit_per_hour'] ?? 5);
        if (RateLimiter::tooMany($request->ip(), 'interest', $limitPerHour)) {
            Flash::error(__('booking.rate_limited'));
            redirect('events/' . $slug);
        }

        InterestSubscriber::create((int) $event['id'], $email ?: null, $phone ?: null);
        Flash::success(__('public.notify_success'));
        redirect('events/' . $slug);
    }
}
