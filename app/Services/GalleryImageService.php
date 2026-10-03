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
    public const MAX_SOURCE_PIXELS = 40_000_000;
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

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $info = @getimagesize($file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || $info === false) {
            $error = Lang::get('upload.bad_type');
            return null;
        }
        if ($info[0] * $info[1] > self::MAX_SOURCE_PIXELS) {
            $error = Lang::get('upload.too_many_pixels');
            return null;
        }

        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
            'image/png' => @imagecreatefrompng($file['tmp_name']),
            default => @imagecreatefromwebp($file['tmp_name']),
        };
        if ($source === false) {
            $error = Lang::get('upload.bad_type');
            return null;
        }

        if ($mime === 'image/jpeg') {
            $source = self::applyExifOrientation($source, $file['tmp_name']);
        }

        $useWebp = function_exists('imagewebp');
        $ext = $useWebp ? 'webp' : 'jpg';
        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $dir = BASE_PATH . '/uploads/' . trim($subdir, '/');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if ($withThumb && !is_dir($dir . '/thumbs')) {
            mkdir($dir . '/thumbs', 0755, true);
        }

        $full = self::resized($source, $fullSize);
        $okFull = self::write($full, $dir . '/' . $name, $useWebp);
        imagedestroy($full);

        $okThumb = true;
        if ($withThumb) {
            $thumb = self::resized($source, self::THUMB_SIZE);
            $okThumb = self::write($thumb, $dir . '/thumbs/' . $name, $useWebp);
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

    private static function write(\GdImage $img, string $path, bool $webp): bool
    {
        if ($webp) {
            return imagewebp($img, $path, 82);
        }
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
