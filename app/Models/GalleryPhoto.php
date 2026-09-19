<?php

namespace App\Models;

class GalleryPhoto extends Model
{
    public static function all(): array
    {
        return self::db()->query('SELECT * FROM gallery_photos ORDER BY sort_order, id')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM gallery_photos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $imagePath, ?string $caption): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO gallery_photos (image_path, caption) VALUES (:image_path, :caption)'
        );
        $stmt->execute(['image_path' => $imagePath, 'caption' => $caption ?: null]);
        return (int) self::db()->lastInsertId();
    }

    public static function delete(int $id): void
    {
        self::db()->prepare('DELETE FROM gallery_photos WHERE id = :id')->execute(['id' => $id]);
    }
}
