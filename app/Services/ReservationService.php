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
use App\Models\Vendor;

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
    public static function reserve(int $lotId, string $name, string $phone, ?string $email): array
    {
        // Resolved before the lot lock below — matching by phone reuses the same
        // vendor record across reservations instead of spawning a duplicate every
        // time, which is what lets a vendor's history accumulate on one profile.
        $vendorId = Vendor::findOrCreateByContact($name, $phone, $email);

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
            Lot::setReservation($lotId, $name, $phone, $email, $token, $vendorId);

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

    /** The vendor confirming via the emailed (or admin-shared) link. */
    public static function confirm(string $token): array
    {
        return self::confirmWhere('reserved_token', $token, 'link', null);
    }

    /**
     * Admin recording that the vendor confirmed some other way — typically by phone. Skips the
     * confirm-deadline check on purpose: that deadline exists to auto-release lots nobody has
     * heard back about, and an admin who has just spoken to the vendor is the opposite of that
     * (the lot is still 'reserved' only because the daily release hasn't run yet).
     */
    public static function confirmByAdmin(int $lotId, ?int $adminId): array
    {
        return self::confirmWhere('id', $lotId, 'admin', $adminId);
    }

    /**
     * A LINE-linked vendor replying "ยืนยัน" in the OA chat. Never guesses between several
     * pending reservations — "confirm" with more than one is ambiguous, so that case hands
     * back the individual links instead of confirming anything.
     * @return array{status: 'none'|'confirmed'|'multiple'|'error', lot?: array, lots?: array, error?: string}
     */
    public static function confirmViaLine(array $vendor): array
    {
        $pending = [];
        foreach (Lot::reservedForVendor((int) $vendor['id']) as $lot) {
            if (!self::pastDeadline(Event::find((int) $lot['event_id']))) {
                $pending[] = $lot;
            }
        }

        if (!$pending) {
            return ['status' => 'none'];
        }
        if (count($pending) > 1) {
            return ['status' => 'multiple', 'lots' => $pending];
        }

        $result = self::confirmWhere('id', (int) $pending[0]['id'], 'line', null);

        return $result['success']
            ? ['status' => 'confirmed', 'lot' => $result['lot']]
            : ['status' => 'error', 'error' => $result['error']];
    }

    /** @param 'reserved_token'|'id' $column */
    private static function confirmWhere(string $column, string|int $value, string $channel, ?int $adminId): array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT * FROM lots WHERE ' . ($column === 'id' ? 'id' : 'reserved_token') . ' = :value FOR UPDATE');
            $stmt->execute(['value' => $value]);
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
            if ($channel !== 'admin' && self::pastDeadline($event)) {
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
                'vendor_id' => $lot['reserved_vendor_id'],
                'payment_method' => 'onsite_cash',
                'status' => 'booked',
                'price_at_booking' => $lot['price'],
                'currency_code' => Setting::get()['currency_code'] ?? 'THB',
            ]);

            Lot::setStatus((int) $lot['id'], 'booked');
            Lot::markReservationConfirmed((int) $lot['id']);
            BookingStatusLog::record(
                $bookingId,
                null,
                'booked',
                $channel === 'admin' ? 'admin' : 'guest',
                $adminId,
                match ($channel) {
                    'admin' => __('reservation.log_confirmed_admin'),
                    'line' => __('reservation.log_confirmed_line'),
                    default => __('reservation.log_confirmed'),
                }
            );

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
        // The admin who just clicked confirm doesn't need an alert telling them a booking came in.
        if ($channel !== 'admin') {
            NotificationService::sendAdminBookingAlert($booking, $lot, $event);
        }

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

    /**
     * Invites go out by email when the vendor has one, and by LINE too when their LINE
     * account is linked — either alone is enough, and the admin can always share the link
     * (or record a phone confirmation) from the lot page.
     */
    private static function sendVendorInvite(array $lot): void
    {
        $settings = Setting::get();
        $eventName = $lot['event_name_th'] ?: ($lot['event_name_en'] ?? '');

        if (!empty($lot['reserved_vendor_email'])) {
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

        $vendor = !empty($lot['reserved_vendor_id']) ? Vendor::find((int) $lot['reserved_vendor_id']) : null;
        if ($vendor && !empty($vendor['line_user_id']) && LineService::isEnabled()) {
            LineService::push($vendor['line_user_id'], __('line.vendor_reservation_invite', [
                'event' => $eventName,
                'lot' => $lot['code'],
                'link' => full_url('reserve/' . $lot['reserved_token']),
            ]) . "\n\n" . __('public.cancellation_policy', BookingService::policyDays()));
        }
    }
}
