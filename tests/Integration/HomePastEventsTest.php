<?php

namespace Tests\Integration;

use App\Controllers\Public\HomeController;
use App\Core\Database;
use App\Core\Request;
use App\Models\Event;
use Tests\TestCase;

/** Ended events must not be offered as bookable cards on the home page; they move to the collapsed "past events" list. */
class HomePastEventsTest extends TestCase
{
    private array $eventIds = [];

    public function setUp(): void
    {
        $this->eventIds[] = $this->makeEvent('zz-home-past', '[TEST] Ended Market', '-20 days', '-18 days');
        $this->eventIds[] = $this->makeEvent('zz-home-next', '[TEST] Coming Market', '+40 days', '+41 days');
    }

    public function tearDown(): void
    {
        $pdo = Database::connection();
        foreach ($this->eventIds as $id) {
            $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);
        }
    }

    private function makeEvent(string $slug, string $name, string $start, string $end): int
    {
        return Event::create([
            'slug' => $slug . '-' . bin2hex(random_bytes(3)),
            'name_th' => $name, 'name_en' => $name,
            'description_th' => null, 'description_en' => null, 'venue_name' => null,
            'start_date' => date('Y-m-d', strtotime($start)), 'end_date' => date('Y-m-d', strtotime($end)),
            'booking_open_at' => date('Y-m-d H:i:s', strtotime('-60 days')),
            'booking_close_at' => date('Y-m-d H:i:s', strtotime($end)),
            'is_published' => 1,
        ]);
    }

    private function homeHtml(): string
    {
        ob_start();
        (new HomeController())->index(new Request('GET', '/', [], [], []));
        return (string) ob_get_clean();
    }

    public function testAnEndedEventIsOnlyInThePastListAndAnUpcomingOneIsACard(): void
    {
        $html = $this->homeHtml();

        $pastPos = strpos($html, 'class="past-events"');
        $this->assertTrue($pastPos !== false, 'the past-events list must be rendered');
        $pastBlock = substr($html, $pastPos, 1500);
        $this->assertTrue(str_contains($pastBlock, '[TEST] Ended Market'));

        $beforePast = substr($html, 0, $pastPos);
        $this->assertFalse(str_contains($beforePast, '[TEST] Ended Market'), 'an ended event must not appear as a card');
        $this->assertTrue(str_contains($html, '[TEST] Coming Market'));
    }
}
