<?php

namespace App\Models;

class DownloadFile extends Model
{
    public static function forCategory(int $categoryId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM download_files WHERE category_id = :category_id ORDER BY sort_order, id');
        $stmt->execute(['category_id' => $categoryId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM download_files WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $categoryId, string $title, string $filePath, ?int $fileSize): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO download_files (category_id, title, file_path, file_size) VALUES (:category_id, :title, :file_path, :file_size)'
        );
        $stmt->execute([
            'category_id' => $categoryId, 'title' => $title, 'file_path' => $filePath, 'file_size' => $fileSize,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function delete(int $id): void
    {
        self::db()->prepare('DELETE FROM download_files WHERE id = :id')->execute(['id' => $id]);
    }
}
