<?php

namespace App\Controllers\Public;

use App\Core\Flash;
use App\Core\Mailer;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\View;
use App\Models\ContactMessage;
use App\Models\Setting;
use App\Support\Validator;

class ContactController
{
    public function form(Request $request): void
    {
        View::render('public/contact/form', [
            'title' => __('contact.title'),
            'settings' => Setting::get(),
        ], 'public');
    }

    public function store(Request $request): void
    {
        // Honeypot: real visitors never see/fill this field.
        if ($request->trimmed('website') !== '') {
            redirect('contact');
        }

        $settings = Setting::get();
        if (RateLimiter::tooMany($request->ip(), 'contact', (int) $settings['booking_rate_limit_per_hour'])) {
            Flash::error(__('booking.rate_limited'));
            redirect('contact');
        }

        $name = $request->trimmed('name');
        $email = $request->trimmed('email');
        $phone = $request->trimmed('phone');
        $message = $request->trimmed('message');

        $errors = [];
        if (!Validator::required($name)) {
            $errors[] = __('validation.required', ['field' => __('contact.form_name')]);
        }
        if (!Validator::required($message)) {
            $errors[] = __('validation.required', ['field' => __('contact.form_message')]);
        }
        if ($email === '' && $phone === '') {
            $errors[] = __('contact.missing_contact');
        }
        if ($email !== '' && !Validator::email($email)) {
            $errors[] = __('validation.invalid_email');
        }

        if ($errors) {
            Flash::error(implode(' ', $errors));
            redirect('contact');
        }

        ContactMessage::create($name, $email ?: null, $phone ?: null, $message);

        if (!empty($settings['org_email'])) {
            $html = View::renderToString('emails/contact_message', [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'message' => $message,
                'settings' => $settings,
            ]);
            Mailer::send($settings['org_email'], __('email.contact_message_subject', ['name' => $name]), $html);
        }

        Flash::success(__('contact.success'));
        redirect('contact');
    }
}
