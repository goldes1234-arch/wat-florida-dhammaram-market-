<?php

namespace Tests\Integration;

use App\Controllers\Public\HomeController;
use App\Core\Database;
use App\Core\Request;
use Tests\TestCase;

/** The rotating big tile (8+ photos) must point every slide at a real URL — a doubled /uploads/ once produced broken images. */
class HomeGalleryMosaicTest extends TestCase
{
    public function setUp(): void
    {
        $pdo = Database::connection();
        // Photos with thumbnails, like everything uploaded since resizing was added.
        for ($i = 1; $i <= 9; $i++) {
            $pdo->prepare('INSERT INTO gallery_photos (image_path, thumb_path, caption, sort_order) VALUES (?, ?, ?, ?)')
                ->execute(["zz_mosaic/p$i.webp", "zz_mosaic/thumbs/p$i.webp", "[TEST] mosaic $i", 62000 + $i]);
        }
    }

    public function tearDown(): void
    {
        Database::connection()->exec('DELETE FROM gallery_photos WHERE image_path LIKE "zz_mosaic/%"');
    }

    private function homeHtml(): string
    {
        ob_start();
        (new HomeController())->index(new Request('GET', '/', [], [], []));
        return (string) ob_get_clean();
    }

    public function testNoImageUrlOnTheHomePageHasADoubledUploadsSegment(): void
    {
        $html = $this->homeHtml();

        $this->assertFalse(str_contains($html, '/uploads/uploads/'), 'every upload URL must be built exactly once');
    }

    public function testEveryRotatingSlideHasItsOwnWellFormedImageUrl(): void
    {
        $html = $this->homeHtml();

        $this->assertSame(1, preg_match('#id="mosaicRotator">(.*?)</div>#s', $html, $m), 'the rotating tile must be rendered with 9 photos');
        $this->assertSame(3, preg_match_all('#<img src="([^"]+)"#', $m[1], $srcs), 'three slides expected');
        foreach ($srcs[1] as $src) {
            $this->assertTrue((bool) preg_match('#/uploads/[^/]+/[^"]+\.(webp|png|jpe?g)$#i', $src), "malformed slide URL: $src");
            $this->assertFalse(str_contains($src, '/thumbs/'), "slides use the full-size photo, not a thumbnail: $src");
        }
    }
}
