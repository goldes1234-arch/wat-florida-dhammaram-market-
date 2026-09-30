<?php

namespace App\Models;

class DownloadCategory extends Model
{
    public static function all(): array
    {
        return self::db()->query('SELECT * FROM download_categories ORDER BY sort_order, id')->fetchAll();
    }

    /** Categories with a files count, for the admin list — includes empty ones. */
    public static function allWithFileCounts(): array
    {
        return self::db()->query(
            'SELECT c.*, COUNT(f.id) AS file_count
             FROM download_categories c
             LEFT JOIN download_files f ON f.category_id = c.id
             GROUP BY c.id
             ORDER BY c.sort_order, c.id'
        )->fetchAll();
    }

    /** Only categories that have at least one file — what the public nav shows. */
    public static function allWithFiles(): array
    {
        return self::db()->query(
            'SELECT DISTINCT c.*
             FROM download_categories c
             JOIN download_files f ON f.category_id = c.id
             ORDER BY c.sort_order, c.id'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM download_categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $name): int
    {
        $stmt = self::db()->prepare('INSERT INTO download_categories (name) VALUES (:name)');
        $stmt->execute(['name' => $name]);
        return (int) self::db()->lastInsertId();
    }

    public static function delete(int $id): void
    {
        self::db()->prepare('DELETE FROM download_categories WHERE id = :id')->execute(['id' => $id]);
    }
}
