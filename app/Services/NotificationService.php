<?php

namespace App\Services;

use App\Core\Mailer;
use App\Core\View;
use App\Models\InterestSubscriber;
use App\Models\Setting;

class NotificationService
{
    public static function sendBookingConfirmation(array $booking, array $lot, array $event): void
    {
        if (empty($booking['booker_email'])) {
            return;
        }

        $html = View::renderToString('emails/booking_confirmation', [
            'booking' => $booking,
            'lot' => $lot,
            'event' => $event,
            'settings' => Setting::get(),
        ]);

        Mailer::send(
            $booking['booker_email'],
            __('email.booking_confirmation_subject', ['code' => $booking['booking_code']]),
            $html
        );
    }

    /** Lets the temple's own staff know a new booking came in — email + LINE broadcast. */
    public static function sendAdminBookingAlert(array $booking, array $lot, array $event): void
    {
        $settings = Setting::get();
        $eventName = $event['name_th'] ?: ($event['name_en'] ?? '');

        if (!empty($settings['org_email'])) {
            $html = View::renderToString('emails/admin_booking_alert', [
                'booking' => $booking,
                'lot' => $lot,
                'event' => $event,
                'settings' => $settings,
            ]);
            Mailer::send(
                $settings['org_email'],
                __('email.admin_booking_alert_subject', ['code' => $booking['booking_code']]),
                $html
            );
        }

        if (LineService::isEnabled()) {
            LineService::broadcast(__('line.new_booking_alert', [
                'event' => $eventName,
                'lot' => $lot['code'],
                'name' => $booking['booker_name'],
                'phone' => $booking['booker_phone'],
                'price' => money((float) $booking['price_at_booking'], $booking['currency_code']),
                'code' => $booking['booking_code'],
            ]));
        }
    }

    /** Lets the temple's own staff know a shop submitted an ad for review — email + LINE broadcast. */
    public static function sendAdminAdSubmissionAlert(?array $ad): void
    {
        if (!$ad) {
            return;
        }

        $settings = Setting::get();

        if (!empty($settings['org_email'])) {
            $html = View::renderToString('emails/admin_ad_submission_alert', [
                'ad' => $ad,
                'settings' => $settings,
            ]);
            Mailer::send(
                $settings['org_email'],
                __('email.admin_ad_submission_alert_subject', ['name' => $ad['business_name']]),
                $html
            );
        }

        if (LineService::isEnabled()) {
            LineService::broadcast(__('line.new_ad_submission_alert', [
                'name' => $ad['business_name'],
                'contact_name' => $ad['contact_name'] ?? '',
                'contact_phone' => $ad['contact_phone'] ?? '',
            ]));
        }
    }

    /** Lets waitlisted vendors know a lot just freed up on an event they're waiting for. */
    public static function sendWaitlistAlert(array $entry, array $event): void
    {
        $eventName = $event['name_th'] ?: ($event['name_en'] ?? '');
        $settings = Setting::get();

        if (!empty($entry['email'])) {
            $html = View::renderToString('emails/waitlist_alert', [
                'entry' => $entry,
                'event' => $event,
                'settings' => $settings,
            ]);
            Mailer::send($entry['email'], __('email.waitlist_alert_subject', ['event' => $eventName]), $html);
        }
    }

    public static function notifyEventOpen(array $event): void
    {
        $subscribers = InterestSubscriber::notNotifiedForEvent((int) $event['id']);
        if (!$subscribers) {
            return;
        }

        $settings = Setting::get();
        $subject = __('email.event_open_subject', ['event' => $event['name_th']]);
        $html = View::renderToString('emails/event_open', ['event' => $event, 'settings' => $settings]);

        foreach ($subscribers as $subscriber) {
            // Phone-only subscribers can't be reached by email in phase 1 (SMS/LINE are
            // future phases) — still mark them notified so they aren't re-checked forever.
            if (!empty($subscriber['email'])) {
                Mailer::send($subscriber['email'], $subject, $html);
            }
            InterestSubscriber::markNotified((int) $subscriber['id']);
        }
    }
}
