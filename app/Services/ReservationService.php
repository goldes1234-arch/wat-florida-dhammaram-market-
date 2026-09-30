<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Mailer;
use App\Core\View;
use App\Models\Booking;
use App\Models\BookingStatusLog;
use App\Models\Event;
use App\Models\Lot;
use App\Models\Setting;

/**
 * Holds a lot for a regular/recurring vendor ahead of the normal public booking
 * flow, pending the vendor's own confirmation that they'll actually attend.
 * Confirming turns the reservation into a real booking (status 'booked') so it
 * flows through the exact same check-in/admin/revenue machinery as any other
 * booking from then on. An unconfirmed reservation is released back to
 * 'available' automatically once the event is within
 * settings.reserved_confirm_deadline_days — see releaseExpired(), called from
 * the daily backup cron so no extra hosting cron job is needed.
 */
class ReservationService
{
    public static function reserve(int $lotId, string $name, string $phone, string $email): array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $lot = Lot::lockForUpdate($lotId);

            if (!$lot) {
                $pdo->rollBack();
                return ['success' => false, 'error' => __('reservation.lot_not_found')];
            }
            if ($lot['status'] !== 'available') {
                $pdo->rollBack();
                return ['success' => false, 'error' => __('booking.lot_taken')];
            }

            $token = bin2hex(random_bytes(32));
            Lot::setReservation($lotId, $name, $phone, $email, $token);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        $lot = Lot::find($lotId);
        self::sendVendorInvite($lot);

        return ['success' => true, 'lot' => $lot];
    }

    /** Admin giving up on a reservation before the vendor ever confirmed. */
    public static function cancel(int $lotId): array
    {
        $lot = Lot::find($lotId);
        if (!$lot || $lot['status'] !== 'reserved') {
            return ['success' => false, 'error' => __('reservation.not_reserved')];
        }

        Lot::clearReservation($lotId);
        return ['success' => true];
    }

    public static function confirm(string $token): array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT * FROM lots WHERE reserved_token = :token FOR UPDATE');
            $stmt->execute(['token' => $token]);
            $lot = $stmt->fetch();

            if (!$lot) {
                $pdo->rollBack();
                return ['success' => false, 'error' => __('reservation.expired_or_invalid')];
            }
            if ($lot['status'] !== 'reserved') {
                $pdo->rollBack();
                // Already confirmed (now booked) or released — not a hard error, the
                // confirm page tells the vendor which of the two happened.
                return ['success' => false, 'error' => __('reservation.already_resolved'), 'lot' => $lot];
            }

            $event = Event::find((int) $lot['event_id']);
            if (self::pastDeadline($event)) {
                $pdo->rollBack();
                return ['success' => false, 'error' => __('reservation.expired_or_invalid')];
            }

            $code = BookingCodeGenerator::generate();
            $bookingId = Booking::create([
                'booking_code' => $code,
                'event_id' => $lot['event_id'],
                'lot_id' => $lot['id'],
                'booker_name' => $lot['reserved_vendor_name'],
                'booker_phone' => $lot['reserved_vendor_phone'],
                'booker_email' => $lot['reserved_vendor_email'],
                'payment_method' => 'onsite_cash',
                'status' => 'booked',
                'price_at_booking' => $lot['price'],
                'currency_code' => Setting::get()['currency_code'] ?? 'THB',
            ]);

            Lot::setStatus((int) $lot['id'], 'booked');
            Lot::markReservationConfirmed((int) $lot['id']);
            BookingStatusLog::record($bookingId, null, 'booked', 'guest', null, __('reservation.log_confirmed'));

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        $lot = Lot::find((int) $lot['id']);
        $booking = Booking::find($bookingId);
        $event = Event::find((int) $lot['event_id']);
        NotificationService::sendBookingConfirmation($booking, $lot, $event);
        NotificationService::sendAdminBookingAlert($booking, $lot, $event);

        return ['success' => true, 'lot' => $lot, 'booking' => $booking];
    }

    /**
     * Releases every reserved-but-unconfirmed lot whose event has entered the
     * confirm-deadline window. Returns how many were released, so the caller (the
     * cron endpoint) can log/report it.
     */
    public static function releaseExpired(): int
    {
        $deadlineDays = (int) (Setting::get()['reserved_confirm_deadline_days'] ?? 10);
        $expired = Lot::expiredReservations($deadlineDays);

        foreach ($expired as $lot) {
            Lot::clearReservation((int) $lot['id']);
        }

        if ($expired && LineService::isEnabled()) {
            $lines = array_map(
                fn (array $lot) => $lot['event_name_th'] . ' — ' . $lot['code'] . ' (' . $lot['reserved_vendor_name'] . ')',
                $expired
            );
            LineService::broadcast(__('line.reservations_expired_alert', ['count' => count($expired)]) . "\n" . implode("\n", $lines));
        }

        return count($expired);
    }

    private static function pastDeadline(?array $event): bool
    {
        if (!$event) {
            return true;
        }
        $deadlineDays = (int) (Setting::get()['reserved_confirm_deadline_days'] ?? 10);
        $deadline = strtotime((string) $event['start_date']) - ($deadlineDays * 86400);
        return time() >= $deadline;
    }

    private static function sendVendorInvite(array $lot): void
    {
        if (empty($lot['reserved_vendor_email'])) {
            return;
        }

        $settings = Setting::get();
        $eventName = $lot['event_name_th'] ?: ($lot['event_name_en'] ?? '');

        $html = View::renderToString('emails/vendor_reservation_invite', [
            'lot' => $lot,
            'settings' => $settings,
        ]);

        Mailer::send(
            $lot['reserved_vendor_email'],
            __('email.vendor_reservation_invite_subject', ['event' => $eventName]),
            $html
        );
    }
}
