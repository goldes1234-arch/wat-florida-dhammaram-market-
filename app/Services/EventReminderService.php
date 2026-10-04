<?php

namespace App\Services;

use App\Core\Mailer;
use App\Core\View;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Setting;
use App\Models\Vendor;

/**
 * Reminds everyone with a confirmed booking that their event is coming up —
 * email when the booker has one on file, and a LINE push to the linked vendor
 * account when the booking is tied to one (see LineWebhookController for how
 * that link gets made). Driven by the daily /cron/backup hit rather than page
 * traffic (unlike EventStatusService's "open" notice), since an event a few
 * days out may see no admin/public page views at all in that window.
 */
class EventReminderService
{
    /** Sends reminders for every due event and returns how many events were processed. */
    public static function sendDueReminders(): int
    {
        $daysBefore = (int) (Setting::get()['vendor_reminder_days_before'] ?? 3);
        $events = Event::dueForVendorReminder($daysBefore);

        foreach ($events as $event) {
            self::remindBookingsForEvent($event);
            Event::markVendorReminderSent((int) $event['id']);
        }

        return count($events);
    }

    private static function remindBookingsForEvent(array $event): void
    {
        $bookings = Booking::confirmedForEvent((int) $event['id']);
        $eventName = $event['name_th'] ?: ($event['name_en'] ?? '');
        $settings = Setting::get();

        foreach ($bookings as $booking) {
            if (!empty($booking['booker_email'])) {
                $html = View::renderToString('emails/event_reminder', [
                    'booking' => $booking,
                    'event' => $event,
                    'settings' => $settings,
                ]);
                Mailer::send(
                    $booking['booker_email'],
                    __('email.event_reminder_subject', ['event' => $eventName]),
                    $html
                );
            }

            if (!empty($booking['vendor_id'])) {
                $vendor = Vendor::find((int) $booking['vendor_id']);
                if ($vendor && !empty($vendor['line_user_id'])) {
                    LineService::push($vendor['line_user_id'], __('line.event_reminder', [
                        'event' => $eventName,
                        'lot' => $booking['lot_code'],
                        'date' => date('m/d/Y', strtotime((string) $event['start_date'])),
                    ]));
                }
            }
        }
    }
}
