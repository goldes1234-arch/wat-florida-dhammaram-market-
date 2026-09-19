<?php

namespace App\Controllers\Public;

use App\Core\Flash;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Models\Event;
use App\Models\Setting;
use App\Models\WaitlistEntry;
use App\Support\Validator;

class WaitlistController
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

        $name = $request->trimmed('name');
        $phone = $request->trimmed('phone');
        $email = $request->trimmed('email');

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

        if ($errors) {
            Flash::error(implode(' ', $errors));
            redirect('events/' . $slug);
        }

        $limitPerHour = (int) (Setting::get()['booking_rate_limit_per_hour'] ?? 5);
        if (RateLimiter::tooMany($request->ip(), 'waitlist', $limitPerHour)) {
            Flash::error(__('booking.rate_limited'));
            redirect('events/' . $slug);
        }

        WaitlistEntry::create((int) $event['id'], $name, $phone, $email ?: null);
        Flash::success(__('public.waitlist_success'));
        redirect('events/' . $slug);
    }
}
