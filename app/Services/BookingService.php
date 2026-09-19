<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Booking;
use App\Models\BookingStatusLog;
use App\Models\Event;
use App\Models\Lot;
use App\Models\Setting;
use App\Models\WaitlistEntry;

/**
 * Owns every state transition of a booking/lot pair. Every method runs as a
 * single DB transaction with SELECT ... FOR UPDATE row locking, which is what
 * makes "first come, first served" safe against two guests racing for the
 * same lot (see spec §5.3.3).
 */
class BookingService
{
    public static function attemptBooking(int $lotId, int $eventId, array $input): array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $lot = Lot::lockForUpdate($lotId);

            if (!$lot || (int) $lot['event_id'] !== $eventId) {
                $pdo->rollBack();
                return ['success' => false, 'error' => __('booking.lot_not_found')];
            }

            if ($lot['status'] !== 'available') {
                $pdo->rollBack();
                return ['success' => false, 'error' => __('booking.lot_taken')];
            }

            $code = BookingCodeGenerator::generate();

            $bookingId = Booking::create([
                'booking_code' => $code,
                'event_id' => $eventId,
                'lot_id' => $lotId,
                'booker_name' => $input['booker_name'],
                'booker_phone' => $input['booker_phone'],
                'booker_email' => $input['booker_email'] ?? null,
                'payment_method' => $input['payment_method'],
                'status' => 'pending_payment',
                'price_at_booking' => $lot['price'],
                'currency_code' => $input['currency_code'],
            ]);

            Lot::setStatus($lotId, 'pending_payment');

            BookingStatusLog::record($bookingId, null, 'pending_payment', 'guest', null, __('booking.log_created'));

            $pdo->commit();

            return [
                'success' => true,
                'booking_id' => $bookingId,
                'booking_code' => $code,
                'lot' => $lot,
            ];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function confirm(int $bookingId, string $actorType, ?int $adminId = null, ?string $note = null): array
    {
        return self::transition($bookingId, ['pending_payment'], 'booked', $actorType, $adminId, $note, true);
    }

    public static function reject(int $bookingId, ?int $adminId, ?string $note = null): array
    {
        return self::transition($bookingId, ['pending_payment'], 'rejected', 'admin', $adminId, $note, false, 'admin');
    }

    public static function cancel(int $bookingId, string $actorType, ?int $adminId = null, ?string $note = null): array
    {
        $cancelledBy = $actorType === 'guest' ? 'guest' : 'admin';
        return self::transition($bookingId, ['pending_payment', 'booked'], 'cancelled', $actorType, $adminId, $note, false, $cancelledBy);
    }

    private static function transition(
        int $bookingId,
        array $allowedFrom,
        string $toStatus,
        string $actorType,
        ?int $adminId,
        ?string $note,
        bool $lotBecomesBooked,
        ?string $cancelledBy = null
    ): array {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $bookingId]);
            $booking = $stmt->fetch();

            if (!$booking) {
                $pdo->rollBack();
                return ['success' => false, 'error' => __('booking.not_found')];
            }

            if (!in_array($booking['status'], $allowedFrom, true)) {
                $pdo->rollBack();
                return ['success' => false, 'error' => __('booking.invalid_transition')];
            }

            $extra = [];
            if ($toStatus === 'booked') {
                $extra['confirmed_at'] = date('Y-m-d H:i:s');
            }
            if ($toStatus === 'cancelled') {
                $extra['cancelled_at'] = date('Y-m-d H:i:s');
                $extra['cancelled_by'] = $cancelledBy;
            }
            if ($note) {
                $extra['admin_note'] = $note;
            }

            Booking::updateStatus($bookingId, $toStatus, $extra);

            Lot::setStatus((int) $booking['lot_id'], $lotBecomesBooked ? 'booked' : 'available');

            BookingStatusLog::record($bookingId, $booking['status'], $toStatus, $actorType, $adminId, $note);

            $pdo->commit();

            $booking['status'] = $toStatus;

            // The lot just freed back up — let anyone on this event's waitlist know,
            // outside the transaction since it only sends notifications (nothing to roll back).
            if (!$lotBecomesBooked) {
                self::notifyWaitlist((int) $booking['event_id']);
            }

            return ['success' => true, 'booking' => $booking];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private static function notifyWaitlist(int $eventId): void
    {
        $entries = WaitlistEntry::notNotifiedForEvent($eventId);
        if (!$entries) {
            return;
        }

        $event = Event::find($eventId);
        if (!$event) {
            return;
        }

        foreach ($entries as $entry) {
            NotificationService::sendWaitlistAlert($entry, $event);
            WaitlistEntry::markNotified((int) $entry['id']);
        }
    }

    /** Whether a guest is still within the self-cancel cutoff window for their booking's event. */
    public static function canGuestCancel(array $booking): bool
    {
        if (!in_array($booking['status'], ['pending_payment', 'booked'], true)) {
            return false;
        }

        $cutoffDays = (int) (Setting::get()['cancellation_cutoff_days'] ?? 0);
        $deadline = strtotime((string) $booking['event_start_date']) - ($cutoffDays * 86400) + 86399;

        return time() <= $deadline;
    }
}
