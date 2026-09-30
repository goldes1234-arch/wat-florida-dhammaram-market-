<?php

namespace Tests\Integration;

use App\Core\Database;
use App\Models\Event;
use App\Models\Lot;
use App\Services\ReservationService;
use Tests\TestCase;

/**
 * Exercises the vendor-reservation flow (reserve -> confirm/cancel, the
 * vendor-dedup-by-phone lookup, and the row-locked "can't double-reserve"
 * guard) against a real, disposable event+lot created in setUp() and torn
 * down after every test — this is the same manual sequence used to QA the
 * feature by hand throughout development, now automated.
 */
class ReservationServiceTest extends TestCase
{
    /** Shared prefix for every test phone number in this file, so tearDown() can sweep up
     *  any vendor row created along the way — including from a reserve() call that's
     *  expected to fail, since Vendor::findOrCreateByContact() runs before the lot-status
     *  check and so still creates the vendor even when the reservation itself is rejected. */
    private const TEST_PHONE_PREFIX = '0899990%';

    private int $eventId;
    private int $lotId;

    public function setUp(): void
    {
        $this->eventId = Event::create([
            'slug' => 'test-reservation-' . bin2hex(random_bytes(4)),
            'name_th' => '[TEST] งานทดสอบระบบจอง',
            'name_en' => '[TEST] Reservation test event',
            'description_th' => null,
            'description_en' => null,
            'venue_name' => null,
            'start_date' => date('Y-m-d', strtotime('+60 days')),
            'end_date' => date('Y-m-d', strtotime('+60 days')),
            'booking_open_at' => date('Y-m-d H:i:s'),
            'booking_close_at' => date('Y-m-d H:i:s', strtotime('+59 days')),
            'is_published' => 0,
        ]);

        $this->lotId = Lot::create($this->eventId, null, 'TEST-A1', 100.00);
    }

    public function tearDown(): void
    {
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM booking_status_logs WHERE booking_id IN (SELECT id FROM bookings WHERE event_id = ?)')
            ->execute([$this->eventId]);
        $pdo->prepare('DELETE FROM bookings WHERE event_id = ?')->execute([$this->eventId]);
        $pdo->prepare('DELETE FROM lots WHERE event_id = ?')->execute([$this->eventId]);
        $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$this->eventId]);
        $pdo->prepare('DELETE FROM vendors WHERE phone LIKE ?')->execute([self::TEST_PHONE_PREFIX]);
    }

    public function testReserveThenConfirmCreatesABookingLinkedToTheVendor(): void
    {
        $result = ReservationService::reserve($this->lotId, 'ผู้ทดสอบ', '0899990001', null);
        $this->assertTrue($result['success']);

        $lot = Lot::find($this->lotId);
        $this->assertSame('reserved', $lot['status']);
        $this->assertNotNull($lot['reserved_vendor_id']);

        $confirm = ReservationService::confirm($lot['reserved_token']);
        $this->assertTrue($confirm['success']);
        $this->assertSame('booked', $confirm['lot']['status']);
        $this->assertSame((int) $lot['reserved_vendor_id'], (int) $confirm['booking']['vendor_id']);
    }

    public function testReservingTwiceWithTheSamePhoneReusesTheSameVendor(): void
    {
        $first = ReservationService::reserve($this->lotId, 'ผู้ทดสอบ หนึ่ง', '0899990002', null);
        $this->assertTrue($first['success']);
        $vendorId1 = (int) Lot::find($this->lotId)['reserved_vendor_id'];

        ReservationService::cancel($this->lotId);

        $secondLotId = Lot::create($this->eventId, null, 'TEST-A2', 100.00);
        $second = ReservationService::reserve($secondLotId, 'ผู้ทดสอบ หนึ่ง', '0899990002', null);
        $this->assertTrue($second['success']);
        $vendorId2 = (int) Lot::find($secondLotId)['reserved_vendor_id'];

        $this->assertSame($vendorId1, $vendorId2, 'the same phone number should resolve to the same vendor record, not a duplicate');
    }

    public function testCannotReserveALotThatIsAlreadyReserved(): void
    {
        $first = ReservationService::reserve($this->lotId, 'ผู้ทดสอบ', '0899990003', null);
        $this->assertTrue($first['success']);

        $second = ReservationService::reserve($this->lotId, 'ผู้ทดสอบ สอง', '0899990004', null);
        $this->assertFalse($second['success'], 'a lot that is already reserved must not be reservable again');
    }

    public function testCancelReturnsTheLotToAvailableAndClearsTheVendorLink(): void
    {
        $result = ReservationService::reserve($this->lotId, 'ผู้ทดสอบ', '0899990005', null);
        $this->assertTrue($result['success']);

        $cancel = ReservationService::cancel($this->lotId);
        $this->assertTrue($cancel['success']);

        $lot = Lot::find($this->lotId);
        $this->assertSame('available', $lot['status']);
        $this->assertNull($lot['reserved_vendor_id']);
    }
}
