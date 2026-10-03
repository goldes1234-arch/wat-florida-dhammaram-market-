<?php

namespace App\Services;

use App\Core\Lang;

/**
 * Stores a gallery photo as a web-sized copy (max 1600px) plus a small preview (max 600px),
 * so a 6MB phone photo does not get served to every visitor. EXIF orientation is applied
 * (phones store sideways pixels with a rotate flag) and the re-encode drops any other metadata.
 */
class GalleryImageService
{
    public const MAX_SOURCE_BYTES = 12 * 1024 * 1024;
    public const MAX_SOURCE_PIXELS = 30_000_000;
    public const FULL_SIZE = 1600;
    public const THUMB_SIZE = 600;
    public const POSTER_SIZE = 2000; // posters carry small print, so they keep more resolution

    /**
     * @param int  $fullSize  longest side of the stored web copy
     * @param bool $withThumb also store a THUMB_SIZE preview (thumb is null when false)
     * @return array{path: string, thumb: ?string}|null relative paths under uploads/, or null with $error set
     */
    public static function store(array $file, string $subdir, ?string &$error = null, int $fullSize = self::FULL_SIZE, bool $withThumb = true): ?array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $error = Lang::get('upload.failed');
            return null;
        }
        if ($file['size'] > self::MAX_SOURCE_BYTES) {
            $error = Lang::get('upload.too_large_gallery');
            return null;
        }

        return self::process($file['tmp_name'], $subdir, $error, $fullSize, $withThumb, false);
    }

    /**
     * Re-encodes an image that is already on disk (used to slim down photos uploaded before
     * resizing existed). $keepFormat keeps PNG/JPEG/WEBP as they are — needed for the logo, which
     * the PDF receipt can only embed as PNG or JPEG.
     *
     * @return array{path: string, thumb: ?string}|null
     */
    public static function optimizeFile(string $absPath, string $subdir, ?string &$error, int $fullSize, bool $withThumb, bool $keepFormat = false): ?array
    {
        return self::process($absPath, $subdir, $error, $fullSize, $withThumb, $keepFormat);
    }

    private static function process(string $path, string $subdir, ?string &$error, int $fullSize, bool $withThumb, bool $keepFormat): ?array
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $info = @getimagesize($path);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || $info === false) {
            $error = Lang::get('upload.bad_type');
            return null;
        }
        if ($info[0] * $info[1] > self::MAX_SOURCE_PIXELS || !self::fitsInMemory($info[0] * $info[1])) {
            $error = Lang::get('upload.too_many_pixels');
            return null;
        }

        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            default => @imagecreatefromwebp($path),
        };
        if ($source === false) {
            $error = Lang::get('upload.bad_type');
            return null;
        }

        if ($mime === 'image/jpeg') {
            $source = self::applyExifOrientation($source, $path);
        }

        $format = $keepFormat ? $mime : (function_exists('imagewebp') ? 'image/webp' : 'image/jpeg');
        $ext = ['image/webp' => 'webp', 'image/png' => 'png', 'image/jpeg' => 'jpg'][$format];
        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $dir = BASE_PATH . '/uploads/' . trim($subdir, '/');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if ($withThumb && !is_dir($dir . '/thumbs')) {
            mkdir($dir . '/thumbs', 0755, true);
        }

        $full = self::resized($source, $fullSize);
        $okFull = self::write($full, $dir . '/' . $name, $format);
        imagedestroy($full);

        $okThumb = true;
        if ($withThumb) {
            $thumb = self::resized($source, self::THUMB_SIZE);
            $okThumb = self::write($thumb, $dir . '/thumbs/' . $name, $format);
            imagedestroy($thumb);
        }
        imagedestroy($source);

        if (!$okFull || !$okThumb) {
            @unlink($dir . '/' . $name);
            @unlink($dir . '/thumbs/' . $name);
            $error = Lang::get('upload.failed');
            return null;
        }

        $rel = trim($subdir, '/');
        return ['path' => $rel . '/' . $name, 'thumb' => $withThumb ? $rel . '/thumbs/' . $name : null];
    }

    /** GD holds ~4 bytes per pixel plus working copies; a decode that would blow PHP's memory limit is a fatal error that cannot be caught, so refuse up front. */
    private static function fitsInMemory(int $pixels): bool
    {
        $limit = ini_get('memory_limit');
        if ($limit === '-1' || $limit === false || $limit === '') {
            return true;
        }
        $bytes = (int) $limit;
        $unit = strtolower(substr(trim($limit), -1));
        $bytes *= match ($unit) { 'g' => 1024 ** 3, 'm' => 1024 ** 2, 'k' => 1024, default => 1 };
        return $pixels * 6 < $bytes * 0.8;
    }

    /** Scales down so the longest side is at most $max (never enlarges), always returning a new image. */
    private static function resized(\GdImage $src, int $max): \GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1, $max / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);
        // Keep transparency (PNG/WebP) — flattened to white later if we have to fall back to JPEG.
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 255, 255, 255, 127));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        return $dst;
    }

    private static function write(\GdImage $img, string $path, string $format): bool
    {
        if ($format === 'image/webp') {
            return imagewebp($img, $path, 82);
        }
        if ($format === 'image/png') {
            imagealphablending($img, false);
            imagesavealpha($img, true);
            return imagepng($img, $path, 7);
        }
        // JPEG has no transparency: flatten onto white.
        $flat = imagecreatetruecolor(imagesx($img), imagesy($img));
        imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
        imagecopy($flat, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
        $ok = imagejpeg($flat, $path, 85);
        imagedestroy($flat);
        return $ok;
    }

    private static function applyExifOrientation(\GdImage $img, string $path): \GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);
        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
        if ($angle === 0) {
            return $img;
        }
        $rotated = imagerotate($img, $angle, 0);
        if ($rotated === false) {
            return $img;
        }
        imagedestroy($img);
        return $rotated;
    }
}
