<?php

namespace Tests\Integration;

use App\Core\Database;
use App\Models\GalleryPhoto;
use Tests\TestCase;

/** The public /gallery page pages through gallery_photos in the same order as the home mosaic. */
class GalleryPhotoPagingTest extends TestCase
{
    /** @var int[] */
    private array $ids = [];

    public function setUp(): void
    {
        $pdo = Database::connection();
        // sort_order far above real photos so these always sort last and never disturb them.
        for ($i = 1; $i <= 5; $i++) {
            $pdo->prepare('INSERT INTO gallery_photos (image_path, caption, sort_order) VALUES (?, ?, ?)')
                ->execute(['zz_test/p' . $i . '.png', '[TEST] photo ' . $i, 60000 + $i]);
            $this->ids[] = (int) $pdo->lastInsertId();
        }
    }

    public function tearDown(): void
    {
        Database::connection()->exec('DELETE FROM gallery_photos WHERE caption LIKE "[TEST] photo %"');
    }

    public function testCountMatchesAllAndPagesAreContiguousSlicesOfAll(): void
    {
        $all = GalleryPhoto::all();
        $this->assertSame(count($all), GalleryPhoto::count());

        $perPage = 2;
        $paged = [];
        for ($page = 1; $page <= (int) ceil(count($all) / $perPage); $page++) {
            foreach (GalleryPhoto::page($page, $perPage) as $row) {
                $paged[] = (int) $row['id'];
            }
        }

        $this->assertSame(array_map(static fn (array $r) => (int) $r['id'], $all), $paged);
    }

    public function testAPageBeyondTheEndIsEmptyAndAnInvalidPageFallsBackToTheFirst(): void
    {
        $this->assertSame([], GalleryPhoto::page(9999, 24));
        $this->assertSame(
            array_column(GalleryPhoto::page(1, 3), 'id'),
            array_column(GalleryPhoto::page(0, 3), 'id')
        );
    }
}
