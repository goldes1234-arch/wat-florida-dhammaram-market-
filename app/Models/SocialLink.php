<?php

namespace App\Models;

class SocialLink extends Model
{
    public static function all(): array
    {
        return self::db()->query('SELECT * FROM social_links ORDER BY sort_order, id')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM social_links WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $label, string $url): int
    {
        $stmt = self::db()->prepare('INSERT INTO social_links (label, url) VALUES (:label, :url)');
        $stmt->execute(['label' => $label, 'url' => $url]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, string $label, string $url): void
    {
        $stmt = self::db()->prepare('UPDATE social_links SET label = :label, url = :url WHERE id = :id');
        $stmt->execute(['label' => $label, 'url' => $url, 'id' => $id]);
    }

    public static function delete(int $id): void
    {
        self::db()->prepare('DELETE FROM social_links WHERE id = :id')->execute(['id' => $id]);
    }
}
