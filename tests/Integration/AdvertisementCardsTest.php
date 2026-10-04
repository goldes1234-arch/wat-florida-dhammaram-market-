<?php

namespace Tests\Integration;

use App\Core\Database;
use App\Models\Advertisement;
use App\Support\Validator;
use Tests\TestCase;

/** Card extras: ribbon, one-tap buttons, "selling at event X, lot A3" from confirmed bookings, and safe links. */
class AdvertisementCardsTest extends TestCase
{
    private array $adIds = [];
    private int $vendorId = 0;
    private int $eventId = 0;
    private int $pastEventId = 0;
    private array $lotIds = [];

    public function setUp(): void
    {
        $pdo = Database::connection();
        $pdo->prepare('INSERT INTO vendors (name, phone) VALUES ("[TEST] ad vendor", "+1 555 010 7777")')->execute();
        $this->vendorId = (int) $pdo->lastInsertId();

        $this->eventId = $this->makeEvent('zz-ad-future', date('Y-m-d', strtotime('+10 days')));
        $this->pastEventId = $this->makeEvent('zz-ad-past', date('Y-m-d', strtotime('-10 days')));
    }

    public function tearDown(): void
    {
        $pdo = Database::connection();
        foreach ($this->adIds as $id) {
            $pdo->prepare('DELETE FROM advertisements WHERE id = ?')->execute([$id]);
        }
        $pdo->exec('DELETE FROM advertisements WHERE business_name LIKE "[TEST]%"');
        $pdo->exec('DELETE FROM bookings WHERE booker_name = "[TEST] ad"');
        foreach ([$this->eventId, $this->pastEventId] as $e) {
            $pdo->exec('DELETE FROM lots WHERE event_id = ' . (int) $e);
            $pdo->exec('DELETE FROM events WHERE id = ' . (int) $e);
        }
        $pdo->exec('DELETE FROM vendors WHERE id = ' . (int) $this->vendorId);
    }

    private function makeEvent(string $slug, string $date): int
    {
        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO events (slug, name_th, name_en, start_date, end_date, booking_open_at, booking_close_at, is_published)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)'
        )->execute([$slug, "[TEST] งาน $slug", "[TEST] Event $slug", $date, $date, "$date 00:00:00", "$date 23:00:00"]);
        return (int) $pdo->lastInsertId();
    }

    private function book(int $eventId, string $code, string $status = 'booked'): void
    {
        $pdo = Database::connection();
        $pdo->prepare('INSERT INTO lots (event_id, code, price, status) VALUES (?, ?, 10, "booked")')->execute([$eventId, $code]);
        $lotId = (int) $pdo->lastInsertId();
        $pdo->prepare(
            'INSERT INTO bookings (booking_code, event_id, lot_id, booker_name, booker_phone, vendor_id, payment_method, status, price_at_booking, currency_code)
             VALUES (?, ?, ?, "[TEST] ad", "1", ?, "onsite_cash", ?, 10, "USD")'
        )->execute([substr('ZZ' . bin2hex(random_bytes(6)), 0, 14), $eventId, $lotId, $this->vendorId, $status]);
    }

    private function makeAd(array $extra = []): array
    {
        $id = Advertisement::create('[TEST] card', 'zz_ads/c.webp', null, null, 'approved', null, null, 'zz_ads/thumbs/c.webp', $extra + ['vendor_id' => $this->vendorId]);
        $this->adIds[] = $id;
        return Advertisement::withImages([Advertisement::find($id)])[0];
    }

    private function html(array $ads, bool $onEventPage = false): string
    {
        $advertisements = $ads;
        ob_start();
        include BASE_PATH . '/resources/views/partials/ads_section.php';
        return (string) ob_get_clean();
    }

    public function testAVendorLinkedShopShowsUpcomingConfirmedLotsOnly(): void
    {
        $this->book($this->eventId, 'A3');
        $this->book($this->eventId, 'A4');
        $this->book($this->eventId, 'B1', 'cancelled');   // not confirmed
        $this->book($this->pastEventId, 'Z9');             // event already over

        $ad = Advertisement::withSellingAt([$this->makeAd()])[0];

        $this->assertSame(1, count($ad['selling']));
        $this->assertSame(['A3', 'A4'], $ad['selling'][0]['lots']);
    }

    public function testOnAnEventPageOnlyThatEventsLotsCount(): void
    {
        $other = $this->makeEvent('zz-ad-other', date('Y-m-d', strtotime('+20 days')));
        $this->book($this->eventId, 'A3');
        $this->book($other, 'C2');

        $ad = Advertisement::withSellingAt([$this->makeAd()], $this->eventId)[0];

        $this->assertSame(1, count($ad['selling']));
        $this->assertSame(['A3'], $ad['selling'][0]['lots']);

        Database::connection()->exec('DELETE FROM bookings WHERE event_id = ' . $other);
        Database::connection()->exec('DELETE FROM lots WHERE event_id = ' . $other);
        Database::connection()->exec('DELETE FROM events WHERE id = ' . $other);
    }

    public function testAShopWithoutAVendorLinkSellsNowhere(): void
    {
        $this->book($this->eventId, 'A3');
        $id = Advertisement::create('[TEST] nolink', 'zz_ads/c.webp', null);
        $this->adIds[] = $id;

        $ad = Advertisement::withSellingAt(Advertisement::withImages([Advertisement::find($id)]))[0];

        $this->assertSame([], $ad['selling']);
    }

    public function testTheCardShowsRibbonButtonsAndTheSellingLine(): void
    {
        $this->book($this->eventId, 'A3');
        $ad = $this->makeAd(['badge' => 'promo', 'phone' => '+1 (407) 555-0123', 'map_url' => 'https://maps.app.goo.gl/x', 'line_url' => 'https://line.me/ti/p/abc']);

        $home = $this->html(Advertisement::withSellingAt([$ad]));
        $this->assertTrue(str_contains($home, 'ad-badge-promo'));
        $this->assertTrue(str_contains($home, 'href="tel:+14075550123"'), 'phone is reduced to dialable characters');
        $this->assertTrue(str_contains($home, 'ad-action-map') && str_contains($home, 'ad-action-line'));
        $this->assertTrue(str_contains($home, 'ad-card-selling') && str_contains($home, 'A3'));
        $this->assertTrue(str_contains($home, 'zz-ad-future'), 'home names the event');

        $eventPage = $this->html(Advertisement::withSellingAt([$ad], $this->eventId), true);
        $this->assertTrue(str_contains($eventPage, 'ad-card-selling'));
        $this->assertFalse(str_contains($eventPage, 'zz-ad-future'), 'on the event page it says "this event" instead of repeating the name');
    }

    public function testAnUnknownBadgeIsDroppedAndNoButtonsAppearWithoutContactData(): void
    {
        $ad = $this->makeAd(['badge' => '<script>', 'vendor_id' => 0]);

        $this->assertSame(null, Database::connection()->query('SELECT badge FROM advertisements WHERE id = ' . (int) $ad['id'])->fetchColumn() ?: null);
        $html = $this->html([$ad]);
        $this->assertFalse(str_contains($html, 'ad-badge'));
        $this->assertFalse(str_contains($html, 'ad-actions'));
    }

    public function testOnlyHttpLinksAndDialablePhonesPassValidation(): void
    {
        $this->assertTrue(Validator::httpUrl(''));
        $this->assertTrue(Validator::httpUrl('https://line.me/ti/p/abc'));
        $this->assertTrue(Validator::httpUrl('http://example.com/a?b=1'));
        $this->assertFalse(Validator::httpUrl('javascript://%0Aalert(1)'));
        $this->assertFalse(Validator::httpUrl('javascript:alert(1)'));
        $this->assertFalse(Validator::httpUrl('data:text/html;base64,AAAA'));
        $this->assertFalse(Validator::httpUrl('mailto:a@b.com'));
        $this->assertFalse(Validator::httpUrl('example.com'));

        $this->assertTrue(Validator::dialable(''));
        $this->assertTrue(Validator::dialable('+1 (407) 555-0123'));
        $this->assertTrue(Validator::dialable('089-123-4567'));
        $this->assertFalse(Validator::dialable('call me'));
        $this->assertFalse(Validator::dialable('12'));
        $this->assertFalse(Validator::dialable('+1 407 555 0123 <b>'));
    }

    public function testALongDescriptionIsRenderedInFullWithAReadMoreButtonForTheCssClamp(): void
    {
        $long = str_repeat('Fresh chef-inspired dishes made with traditional Thai flavors. ', 12) . 'THE-END';
        $id = Advertisement::create('[TEST] long', 'zz_ads/c.webp', null, $long);
        $this->adIds[] = $id;

        $html = $this->html(Advertisement::withImages([Advertisement::find($id)]));

        $this->assertTrue(str_contains($html, 'THE-END'), 'the whole text must be in the page, the clamp is visual only');
        $this->assertTrue(str_contains($html, 'class="ad-more"'));
        $this->assertTrue(str_contains($html, 'data-less='));
    }
}
