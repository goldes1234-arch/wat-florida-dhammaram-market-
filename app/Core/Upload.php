<?php

namespace App\Core;

class Upload
{
    private const MAX_BYTES = 5 * 1024 * 1024; // 5MB
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Validates, re-encodes (to strip any non-image payload/EXIF) and stores an
     * uploaded image under uploads/{subdir}/. Returns the relative path to store
     * in the DB, or null with $error set on failure.
     */
    public static function storeImage(array $file, string $subdir, ?string &$error = null): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $error = Lang::get('upload.failed');
            return null;
        }

        if ($file['size'] > self::MAX_BYTES) {
            $error = Lang::get('upload.too_large');
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!isset(self::ALLOWED[$mime])) {
            $error = Lang::get('upload.bad_type');
            return null;
        }

        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $error = Lang::get('upload.bad_type');
            return null;
        }

        $image = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
            'image/png' => imagecreatefrompng($file['tmp_name']),
            'image/webp' => imagecreatefromwebp($file['tmp_name']),
            default => false,
        };

        if ($image === false) {
            $error = Lang::get('upload.bad_type');
            return null;
        }

        $ext = self::ALLOWED[$mime];
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $relativePath = trim($subdir, '/') . '/' . $filename;
        $fullDir = BASE_PATH . '/uploads/' . trim($subdir, '/');
        $fullPath = BASE_PATH . '/uploads/' . $relativePath;

        if (!is_dir($fullDir)) {
            mkdir($fullDir, 0755, true);
        }

        $saved = match ($mime) {
            'image/jpeg' => imagejpeg($image, $fullPath, 85),
            'image/png' => imagepng($image, $fullPath, 6),
            'image/webp' => imagewebp($image, $fullPath, 85),
            default => false,
        };
        imagedestroy($image);

        if (!$saved) {
            $error = Lang::get('upload.failed');
            return null;
        }

        return $relativePath;
    }

    public static function delete(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }
        $full = BASE_PATH . '/uploads/' . ltrim($relativePath, '/');
        if (is_file($full)) {
            @unlink($full);
        }
    }
}
