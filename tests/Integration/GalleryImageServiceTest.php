<?php

namespace Tests\Integration;

use App\Services\GalleryImageService;
use Tests\TestCase;

class GalleryImageServiceTest extends TestCase
{
    private const SUBDIR = 'zz_test_gallery';
    private array $tmpFiles = [];

    public function tearDown(): void
    {
        foreach ($this->tmpFiles as $f) {
            @unlink($f);
        }
        $dir = BASE_PATH . '/uploads/' . self::SUBDIR;
        foreach (array_merge(glob($dir . '/thumbs/*') ?: [], glob($dir . '/*') ?: []) as $f) {
            if (is_file($f)) {
                @unlink($f);
            }
        }
        @rmdir($dir . '/thumbs');
        @rmdir($dir);
    }

    private function fixture(int $w, int $h, string $type = 'png'): array
    {
        $img = imagecreatetruecolor($w, $h);
        imagefill($img, 0, 0, imagecolorallocate($img, 200, 80, 40));
        $path = tempnam(sys_get_temp_dir(), 'gal') ?: (BASE_PATH . '/storage/tmp/gal' . uniqid());
        $this->tmpFiles[] = $path;
        $type === 'jpg' ? imagejpeg($img, $path) : imagepng($img, $path);
        return ['tmp_name' => $path, 'size' => filesize($path), 'error' => UPLOAD_ERR_OK, 'name' => 'x.' . $type];
    }

    public function testALargePhotoIsShrunkToAWebSizeAndAThumbnail(): void
    {
        $error = null;
        $result = GalleryImageService::store($this->fixture(3200, 2000), self::SUBDIR, $error);

        $this->assertNotNull($result, (string) $error);
        [$fw, $fh] = getimagesize(BASE_PATH . '/uploads/' . $result['path']);
        [$tw, $th] = getimagesize(BASE_PATH . '/uploads/' . $result['thumb']);
        $this->assertSame(1600, $fw);
        $this->assertSame(1000, $fh, 'aspect ratio must be preserved');
        $this->assertSame(600, $tw);
        $this->assertSame(375, $th);
        $this->assertTrue(str_contains($result['thumb'], '/thumbs/'));
    }

    public function testAPosterKeepsMoreResolutionAndNeedsNoThumbnail(): void
    {
        $result = GalleryImageService::store($this->fixture(3000, 4500), self::SUBDIR, $error, GalleryImageService::POSTER_SIZE, false);

        $this->assertNotNull($result);
        $this->assertNull($result['thumb']);
        $this->assertSame([1333, 2000], array_slice(getimagesize(BASE_PATH . '/uploads/' . $result['path']), 0, 2));
        $this->assertFalse(is_dir(BASE_PATH . '/uploads/' . self::SUBDIR . '/thumbs'), 'no thumbs folder should be made for posters');
    }

    public function testASmallPhotoIsNeverEnlarged(): void
    {
        $result = GalleryImageService::store($this->fixture(400, 300, 'jpg'), self::SUBDIR);

        $this->assertNotNull($result);
        $this->assertSame([400, 300], array_slice(getimagesize(BASE_PATH . '/uploads/' . $result['path']), 0, 2));
        $this->assertSame([400, 300], array_slice(getimagesize(BASE_PATH . '/uploads/' . $result['thumb']), 0, 2));
    }

    public function testNonImagesAndFailedUploadsAreRejectedWithAMessage(): void
    {
        $textPath = tempnam(sys_get_temp_dir(), 'gal');
        $this->tmpFiles[] = $textPath;
        file_put_contents($textPath, 'not an image');

        $error = null;
        $this->assertNull(GalleryImageService::store(['tmp_name' => $textPath, 'size' => 12, 'error' => UPLOAD_ERR_OK], self::SUBDIR, $error));
        $this->assertNotNull($error);

        $error = null;
        $this->assertNull(GalleryImageService::store(['tmp_name' => '', 'size' => 0, 'error' => UPLOAD_ERR_INI_SIZE], self::SUBDIR, $error));
        $this->assertNotNull($error);
    }

    public function testOversizedFilesAreRejectedBeforeAnyDecoding(): void
    {
        $file = $this->fixture(50, 50);
        $file['size'] = GalleryImageService::MAX_SOURCE_BYTES + 1;

        $error = null;
        $this->assertNull(GalleryImageService::store($file, self::SUBDIR, $error));
        $this->assertNotNull($error);
    }
}
