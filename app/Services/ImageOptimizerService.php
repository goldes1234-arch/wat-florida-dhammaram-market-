<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Setting;

/**
 * One-off clean-up for photos uploaded before uploads were resized: re-encodes each to a web size
 * (plus a thumbnail where the page uses one), points the database at the new file, and moves the
 * original to storage/originals/ (web-denied) instead of deleting it, so nothing is lost.
 * Works in small batches so a shared host's time limit is never hit; run it again until nothing is pending.
 */
class ImageOptimizerService
{
    private const BIG_FILE_BYTES = 700 * 1024;
    private const LOGO_BIG_BYTES = 300 * 1024;

    /** @return array<string,int> pending image count per kind, only non-zero kinds */
    public static function pendingCounts(): array
    {
        $counts = [];
        foreach (self::items() as $item) {
            $counts[$item['kind']] = ($counts[$item['kind']] ?? 0) + 1;
        }
        return $counts;
    }

    public static function pendingTotal(): int
    {
        return array_sum(self::pendingCounts());
    }

    /**
     * @param callable|null $only optional filter(array $item): bool — lets tests limit the run to their own fixtures
     * @param string|null $subdirOverride tests write the new files here instead of the real uploads folders
     * @return array{processed:int, saved:int, failed:string[], remaining:int}
     */
    public static function run(int $maxItems = 10, int $maxSeconds = 25, ?callable $only = null, ?string $subdirOverride = null): array
    {
        $started = time();
        $processed = 0;
        $saved = 0;
        $failed = [];

        foreach (self::items() as $item) {
            if ($only !== null && !$only($item)) {
                continue;
            }
            // Only successes count toward the batch size, so a few files that cannot be shrunk never block the rest;
            // the attempt cap and the clock still bound the run.
            if ($processed >= $maxItems || $processed + count($failed) >= $maxItems * 3 || time() - $started >= $maxSeconds) {
                break;
            }
            $before = (int) @filesize(BASE_PATH . '/uploads/' . $item['path']);
            $error = null;
            $result = self::optimize($item, $error, $subdirOverride);
            if ($result === null) {
                $failed[] = $item['path'] . ($error ? ' — ' . $error : '');
                continue;
            }
            $processed++;
            $saved += max(0, $before - $result);
        }

        return ['processed' => $processed, 'saved' => $saved, 'failed' => $failed, 'remaining' => self::pendingTotal()];
    }

    /** Everything that still needs work, in a stable order. Cheap: one query per kind plus a stat() per file. */
    private static function items(): array
    {
        $pdo = Database::connection();
        $items = [];

        foreach ($pdo->query('SELECT id, image_path FROM gallery_photos WHERE thumb_path IS NULL ORDER BY id')->fetchAll() as $r) {
            $items[] = ['kind' => 'gallery', 'id' => (int) $r['id'], 'path' => $r['image_path']];
        }
        foreach ($pdo->query('SELECT id, banner_image FROM events WHERE banner_image IS NOT NULL AND banner_thumb IS NULL AND deleted_at IS NULL ORDER BY id')->fetchAll() as $r) {
            $items[] = ['kind' => 'event_banner', 'id' => (int) $r['id'], 'path' => $r['banner_image']];
        }
        foreach ($pdo->query('SELECT id, image_path FROM event_photos ORDER BY id')->fetchAll() as $r) {
            if (self::needsShrinking($r['image_path'], GalleryImageService::FULL_SIZE, self::BIG_FILE_BYTES)) {
                $items[] = ['kind' => 'event_photo', 'id' => (int) $r['id'], 'path' => $r['image_path']];
            }
        }

        $settings = Setting::get(true);
        if (!empty($settings['hero_banner_image']) && self::needsShrinking($settings['hero_banner_image'], 2400, self::BIG_FILE_BYTES)) {
            $items[] = ['kind' => 'hero', 'id' => 1, 'path' => $settings['hero_banner_image']];
        }
        if (!empty($settings['logo_path']) && self::needsShrinking($settings['logo_path'], 600, self::LOGO_BIG_BYTES)) {
            $items[] = ['kind' => 'logo', 'id' => 1, 'path' => $settings['logo_path']];
        }

        return $items;
    }

    private static function needsShrinking(string $relative, int $maxSide, int $bigBytes): bool
    {
        $abs = self::absolute($relative);
        if ($abs === null) {
            return false;
        }
        $info = @getimagesize($abs);
        if ($info === false) {
            return false;
        }
        return filesize($abs) > $bigBytes || max($info[0], $info[1]) > $maxSide * 1.05;
    }

    /** uploads/-relative path to a real file inside uploads/, or null (also blocks any ../ trick). */
    private static function absolute(string $relative): ?string
    {
        $root = realpath(BASE_PATH . '/uploads');
        $file = $root ? realpath($root . '/' . ltrim($relative, '/')) : false;
        return ($file && is_file($file) && str_starts_with($file, $root . DIRECTORY_SEPARATOR)) ? $file : null;
    }

    /** @return int|null size in bytes of the new main file, or null on failure ($error set) */
    private static function optimize(array $item, ?string &$error, ?string $subdirOverride = null): ?int
    {
        $abs = self::absolute($item['path']);
        if ($abs === null) {
            $error = 'file missing';
            return null;
        }

        [$subdir, $size, $thumb, $keep] = match ($item['kind']) {
            'hero' => ['branding', 2400, false, false],
            'logo' => ['branding', 600, false, true],
            'event_photo' => ['events', GalleryImageService::FULL_SIZE, false, false],
            'event_banner' => ['events', GalleryImageService::FULL_SIZE, true, false],
            default => ['gallery', GalleryImageService::FULL_SIZE, true, false],
        };

        $new = GalleryImageService::optimizeFile($abs, $subdirOverride ?? $subdir, $error, $size, $thumb, $keep);
        if ($new === null) {
            return null;
        }

        $newAbs = BASE_PATH . '/uploads/' . $new['path'];
        $newSize = (int) filesize($newAbs);

        // Photos that need a thumbnail always switch; the others only if it actually got smaller.
        if (!$thumb && $newSize >= filesize($abs)) {
            self::discard($new);
            $error = 'already small';
            return null;
        }

        try {
            self::point($item, $new);
        } catch (\Throwable $e) {
            self::discard($new);
            $error = $e->getMessage();
            return null;
        }

        self::archiveOriginal($item['path']);
        return $newSize;
    }

    private static function point(array $item, array $new): void
    {
        $pdo = Database::connection();
        switch ($item['kind']) {
            case 'gallery':
                $pdo->prepare('UPDATE gallery_photos SET image_path = :p, thumb_path = :t WHERE id = :id')
                    ->execute(['p' => $new['path'], 't' => $new['thumb'], 'id' => $item['id']]);
                break;
            case 'event_banner':
                $pdo->prepare('UPDATE events SET banner_image = :p, banner_thumb = :t WHERE id = :id')
                    ->execute(['p' => $new['path'], 't' => $new['thumb'], 'id' => $item['id']]);
                break;
            case 'event_photo':
                $pdo->prepare('UPDATE event_photos SET image_path = :p WHERE id = :id')
                    ->execute(['p' => $new['path'], 'id' => $item['id']]);
                break;
            case 'hero':
                Setting::update(['hero_banner_image' => $new['path']]);
                break;
            case 'logo':
                Setting::update(['logo_path' => $new['path']]);
                break;
        }
    }

    private static function discard(array $new): void
    {
        @unlink(BASE_PATH . '/uploads/' . $new['path']);
        if (!empty($new['thumb'])) {
            @unlink(BASE_PATH . '/uploads/' . $new['thumb']);
        }
    }

    /** Moves the replaced original out of the public uploads folder rather than deleting it. */
    private static function archiveOriginal(string $relative): void
    {
        $from = self::absolute($relative);
        if ($from === null) {
            return;
        }
        $to = BASE_PATH . '/storage/originals/' . ltrim($relative, '/');
        if (!is_dir(dirname($to))) {
            mkdir(dirname($to), 0755, true);
        }
        @rename($from, $to);
    }
}
