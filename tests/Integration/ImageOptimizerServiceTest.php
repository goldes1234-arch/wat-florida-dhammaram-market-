<?php

namespace Tests\Integration;

use App\Core\Database;
use App\Services\GalleryImageService;
use App\Services\ImageOptimizerService;
use Tests\TestCase;

/** Slimming old uploads: new web-size files, DB re-pointed, originals archived (not deleted) — only on this test's own fixtures. */
class ImageOptimizerServiceTest extends TestCase
{
    private const DIR = 'zz_opt';
    private const OUT = 'zz_opt_out';
    private int $galleryId = 0;
    private string $oldRel = '';

    public function setUp(): void
    {
        @mkdir(BASE_PATH . '/uploads/' . self::DIR, 0775, true);
        $this->oldRel = self::DIR . '/old.jpg';
        $img = imagecreatetruecolor(3200, 2000);
        // Noise so the JPEG is big, like a real camera photo.
        for ($i = 0; $i < 4000; $i++) {
            imagesetpixel($img, random_int(0, 3199), random_int(0, 1999), random_int(0, 0xFFFFFF));
        }
        imagejpeg($img, BASE_PATH . '/uploads/' . $this->oldRel, 95);

        $pdo = Database::connection();
        $pdo->prepare('INSERT INTO gallery_photos (image_path, thumb_path, caption, sort_order) VALUES (?, NULL, "[TEST] old", 61000)')
            ->execute([$this->oldRel]);
        $this->galleryId = (int) $pdo->lastInsertId();
    }

    public function tearDown(): void
    {
        $pdo = Database::connection();
        $row = $pdo->query('SELECT image_path, thumb_path FROM gallery_photos WHERE id = ' . (int) $this->galleryId)->fetch();
        $pdo->prepare('DELETE FROM gallery_photos WHERE id = ?')->execute([$this->galleryId]);
        foreach ([$row['image_path'] ?? null, $row['thumb_path'] ?? null, $this->oldRel] as $rel) {
            if ($rel) {
                @unlink(BASE_PATH . '/uploads/' . $rel);
            }
        }
        @unlink(BASE_PATH . '/storage/originals/' . $this->oldRel);
        @rmdir(BASE_PATH . '/storage/originals/' . self::DIR);
        @rmdir(BASE_PATH . '/uploads/' . self::OUT . '/thumbs');
        @rmdir(BASE_PATH . '/uploads/' . self::OUT);
        @rmdir(BASE_PATH . '/uploads/' . self::DIR);
    }

    private function onlyMine(): callable
    {
        return fn (array $item) => $item['kind'] === 'gallery' && $item['id'] === $this->galleryId;
    }

    public function testAnOldGalleryPhotoGetsAWebSizeCopyAThumbnailAndTheOriginalIsArchived(): void
    {
        $beforeBytes = filesize(BASE_PATH . '/uploads/' . $this->oldRel);

        $result = ImageOptimizerService::run(10, 25, $this->onlyMine(), self::OUT);

        $this->assertSame(1, $result['processed'], json_encode($result['failed']));
        $this->assertTrue($result['saved'] > 0);

        $row = Database::connection()->query('SELECT image_path, thumb_path FROM gallery_photos WHERE id = ' . $this->galleryId)->fetch();
        $this->assertTrue($this->oldRel !== $row['image_path'], 'the row must point at the new file');
        $this->assertNotNull($row['thumb_path']);

        [$w, $h] = getimagesize(BASE_PATH . '/uploads/' . $row['image_path']);
        $this->assertSame(1600, $w);
        $this->assertSame(1000, $h);
        $this->assertTrue(filesize(BASE_PATH . '/uploads/' . $row['image_path']) < $beforeBytes);
        $this->assertSame(600, getimagesize(BASE_PATH . '/uploads/' . $row['thumb_path'])[0]);

        $this->assertFalse(is_file(BASE_PATH . '/uploads/' . $this->oldRel), 'the original must leave the public uploads folder');
        $this->assertTrue(is_file(BASE_PATH . '/storage/originals/' . $this->oldRel), 'but it must be kept, not deleted');
    }

    public function testRunningAgainFindsNothingLeftForThatPhoto(): void
    {
        ImageOptimizerService::run(10, 25, $this->onlyMine(), self::OUT);

        $second = ImageOptimizerService::run(10, 25, $this->onlyMine(), self::OUT);

        $this->assertSame(0, $second['processed']);
        $this->assertSame([], $second['failed']);
    }

    public function testAMissingFileIsReportedAndLeavesTheRowUntouched(): void
    {
        unlink(BASE_PATH . '/uploads/' . $this->oldRel);

        $result = ImageOptimizerService::run(10, 25, $this->onlyMine(), self::OUT);

        $this->assertSame(0, $result['processed']);
        $this->assertSame(1, count($result['failed']));
        $this->assertSame($this->oldRel, Database::connection()->query('SELECT image_path FROM gallery_photos WHERE id = ' . $this->galleryId)->fetchColumn());
    }

    public function testKeepFormatKeepsAPngAPngSoTheLogoStillWorksInPdfReceipts(): void
    {
        $src = BASE_PATH . '/uploads/' . self::DIR . '/logo.png';
        $img = imagecreatetruecolor(1800, 1800);
        imagepng($img, $src);

        $error = null;
        $out = GalleryImageService::optimizeFile($src, self::DIR, $error, 600, false, true);

        $this->assertNotNull($out, (string) $error);
        $this->assertTrue(str_ends_with($out['path'], '.png'));
        $this->assertSame([600, 600], array_slice(getimagesize(BASE_PATH . '/uploads/' . $out['path']), 0, 2));
        @unlink($src);
        @unlink(BASE_PATH . '/uploads/' . $out['path']);
    }
}
