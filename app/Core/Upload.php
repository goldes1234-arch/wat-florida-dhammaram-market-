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

    private const MAX_FILE_BYTES = 50 * 1024 * 1024; // 50MB — big enough for an audiobook chapter
    private const ALLOWED_FILE_TYPES = [
        'application/pdf' => 'pdf',
        'audio/mpeg' => 'mp3',
        'audio/mp4' => 'm4a',
        'audio/x-m4a' => 'm4a',
        'audio/wav' => 'wav',
        'audio/x-wav' => 'wav',
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

    /**
     * Stores an arbitrary downloadable file (PDF, audio) as-is — no re-encoding is
     * possible for these types the way storeImage() re-renders images, so the type
     * check via finfo() (the real content, not the client-supplied filename/mime)
     * plus deriving the stored filename's extension from our own allow-list (never
     * from the upload) is what keeps this safe.
     */
    public static function storeFile(array $file, string $subdir, ?string &$error = null): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $error = Lang::get('upload.failed');
            return null;
        }

        if ($file['size'] > self::MAX_FILE_BYTES) {
            $error = Lang::get('upload.too_large');
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!isset(self::ALLOWED_FILE_TYPES[$mime])) {
            $error = Lang::get('upload.bad_type');
            return null;
        }

        $ext = self::ALLOWED_FILE_TYPES[$mime];
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $relativePath = trim($subdir, '/') . '/' . $filename;
        $fullDir = BASE_PATH . '/uploads/' . trim($subdir, '/');
        $fullPath = BASE_PATH . '/uploads/' . $relativePath;

        if (!is_dir($fullDir)) {
            mkdir($fullDir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
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
