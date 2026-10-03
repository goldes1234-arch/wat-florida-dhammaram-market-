<?php

namespace Tests\Unit;

use Tests\TestCase;

class UploadAspectRatioTest extends TestCase
{
    private string $dir;
    private string $file;

    public function setUp(): void
    {
        $this->dir = BASE_PATH . '/uploads/zz_test_ratio';
        @mkdir($this->dir, 0775, true);
        $this->file = $this->dir . '/wide.png';
        $img = imagecreatetruecolor(400, 100);
        imagepng($img, $this->file);
    }

    public function tearDown(): void
    {
        @unlink($this->file);
        @rmdir($this->dir);
    }

    public function testReturnsWidthOverHeightOfAnUploadedImage(): void
    {
        $this->assertSame(4.0, upload_aspect_ratio('zz_test_ratio/wide.png'));
    }

    public function testMissingOrEmptyPathsGiveNull(): void
    {
        $this->assertNull(upload_aspect_ratio(null));
        $this->assertNull(upload_aspect_ratio(''));
        $this->assertNull(upload_aspect_ratio('zz_test_ratio/nope.png'));
    }

    public function testPathsEscapingTheUploadsFolderAreRefused(): void
    {
        $this->assertNull(upload_aspect_ratio('../assets/css/public.css'));
        $this->assertNull(upload_aspect_ratio('../../index.php'));
    }
}
