<?php

namespace App\Models;

class Advertisement extends Model
{
    public static function all(): array
    {
        return self::db()->query('SELECT * FROM advertisements ORDER BY sort_order, id')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM advertisements WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $businessName, string $imagePath, ?string $linkUrl): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO advertisements (business_name, image_path, link_url) VALUES (:business_name, :image_path, :link_url)'
        );
        $stmt->execute([
            'business_name' => $businessName,
            'image_path' => $imagePath,
            'link_url' => $linkUrl ?: null,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function delete(int $id): void
    {
        self::db()->prepare('DELETE FROM advertisements WHERE id = :id')->execute(['id' => $id]);
    }
}
