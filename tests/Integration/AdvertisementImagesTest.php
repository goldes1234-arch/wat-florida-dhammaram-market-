<?php

namespace Tests\Integration;

use App\Core\Database;
use App\Models\Advertisement;
use App\Services\AdvertisementImageService;
use App\Services\ImageOptimizerService;
use Tests\TestCase;

/** One shop, several photos: cover + extras, per-shop cap, cascade delete, public card markup, optimizer for old ads. */
class AdvertisementImagesTest extends TestCase
{
    private const OUT = 'zz_ads_out';
    private array $adIds = [];

    public function tearDown(): void
    {
        $pdo = Database::connection();
        foreach ($this->adIds as $id) {
            $pdo->prepare('DELETE FROM advertisements WHERE id = ?')->execute([$id]);
        }
        $pdo->exec('DELETE FROM advertisements WHERE business_name LIKE "[TEST]%"');
        foreach (glob(BASE_PATH . '/uploads/' . self::OUT . '/thumbs/*') ?: [] as $f) {
            @unlink($f);
        }
        foreach (glob(BASE_PATH . '/uploads/' . self::OUT . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir(BASE_PATH . '/uploads/' . self::OUT . '/thumbs');
        @rmdir(BASE_PATH . '/uploads/' . self::OUT);
        @unlink(BASE_PATH . '/uploads/zz_ads_old.jpg');
        foreach (glob(BASE_PATH . '/storage/originals/zz_ads_old.jpg') ?: [] as $f) {
            @unlink($f);
        }
    }

    private function makeAd(string $name, int $extras, ?string $link = null): int
    {
        $id = Advertisement::create("[TEST] $name", 'zz_ads/cover.webp', $link, null, 'approved', null, null, 'zz_ads/thumbs/cover.webp');
        $this->adIds[] = $id;
        for ($i = 1; $i <= $extras; $i++) {
            Advertisement::addImage($id, "zz_ads/extra$i.webp", "zz_ads/thumbs/extra$i.webp");
        }
        return $id;
    }

    private function find(int $id): array
    {
        foreach (Advertisement::approved() as $ad) {
            if ((int) $ad['id'] === $id) {
                return $ad;
            }
        }
        $this->assertTrue(false, "ad $id should be listed");
        return [];
    }

    private function sectionHtml(array $ads): string
    {
        $advertisements = $ads;
        ob_start();
        include BASE_PATH . '/resources/views/partials/ads_section.php';
        return (string) ob_get_clean();
    }

    public function testACoverPlusExtrasComeBackInOrderCoverFirst(): void
    {
        $ad = $this->find($this->makeAd('order', 3));

        $this->assertSame(4, count($ad['images']));
        $this->assertSame('zz_ads/cover.webp', $ad['images'][0]['path']);
        $this->assertSame(['zz_ads/extra1.webp', 'zz_ads/extra2.webp', 'zz_ads/extra3.webp'], array_column(array_slice($ad['images'], 1), 'path'));
    }

    public function testAnOldAdWithoutAThumbnailFallsBackToItsFullImage(): void
    {
        $id = Advertisement::create('[TEST] old', 'zz_ads/old.jpg', null);
        $this->adIds[] = $id;

        $ad = $this->find($id);

        $this->assertSame(1, count($ad['images']));
        $this->assertSame('zz_ads/old.jpg', $ad['images'][0]['thumb']);
    }

    public function testAShopCanHoldAtMostFivePhotosCoverIncluded(): void
    {
        $id = $this->makeAd('cap', 2);
        $this->assertSame(2, Advertisement::roomForImages($id));

        Advertisement::addImage($id, 'zz_ads/e3.webp', null);
        Advertisement::addImage($id, 'zz_ads/e4.webp', null);

        $this->assertSame(0, Advertisement::roomForImages($id));
    }

    public function testDeletingAShopRemovesItsExtraPhotoRows(): void
    {
        $id = $this->makeAd('cascade', 2);

        Advertisement::delete($id);

        $this->assertSame(0, Advertisement::extraImageCount($id));
    }

    public function testNormalizeSkipsEmptySlotsAndAcceptsASingleFileField(): void
    {
        $multi = [
            'name' => ['a.jpg', '', 'c.jpg'],
            'type' => ['image/jpeg', '', 'image/jpeg'],
            'tmp_name' => ['/tmp/a', '', '/tmp/c'],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE, UPLOAD_ERR_OK],
            'size' => [10, 0, 30],
        ];
        $files = AdvertisementImageService::normalize($multi);
        $this->assertSame(2, count($files));
        $this->assertSame('/tmp/c', $files[1]['tmp_name']);

        $single = ['name' => 'a.jpg', 'type' => 'image/jpeg', 'tmp_name' => '/tmp/a', 'error' => UPLOAD_ERR_OK, 'size' => 10];
        $this->assertSame(1, count(AdvertisementImageService::normalize($single)));
        $this->assertSame([], AdvertisementImageService::normalize(['name' => '', 'type' => '', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0]));
        $this->assertSame([], AdvertisementImageService::normalize(null));
    }

    public function testStoreAllRefusesTooManyFilesAndNoFiles(): void
    {
        $file = ['name' => 'a.jpg', 'type' => 'image/jpeg', 'tmp_name' => '/nonexistent', 'error' => UPLOAD_ERR_OK, 'size' => 10];

        $error = null;
        $this->assertNull(AdvertisementImageService::storeAll([$file, $file, $file], 2, $error));
        $this->assertTrue($error !== null && $error !== '');

        $error = null;
        $this->assertNull(AdvertisementImageService::storeAll([], 5, $error));
        $this->assertTrue($error !== null && $error !== '');
    }

    public function testAMultiPhotoShopOpensItsPhotosInOneLightboxGroupAndKeepsItsLinkAsAButton(): void
    {
        $html = $this->sectionHtml([$this->find($this->makeAd('multi', 2, 'https://example.com/shop'))]);

        $this->assertSame(3, substr_count($html, 'data-lightbox-group="ad-'), 'cover + 2 extras share one group');
        $this->assertSame(3, substr_count($html, 'class="ad-slide'), 'one slide per photo');
        $this->assertTrue(str_contains($html, 'ad-action-web'), 'the shop link stays reachable as a button');
        $this->assertTrue(str_contains($html, 'data-ad-slides="3"'));
    }

    public function testASinglePhotoShopHasNoSlideshowAndTheLinkIsAButtonNotTheWholeCard(): void
    {
        $html = $this->sectionHtml([$this->find($this->makeAd('single', 0, 'https://example.com/shop'))]);

        $this->assertTrue(str_contains($html, 'href="https://example.com/shop"'));
        $this->assertFalse(str_contains($html, 'data-lightbox-group'));
        $this->assertFalse(str_contains($html, 'data-ad-slides'));
        $this->assertFalse(str_contains($html, '<a href="https://example.com/shop" target="_blank" rel="noopener" class="ad-card"'));
    }

    public function testAnOldAdPhotoGetsAThumbnailFromTheOptimizerAndIsThenNotQueuedAgain(): void
    {
        @mkdir(BASE_PATH . '/uploads', 0775, true);
        $img = imagecreatetruecolor(2400, 1800);
        for ($i = 0; $i < 3000; $i++) {
            imagesetpixel($img, random_int(0, 2399), random_int(0, 1799), random_int(0, 0xFFFFFF));
        }
        imagejpeg($img, BASE_PATH . '/uploads/zz_ads_old.jpg', 95);

        $id = Advertisement::create('[TEST] legacy', 'zz_ads_old.jpg', null);
        $this->adIds[] = $id;
        $only = fn (array $item) => $item['kind'] === 'ad' && $item['id'] === $id;

        $first = ImageOptimizerService::run(10, 25, $only, self::OUT);
        $this->assertSame(1, $first['processed'], json_encode($first['failed']));

        $row = Database::connection()->query('SELECT image_path, thumb_path FROM advertisements WHERE id = ' . $id)->fetch();
        $this->assertNotNull($row['thumb_path']);
        $this->assertSame(1600, getimagesize(BASE_PATH . '/uploads/' . $row['image_path'])[0]);

        $this->assertSame(0, ImageOptimizerService::run(10, 25, $only, self::OUT)['processed']);
    }
}
