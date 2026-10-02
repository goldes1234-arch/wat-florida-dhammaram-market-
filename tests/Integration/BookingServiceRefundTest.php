<?php

namespace Tests\Integration;

use App\Core\Database;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Lot;
use App\Services\BookingService;
use Tests\TestCase;

/**
 * Covers BookingService::refund()'s precondition guards — the logic that's
 * actually ours (not Stripe's). A true success-path test would need a live
 * Stripe test-mode secret key, which isn't available in this environment;
 * these guards are exactly what stand between a refund request and ever
 * reaching Stripe, so they're the highest-value thing to lock in here.
 */
class BookingServiceRefundTest extends TestCase
{
    private int $eventId;
    private int $lotId;

    public function setUp(): void
    {
        $this->eventId = Event::create([
            'slug' => 'test-refund-' . bin2hex(random_bytes(4)),
            'name_th' => '[TEST] งานทดสอบคืนเงิน',
            'name_en' => '[TEST] Refund test event',
            'description_th' => null,
            'description_en' => null,
            'venue_name' => null,
            'start_date' => date('Y-m-d', strtotime('+60 days')),
            'end_date' => date('Y-m-d', strtotime('+60 days')),
            'booking_open_at' => date('Y-m-d H:i:s'),
            'booking_close_at' => date('Y-m-d H:i:s', strtotime('+59 days')),
            'is_published' => 0,
        ]);
        $this->lotId = Lot::create($this->eventId, null, 'TEST-R1', 100.00);
    }

    public function tearDown(): void
    {
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM booking_status_logs WHERE booking_id IN (SELECT id FROM bookings WHERE event_id = ?)')
            ->execute([$this->eventId]);
        $pdo->prepare('DELETE FROM bookings WHERE event_id = ?')->execute([$this->eventId]);
        $pdo->prepare('DELETE FROM lots WHERE event_id = ?')->execute([$this->eventId]);
        $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$this->eventId]);
    }

    public function testRefundRejectsABookingThatIsNotCancelled(): void
    {
        $bookingId = $this->createBooking('booked', 'stripe', 'pi_test_123');

        $result = BookingService::refund($bookingId, null);

        $this->assertFalse($result['success']);
    }

    public function testRefundRejectsWhenThereIsNoStripePaymentIntent(): void
    {
        $bookingId = $this->createBooking('cancelled', 'onsite_cash', null);

        $result = BookingService::refund($bookingId, null);

        $this->assertFalse($result['success']);
    }

    public function testRefundRejectsAnAlreadyRefundedBooking(): void
    {
        $bookingId = $this->createBooking('cancelled', 'stripe', 'pi_test_456');
        Booking::updateStatus($bookingId, 'cancelled', ['refunded_at' => date('Y-m-d H:i:s')]);

        $result = BookingService::refund($bookingId, null);

        $this->assertFalse($result['success']);
    }

    public function testRefundWindowIsOpenWithTenOrMoreDaysLeftAndClosedBelowThat(): void
    {
        $bookingId = $this->createBooking('cancelled', 'stripe', 'pi_test_win');

        $this->setEventStartInDays(60);
        $this->assertTrue(BookingService::isRefundWindowOpen(Booking::find($bookingId), 10));

        // Exactly 10 days out still counts (the deadline runs to the end of that day).
        $this->setEventStartInDays(10);
        $this->assertTrue(BookingService::isRefundWindowOpen(Booking::find($bookingId), 10));

        $this->setEventStartInDays(9);
        $this->assertFalse(BookingService::isRefundWindowOpen(Booking::find($bookingId), 10));
    }

    public function testRefundIsBlockedPastTheWindowBeforeAnythingReachesStripe(): void
    {
        $bookingId = $this->createBooking('cancelled', 'stripe', 'pi_test_late');
        $this->setEventStartInDays(5);

        $result = BookingService::refund($bookingId, null);

        $this->assertFalse($result['success']);
        $this->assertNull(Booking::find($bookingId)['refunded_at']);
    }

    public function testAnOverrideAlwaysNeedsAReason(): void
    {
        $bookingId = $this->createBooking('cancelled', 'stripe', 'pi_test_override');
        $this->setEventStartInDays(5);

        foreach (['', '   '] as $blankReason) {
            $result = BookingService::refund($bookingId, null, $blankReason);
            $this->assertFalse($result['success']);
        }
        $this->assertNull(Booking::find($bookingId)['refunded_at']);
    }

    public function testRefundWindowIsMeasuredFromWhenTheBookingWasCancelledNotFromNow(): void
    {
        $bookingId = $this->createBooking('cancelled', 'stripe', 'pi_test_when');
        $this->setEventStartInDays(8);

        // Cancelled 5 days ago = 13 days before the event: in time, even though only 8 days
        // remain today — a slow admin must not cost a guest who cancelled on time.
        Booking::updateStatus($bookingId, 'cancelled', ['cancelled_at' => date('Y-m-d H:i:s', strtotime('-5 days'))]);
        $this->assertTrue(BookingService::isRefundWindowOpen(Booking::find($bookingId), 10));

        // Cancelled just now = 8 days before the event: too late.
        Booking::updateStatus($bookingId, 'cancelled', ['cancelled_at' => date('Y-m-d H:i:s')]);
        $this->assertFalse(BookingService::isRefundWindowOpen(Booking::find($bookingId), 10));
    }

    private function setEventStartInDays(int $days): void
    {
        $date = date('Y-m-d', strtotime("+{$days} days"));
        Database::connection()
            ->prepare('UPDATE events SET start_date = ?, end_date = ? WHERE id = ?')
            ->execute([$date, $date, $this->eventId]);
    }

    private function createBooking(string $status, string $paymentMethod, ?string $paymentIntentId): int
    {
        $id = Booking::create([
            'booking_code' => 'TMRF' . strtoupper(bin2hex(random_bytes(3))),
            'event_id' => $this->eventId,
            'lot_id' => $this->lotId,
            'booker_name' => 'ผู้ทดสอบ',
            'booker_phone' => '0899990099',
            'booker_email' => null,
            'payment_method' => $paymentMethod,
            'status' => $status,
            'price_at_booking' => 100.00,
            'currency_code' => 'THB',
        ]);
        if ($paymentIntentId) {
            Booking::updateStatus($id, $status, ['stripe_payment_intent_id' => $paymentIntentId]);
        }
        return $id;
    }
}
