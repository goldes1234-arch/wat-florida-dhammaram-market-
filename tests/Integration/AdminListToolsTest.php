<?php

namespace Tests\Integration;

use App\Core\Database;
use App\Models\Booking;
use App\Models\Event;
use App\Models\GalleryPhoto;
use App\Models\Lot;
use App\Models\Vendor;
use App\Services\AttentionService;
use App\Services\BookingService;
use Tests\TestCase;

/** Admin booking search / status counts, the dashboard "needs attention" feed, and gallery drag-order + duplicate detection. */
class AdminListToolsTest extends TestCase
{
    private int $eventId;
    private int $lotId;
    private int $vendorId = 0;
    private string $code = '';

    public function setUp(): void
    {
        $this->eventId = Event::create([
            'slug' => 'test-admin-list-' . bin2hex(random_bytes(3)),
            'name_th' => '[TEST] Admin list', 'name_en' => '[TEST] Admin list',
            'description_th' => null, 'description_en' => null, 'venue_name' => null,
            'start_date' => date('Y-m-d', strtotime('+30 days')), 'end_date' => date('Y-m-d', strtotime('+30 days')),
            'booking_open_at' => date('Y-m-d H:i:s'), 'booking_close_at' => date('Y-m-d H:i:s', strtotime('+20 days')),
            'is_published' => 0,
        ]);
        $this->lotId = Lot::create($this->eventId, null, 'TEST-S1', 50.00);
        $result = BookingService::attemptBooking($this->lotId, $this->eventId, [
            'booker_name' => '[TEST] Somsri Findable', 'booker_phone' => '0891110001', 'booker_email' => 'findme@example.invalid',
            'items_for_sale' => 'ผัดไทย', 'payment_method' => 'onsite_cash', 'currency_code' => 'THB',
        ]);
        $this->code = $result['booking_code'];
    }

    public function tearDown(): void
    {
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM booking_status_logs WHERE booking_id IN (SELECT id FROM bookings WHERE event_id = ?)')->execute([$this->eventId]);
        $pdo->prepare('DELETE FROM bookings WHERE event_id = ?')->execute([$this->eventId]);
        $pdo->prepare('DELETE FROM lots WHERE event_id = ?')->execute([$this->eventId]);
        $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$this->eventId]);
        if ($this->vendorId) {
            $pdo->prepare('DELETE FROM vendors WHERE id = ?')->execute([$this->vendorId]);
        }
        $pdo->exec('DELETE FROM gallery_photos WHERE image_path LIKE "zz_adm/%"');
        foreach (glob(BASE_PATH . '/uploads/zz_adm/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir(BASE_PATH . '/uploads/zz_adm');
    }

    private function find(?string $search): array
    {
        return array_values(array_filter(
            Booking::forAdmin($this->eventId, null, 'desc', null, null, 50, $search),
            fn (array $b) => $b['booking_code'] === $this->code
        ));
    }

    public function testSearchFindsABookingByCodeNamePhoneOrEmail(): void
    {
        foreach ([$this->code, strtolower($this->code), 'Somsri', '0891110001', 'findme@'] as $term) {
            $this->assertSame(1, count($this->find($term)), "searching '$term' should find the booking");
        }
        $this->assertSame(0, count($this->find('no-such-person-xyz')));
    }

    public function testLikeWildcardsInASearchAreTreatedAsPlainText(): void
    {
        $this->assertSame(0, count($this->find('%')), '% must not match everything');
        $this->assertSame(0, count($this->find('_')), '_ must not match any single character');
    }

    public function testStatusCountsFollowTheSearchAndEventFilters(): void
    {
        $counts = Booking::statusCountsForAdmin($this->eventId, null, 'Somsri');
        $this->assertSame(1, $counts['pending_payment']);
        $this->assertSame(0, $counts['booked']);
        $this->assertSame(1, Booking::countForAdmin($this->eventId, 'pending_payment', null, 'Somsri'));
        $this->assertSame(0, Booking::countForAdmin($this->eventId, 'booked', null, 'Somsri'));
    }

    public function testAttentionFeedListsPendingPaymentsAndLineRequests(): void
    {
        $this->vendorId = Vendor::create('[TEST] Needs approval', '0899990099', null);
        Vendor::requestLineLink($this->vendorId, 'U_test_attention');

        $byKey = [];
        foreach (AttentionService::items() as $item) {
            $byKey[$item['key']] = $item;
        }

        $this->assertTrue(isset($byKey['pending_payments']) && $byKey['pending_payments']['count'] >= 1);
        $this->assertTrue(isset($byKey['line_requests']) && $byKey['line_requests']['count'] >= 1);
        $this->assertTrue(str_contains($byKey['pending_payments']['url'], 'status=pending_payment'));
    }

    public function testDraggingPhotosSetsTheirOrderAndListedOnesComeFirst(): void
    {
        $ids = [];
        foreach (['a', 'b', 'c'] as $n) {
            $ids[$n] = GalleryPhoto::create('zz_adm/' . $n . '.webp', null, $n);
        }

        GalleryPhoto::setOrder([$ids['c'], $ids['a']]);

        $order = array_values(array_filter(
            array_map(fn (array $r) => (int) $r['id'], GalleryPhoto::all()),
            fn (int $id) => in_array($id, $ids, true)
        ));
        $this->assertSame([$ids['c'], $ids['a'], $ids['b']], $order);
    }

    public function testIdenticalImageFilesAreFlaggedAsDuplicatesOfTheEarlierOne(): void
    {
        @mkdir(BASE_PATH . '/uploads/zz_adm', 0775, true);
        $a = imagecreatetruecolor(40, 30);
        imagefill($a, 0, 0, imagecolorallocate($a, 10, 120, 200));
        imagepng($a, BASE_PATH . '/uploads/zz_adm/one.png');
        copy(BASE_PATH . '/uploads/zz_adm/one.png', BASE_PATH . '/uploads/zz_adm/two.png');
        $b = imagecreatetruecolor(40, 30);
        imagefill($b, 0, 0, imagecolorallocate($b, 200, 20, 20));
        imagepng($b, BASE_PATH . '/uploads/zz_adm/other.png');

        $idOne = GalleryPhoto::create('zz_adm/one.png', null, '1');
        $idTwo = GalleryPhoto::create('zz_adm/two.png', null, '2');
        $idOther = GalleryPhoto::create('zz_adm/other.png', null, '3');

        $dups = GalleryPhoto::duplicatesAmong([GalleryPhoto::find($idOne), GalleryPhoto::find($idTwo), GalleryPhoto::find($idOther)]);

        $this->assertSame([$idTwo => $idOne], $dups);
    }
}
