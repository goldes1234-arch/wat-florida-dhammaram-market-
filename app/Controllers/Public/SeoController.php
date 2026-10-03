<?php

namespace App\Controllers\Public;

use App\Core\Request;
use App\Models\Event;

/** robots.txt and sitemap.xml — tell search engines what is worth indexing and keep the rest out. */
class SeoController
{
    /** Pages that are per-person, token-gated or admin-only: never useful in search results. */
    private const PRIVATE_PATHS = [
        '/admin', '/booking/', '/my-booking', '/vendor/', '/reserve/', '/checkin-link/',
        '/cron/', '/stripe/', '/line/', '/health', '/lang/',
    ];

    public function robots(Request $request): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo self::robotsTxt(full_url('sitemap.xml'), Request::basePath());
    }

    public function sitemap(Request $request): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        echo self::sitemapXml(Event::publishedForPublic());
    }

    /** $basePath is the sub-directory the app is served from ('' on production), so Disallow lines stay correct on a local XAMPP path. */
    public static function robotsTxt(string $sitemapUrl, string $basePath = ''): string
    {
        $basePath = rtrim($basePath, '/');
        $lines = ['User-agent: *'];
        foreach (self::PRIVATE_PATHS as $path) {
            $lines[] = 'Disallow: ' . $basePath . $path;
        }
        $lines[] = '';
        $lines[] = 'Sitemap: ' . $sitemapUrl;

        return implode("\n", $lines) . "\n";
    }

    /** @param array<int, array<string, mixed>> $events published events (need 'slug', optionally 'updated_at') */
    public static function sitemapXml(array $events): string
    {
        $urls = [
            ['loc' => full_url(''), 'priority' => '1.0'],
            ['loc' => full_url('contact'), 'priority' => '0.4'],
            ['loc' => full_url('advertise'), 'priority' => '0.3'],
            ['loc' => full_url('gallery'), 'priority' => '0.5'],
            ['loc' => full_url('privacy'), 'priority' => '0.1'],
            ['loc' => full_url('terms'), 'priority' => '0.1'],
        ];
        foreach ($events as $event) {
            $urls[] = [
                'loc' => full_url('events/' . rawurlencode($event['slug'])),
                'priority' => '0.8',
                'lastmod' => !empty($event['updated_at']) ? date('c', strtotime($event['updated_at'])) : null,
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            $xml .= "  <url>\n    <loc>" . htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
            if (!empty($url['lastmod'])) {
                $xml .= '    <lastmod>' . $url['lastmod'] . "</lastmod>\n";
            }
            $xml .= '    <priority>' . $url['priority'] . "</priority>\n  </url>\n";
        }

        return $xml . "</urlset>\n";
    }
}
