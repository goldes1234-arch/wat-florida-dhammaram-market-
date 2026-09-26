<?php

namespace App\Models;

class Advertisement extends Model
{
    /** Approved ads shown on the public site. */
    public static function approved(): array
    {
        $stmt = self::db()->prepare(
            "SELECT * FROM advertisements WHERE status = 'approved' ORDER BY sort_order, id"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Submissions awaiting admin review. */
    public static function pending(): array
    {
        $stmt = self::db()->prepare(
            "SELECT * FROM advertisements WHERE status = 'pending' ORDER BY id"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function pendingCount(): int
    {
        $stmt = self::db()->query("SELECT COUNT(*) AS total FROM advertisements WHERE status = 'pending'");
        return (int) $stmt->fetch()['total'];
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM advertisements WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(
        string $businessName,
        string $imagePath,
        ?string $linkUrl,
        ?string $description = null,
        string $status = 'approved',
        ?string $contactName = null,
        ?string $contactPhone = null
    ): int {
        $stmt = self::db()->prepare(
            'INSERT INTO advertisements (business_name, description, image_path, link_url, status, contact_name, contact_phone)
             VALUES (:business_name, :description, :image_path, :link_url, :status, :contact_name, :contact_phone)'
        );
        $stmt->execute([
            'business_name' => $businessName,
            'description' => $description ?: null,
            'image_path' => $imagePath,
            'link_url' => $linkUrl ?: null,
            'status' => $status,
            'contact_name' => $contactName ?: null,
            'contact_phone' => $contactPhone ?: null,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function approve(int $id): void
    {
        self::db()->prepare("UPDATE advertisements SET status = 'approved' WHERE id = :id")->execute(['id' => $id]);
    }

    public static function delete(int $id): void
    {
        self::db()->prepare('DELETE FROM advertisements WHERE id = :id')->execute(['id' => $id]);
    }
}
