<?php

namespace Tests\Integration;

use App\Core\Database;
use App\Models\Event;
use App\Models\GalleryPhoto;
use Tests\TestCase;

/** New photos append, move() swaps neighbours (even when older rows all share sort_order 0), albums group by event. */
class GalleryPhotoOrderingTest extends TestCase
{
    private int $eventId;
    private array $ids = [];

    public function setUp(): void
    {
        $this->eventId = Event::create([
            'slug' => 'test-gallery-' . bin2hex(random_bytes(4)),
            'name_th' => '[TEST] อัลบั้ม', 'name_en' => '[TEST] Album',
            'description_th' => null, 'description_en' => null, 'venue_name' => null,
            'start_date' => date('Y-m-d', strtotime('+30 days')), 'end_date' => date('Y-m-d', strtotime('+30 days')),
            'booking_open_at' => date('Y-m-d H:i:s'), 'booking_close_at' => date('Y-m-d H:i:s', strtotime('+20 days')),
            'is_published' => 0,
        ]);
    }

    public function tearDown(): void
    {
        $pdo = Database::connection();
        $pdo->exec('DELETE FROM gallery_photos WHERE image_path LIKE "zz_order/%"');
        $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$this->eventId]);
    }

    private function add(string $name, ?int $eventId = null): int
    {
        return $this->ids[$name] = GalleryPhoto::create('zz_order/' . $name . '.webp', 'zz_order/thumbs/' . $name . '.webp', $name, $eventId);
    }

    /** Ids of just this test's photos, in display order. */
    private function order(): array
    {
        $mine = array_flip($this->ids);
        $out = [];
        foreach (GalleryPhoto::all() as $row) {
            if (isset($mine[(int) $row['id']])) {
                $out[] = $mine[(int) $row['id']];
            }
        }
        return $out;
    }

    public function testNewPhotosAreAppendedAtTheEnd(): void
    {
        $this->add('a');
        $this->add('b');
        $this->add('c');

        $this->assertSame(['a', 'b', 'c'], $this->order());
    }

    public function testMoveSwapsWithTheNeighbourAndStopsAtTheEdges(): void
    {
        $this->add('a');
        $this->add('b');
        $this->add('c');

        GalleryPhoto::move($this->ids['c'], -1);
        $this->assertSame(['a', 'c', 'b'], $this->order());

        GalleryPhoto::move($this->ids['a'], -1); // already first: no change
        $this->assertSame(['a', 'c', 'b'], $this->order());

        GalleryPhoto::move($this->ids['b'], 1);  // already last: no change
        $this->assertSame(['a', 'c', 'b'], $this->order());
    }

    public function testMoveWorksWhenOlderPhotosAllShareSortOrderZero(): void
    {
        $pdo = Database::connection();
        $this->add('a');
        $this->add('b');
        $pdo->prepare('UPDATE gallery_photos SET sort_order = 0 WHERE id IN (?, ?)')->execute([$this->ids['a'], $this->ids['b']]);

        GalleryPhoto::move($this->ids['b'], -1);

        $this->assertSame(['b', 'a'], $this->order());
    }

    public function testAlbumsListOnlyEventsThatHavePhotosAndFilterByEvent(): void
    {
        $this->add('x', $this->eventId);
        $this->add('y');

        $albumIds = array_map('intval', array_column(GalleryPhoto::albums(), 'id'));
        $this->assertTrue(in_array($this->eventId, $albumIds, true));
        $this->assertSame(1, GalleryPhoto::count($this->eventId));
        $this->assertSame(
            [$this->ids['x']],
            array_map('intval', array_column(GalleryPhoto::page(1, 24, $this->eventId), 'id'))
        );
    }

    public function testUpdateDetailsChangesCaptionAndAlbum(): void
    {
        $id = $this->add('z');

        GalleryPhoto::updateDetails($id, 'new caption', $this->eventId);
        $row = GalleryPhoto::find($id);
        $this->assertSame('new caption', $row['caption']);
        $this->assertSame($this->eventId, (int) $row['event_id']);

        GalleryPhoto::updateDetails($id, '', null);
        $row = GalleryPhoto::find($id);
        $this->assertNull($row['caption']);
        $this->assertNull($row['event_id']);
    }
}
