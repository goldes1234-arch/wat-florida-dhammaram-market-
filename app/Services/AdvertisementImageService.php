<?php

namespace App\Services;

use App\Core\Lang;
use App\Models\Advertisement;

/** Stores the photos uploaded for a shop listing: web-sized copy + preview each, at most Advertisement::MAX_IMAGES per shop. */
class AdvertisementImageService
{
    private const SUBDIR = 'ads';

    /**
     * Turns the PHP "images[]" upload structure into one file array per chosen file, skipping empty slots.
     * Also accepts a plain single-file field, so old forms/clients keep working.
     *
     * @return array<int, array{name:string,type:string,tmp_name:string,error:int,size:int}>
     */
    public static function normalize(?array $field): array
    {
        if (!$field || !isset($field['error'])) {
            return [];
        }
        if (!is_array($field['error'])) {
            return $field['error'] === UPLOAD_ERR_NO_FILE ? [] : [$field];
        }

        $files = [];
        foreach ($field['error'] as $i => $error) {
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $files[] = [
                'name' => $field['name'][$i] ?? '',
                'type' => $field['type'][$i] ?? '',
                'tmp_name' => $field['tmp_name'][$i] ?? '',
                'error' => (int) $error,
                'size' => (int) ($field['size'][$i] ?? 0),
            ];
        }
        return $files;
    }

    /**
     * Stores every file or none: if one fails, the ones already written are removed again.
     *
     * @param array $files from normalize()
     * @param int   $limit how many photos may be added (callers pass the room left)
     * @return array<int, array{path:string, thumb:?string}>|null null with $error set
     */
    public static function storeAll(array $files, int $limit, ?string &$error = null): ?array
    {
        if (!$files) {
            $error = Lang::get('ads.images_required');
            return null;
        }
        if (count($files) > $limit) {
            $error = Lang::get('ads.too_many_images', ['max' => (string) Advertisement::MAX_IMAGES]);
            return null;
        }

        $stored = [];
        foreach ($files as $file) {
            $one = GalleryImageService::store($file, self::SUBDIR, $error);
            if ($one === null) {
                foreach ($stored as $done) {
                    self::deleteFiles($done['path'], $done['thumb']);
                }
                return null;
            }
            $stored[] = $one;
        }
        return $stored;
    }

    public static function deleteFiles(?string $path, ?string $thumb): void
    {
        \App\Core\Upload::delete($path);
        \App\Core\Upload::delete($thumb);
    }
}
