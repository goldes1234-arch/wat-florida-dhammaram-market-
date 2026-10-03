<?php

namespace Tests\Integration;

use App\Core\Database;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Lot;
use App\Models\Vendor;
use App\Services\BookingService;
use App\Services\ReservationService;
use Tests\TestCase;

/** The "what does this stall sell?" field: stored on booking, shown via Lot::forEvent(), editable, copied from regular vendors. */
class ItemsForSaleTest extends TestCase
{
    private int $eventId;
    private int $lotId;

    public function setUp(): void
    {
        $this->eventId = Event::create([
            'slug' => 'test-items-' . bin2hex(random_bytes(4)),
            'name_th' => '[TEST] งานทดสอบสินค้า',
            'name_en' => '[TEST] Items test event',
            'description_th' => null,
            'description_en' => null,
            'venue_name' => null,
            'start_date' => date('Y-m-d', strtotime('+60 days')),
            'end_date' => date('Y-m-d', strtotime('+60 days')),
            'booking_open_at' => date('Y-m-d H:i:s'),
            'booking_close_at' => date('Y-m-d H:i:s', strtotime('+59 days')),
            'is_published' => 0,
        ]);
        $this->lotId = Lot::create($this->eventId, null, 'TEST-I1', 100.00);
    }

    public function tearDown(): void
    {
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM booking_status_logs WHERE booking_id IN (SELECT id FROM bookings WHERE event_id = ?)')
            ->execute([$this->eventId]);
        $pdo->prepare('DELETE FROM bookings WHERE event_id = ?')->execute([$this->eventId]);
        $pdo->prepare('DELETE FROM lots WHERE event_id = ?')->execute([$this->eventId]);
        $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$this->eventId]);
        $pdo->prepare('DELETE FROM vendors WHERE phone = ?')->execute(['0899990088']);
    }

    private function book(?string $items): array
    {
        return BookingService::attemptBooking($this->lotId, $this->eventId, [
            'booker_name' => '[TEST] Seller',
            'booker_phone' => '0899990087',
            'booker_email' => null,
            'items_for_sale' => $items,
            'payment_method' => 'onsite_cash',
            'currency_code' => 'THB',
        ]);
    }

    private function publicLot(): array
    {
        foreach (Lot::forEvent($this->eventId) as $lot) {
            if ((int) $lot['id'] === $this->lotId) {
                return $lot;
            }
        }
        $this->fail('lot not found in Lot::forEvent()');
    }

    public function testBookingStoresWhatTheStallSellsAndTheEventPageQueryReturnsIt(): void
    {
        $result = $this->book('ข้าวแกง ผลไม้');
        $this->assertTrue($result['success']);

        $this->assertSame('ข้าวแกง ผลไม้', Booking::find($result['booking_id'])['items_for_sale']);
        $this->assertSame('ข้าวแกง ผลไม้', $this->publicLot()['items_for_sale']);
    }

    public function testBlankItemsAreStoredAsNull(): void
    {
        $result = $this->book('');

        $this->assertNull(Booking::find($result['booking_id'])['items_for_sale']);
    }

    public function testAdminCanEditAndClearIt(): void
    {
        $id = $this->book('เดิม')['booking_id'];

        Booking::setItemsForSale($id, '  น้ำสมุนไพร  ');
        $this->assertSame('น้ำสมุนไพร', Booking::find($id)['items_for_sale']);

        Booking::setItemsForSale($id, '   ');
        $this->assertNull(Booking::find($id)['items_for_sale']);
    }

    public function testACancelledBookingNoLongerShowsItsItemsOnTheLot(): void
    {
        $id = $this->book('ขนมไทย')['booking_id'];
        BookingService::cancel($id, 'admin', null, 'test');

        $this->assertNull($this->publicLot()['items_for_sale'], 'a freed lot must not advertise the old stall');
    }

    public function testConfirmingARegularVendorsReservationCopiesTheirDefaultItems(): void
    {
        $vendorId = Vendor::create('[TEST] Regular', '0899990088', null);
        Vendor::update($vendorId, '[TEST] Regular', '0899990088', null, null, 'ผัดไทย');

        ReservationService::reserve($this->lotId, '[TEST] Regular', '0899990088', null);
        $token = Lot::find($this->lotId)['reserved_token'];
        $confirm = ReservationService::confirm($token);

        $this->assertTrue($confirm['success']);
        $this->assertSame('ผัดไทย', $confirm['booking']['items_for_sale']);
    }
}
