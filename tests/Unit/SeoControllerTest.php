<?php

namespace Tests\Unit;

use App\Controllers\Public\SeoController;
use Tests\TestCase;

class SeoControllerTest extends TestCase
{
    public function testRobotsKeepsPrivatePagesOutAndPointsAtTheSitemap(): void
    {
        $txt = SeoController::robotsTxt('https://example.org/sitemap.xml');

        $this->assertTrue(str_contains($txt, "User-agent: *\n"));
        foreach (['/admin', '/my-booking', '/booking/', '/vendor/', '/reserve/', '/cron/'] as $path) {
            $this->assertTrue(str_contains($txt, 'Disallow: ' . $path . "\n"), "$path should be disallowed");
        }
        $this->assertTrue(str_contains($txt, "Sitemap: https://example.org/sitemap.xml\n"));
        $this->assertFalse(str_contains($txt, "Disallow: /\n"), 'must not block the whole site');
    }

    public function testRobotsPrefixesDisallowLinesWhenServedFromASubdirectory(): void
    {
        $txt = SeoController::robotsTxt('https://example.org/sub/sitemap.xml', '/sub/');

        $this->assertTrue(str_contains($txt, "Disallow: /sub/admin\n"));
    }

    public function testSitemapListsStaticPagesAndEachPublishedEvent(): void
    {
        $xml = SeoController::sitemapXml([
            ['slug' => 'spring-market', 'updated_at' => '2026-10-01 12:00:00'],
            ['slug' => 'a&b market'],
        ]);

        $doc = simplexml_load_string($xml);
        $this->assertNotNull($doc, 'sitemap must be well-formed XML');
        $all = [];
        foreach ($doc->url as $url) {
            $all[] = (string) $url->loc;
        }

        $this->assertSame(8, count($all));
        $this->assertTrue(str_contains($all[6], 'events/spring-market'));
        $this->assertTrue(str_contains($all[7], 'events/a%26b%20market'), 'slug must be URL-encoded');
        $this->assertTrue(str_contains($xml, '<lastmod>2026-10-01T12:00:00+00:00</lastmod>'));
    }

    public function testSitemapWithNoEventsStillListsTheStaticPages(): void
    {
        $doc = simplexml_load_string(SeoController::sitemapXml([]));

        $this->assertSame(6, count($doc->url));
    }
}
