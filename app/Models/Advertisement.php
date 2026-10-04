<?php

namespace App\Models;

class Advertisement extends Model
{
    /** Photos per shop, cover included. */
    public const MAX_IMAGES = 5;

    /** Approved ads shown on the public site. */
    public static function approved(): array
    {
        $stmt = self::db()->prepare(
            "SELECT * FROM advertisements WHERE status = 'approved' ORDER BY sort_order, id"
        );
        $stmt->execute();
        return self::withImages($stmt->fetchAll());
    }

    /** Submissions awaiting admin review. */
    public static function pending(): array
    {
        $stmt = self::db()->prepare(
            "SELECT * FROM advertisements WHERE status = 'pending' ORDER BY id"
        );
        $stmt->execute();
        return self::withImages($stmt->fetchAll());
    }

    /**
     * Adds an 'images' list to each ad: the cover first, then the extra photos in order. Each entry is
     * ['id' => extra image id or null for the cover, 'path' => full image, 'thumb' => preview or the full image].
     * One query for all ads, so a page with many shops stays cheap.
     */
    public static function withImages(array $ads): array
    {
        $extras = [];
        if ($ads) {
            $ids = array_map(static fn ($a) => (int) $a['id'], $ads);
            $marks = implode(',', array_fill(0, count($ids), '?'));
            $stmt = self::db()->prepare("SELECT * FROM advertisement_images WHERE advertisement_id IN ($marks) ORDER BY sort_order, id");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() as $row) {
                $extras[(int) $row['advertisement_id']][] = $row;
            }
        }

        foreach ($ads as &$ad) {
            $ad['images'] = [['id' => null, 'path' => $ad['image_path'], 'thumb' => $ad['thumb_path'] ?? null ?: $ad['image_path']]];
            foreach ($extras[(int) $ad['id']] ?? [] as $row) {
                $ad['images'][] = ['id' => (int) $row['id'], 'path' => $row['image_path'], 'thumb' => $row['thumb_path'] ?: $row['image_path']];
            }
        }
        unset($ad);

        return $ads;
    }

    public static function extraImageCount(int $adId): int
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM advertisement_images WHERE advertisement_id = :id');
        $stmt->execute(['id' => $adId]);
        return (int) $stmt->fetchColumn();
    }

    /** How many more photos this shop can take (the cover counts as one). */
    public static function roomForImages(int $adId): int
    {
        return max(0, self::MAX_IMAGES - 1 - self::extraImageCount($adId));
    }

    public static function addImage(int $adId, string $path, ?string $thumb): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO advertisement_images (advertisement_id, image_path, thumb_path, sort_order)
             VALUES (:ad, :p, :t, (SELECT COALESCE(MAX(sort_order), 0) + 1 FROM advertisement_images x WHERE x.advertisement_id = :ad2))'
        );
        $stmt->execute(['ad' => $adId, 'p' => $path, 't' => $thumb, 'ad2' => $adId]);
        return (int) self::db()->lastInsertId();
    }

    public static function findImage(int $imageId): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM advertisement_images WHERE id = :id');
        $stmt->execute(['id' => $imageId]);
        return $stmt->fetch() ?: null;
    }

    public static function deleteImage(int $imageId): void
    {
        self::db()->prepare('DELETE FROM advertisement_images WHERE id = :id')->execute(['id' => $imageId]);
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
        ?string $contactPhone = null,
        ?string $thumbPath = null
    ): int {
        $stmt = self::db()->prepare(
            'INSERT INTO advertisements (business_name, description, image_path, thumb_path, link_url, status, contact_name, contact_phone)
             VALUES (:business_name, :description, :image_path, :thumb_path, :link_url, :status, :contact_name, :contact_phone)'
        );
        $stmt->execute([
            'business_name' => $businessName,
            'description' => $description ?: null,
            'image_path' => $imagePath,
            'thumb_path' => $thumbPath,
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
