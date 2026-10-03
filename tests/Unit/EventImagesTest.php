<?php

namespace Tests\Unit;

use Tests\TestCase;

/** Which promo image goes where, judged from the real shape of the files (old events have a single, often portrait, banner). */
class EventImagesTest extends TestCase
{
    private const DIR = 'zz_test_event_images';

    public function setUp(): void
    {
        @mkdir(BASE_PATH . '/uploads/' . self::DIR, 0775, true);
        foreach (['wide' => [1600, 900], 'tall' => [941, 1672], 'ultra' => [3000, 500], 'square' => [800, 800]] as $name => [$w, $h]) {
            imagepng(imagecreatetruecolor($w, $h), BASE_PATH . '/uploads/' . self::DIR . '/' . $name . '.png');
        }
    }

    public function tearDown(): void
    {
        foreach (glob(BASE_PATH . '/uploads/' . self::DIR . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir(BASE_PATH . '/uploads/' . self::DIR);
    }

    private function p(string $name): string
    {
        return self::DIR . '/' . $name . '.png';
    }

    public function testALandscapeBannerFillsCardsAndHeadsTheEventPage(): void
    {
        $r = event_images(['banner_image' => $this->p('wide'), 'banner_thumb' => 'thumbs/wide.webp']);

        $this->assertSame($this->p('wide'), $r['hero']);
        $this->assertNull($r['poster']);
        $this->assertSame('thumbs/wide.webp', $r['card']['thumb']);
        $this->assertSame($this->p('wide'), $r['card']['full']);
        $this->assertTrue($r['card']['cover']);
    }

    public function testAnOldPortraitBannerIsTreatedAsAPosterNotAHero(): void
    {
        $r = event_images(['banner_image' => $this->p('tall')]);

        $this->assertNull($r['hero']);
        $this->assertSame($this->p('tall'), $r['poster']);
        $this->assertFalse($r['card']['cover'], 'a portrait image must be shown whole, not cropped');
    }

    public function testASquareImageIsNotUsedAsAWideHero(): void
    {
        $r = event_images(['banner_image' => $this->p('square')]);

        $this->assertNull($r['hero']);
        $this->assertSame($this->p('square'), $r['poster']);
    }

    public function testAnExplicitPosterIsKeptAlongsideALandscapeBanner(): void
    {
        $r = event_images(['banner_image' => $this->p('wide'), 'poster_image' => $this->p('tall')]);

        $this->assertSame($this->p('wide'), $r['hero']);
        $this->assertSame($this->p('tall'), $r['poster']);
        $this->assertTrue($r['card']['cover']);
    }

    public function testAPosterAloneStillGivesCardsAnImageButNeverCropsIt(): void
    {
        $r = event_images(['poster_image' => $this->p('tall')]);

        $this->assertNull($r['hero']);
        $this->assertSame($this->p('tall'), $r['poster']);
        $this->assertSame($this->p('tall'), $r['card']['full']);
        $this->assertFalse($r['card']['cover']);
    }

    public function testAnExtremelyWideBannerIsShownWholeInsteadOfCropped(): void
    {
        $r = event_images(['banner_image' => $this->p('ultra')]);

        $this->assertSame($this->p('ultra'), $r['hero']);
        $this->assertFalse($r['card']['cover']);
    }

    public function testNoImagesMeansNoCardImage(): void
    {
        $r = event_images([]);

        $this->assertNull($r['hero']);
        $this->assertNull($r['poster']);
        $this->assertNull($r['card']);
    }
}
