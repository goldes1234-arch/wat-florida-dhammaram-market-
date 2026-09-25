<?php

namespace App\Models;

class Event extends Model
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM events WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM events WHERE slug = :slug AND deleted_at IS NULL');
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch() ?: null;
    }

    public static function allForAdmin(): array
    {
        return self::db()->query(
            'SELECT * FROM events WHERE deleted_at IS NULL ORDER BY start_date DESC'
        )->fetchAll();
    }

    public static function publishedForPublic(): array
    {
        // Surface open-for-booking events first, then upcoming ones, and push
        // already-closed events to the bottom rather than sorting purely by date.
        return self::db()->query(
            "SELECT * FROM events WHERE deleted_at IS NULL AND is_published = 1
             ORDER BY
                CASE
                    WHEN NOW() BETWEEN booking_open_at AND booking_close_at THEN 0
                    WHEN NOW() < booking_open_at THEN 1
                    ELSE 2
                END,
                start_date ASC"
        )->fetchAll();
    }

    public static function slugExists(string $slug, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $stmt = self::db()->prepare('SELECT id FROM events WHERE slug = :slug AND id <> :id');
            $stmt->execute(['slug' => $slug, 'id' => $excludeId]);
        } else {
            $stmt = self::db()->prepare('SELECT id FROM events WHERE slug = :slug');
            $stmt->execute(['slug' => $slug]);
        }
        return (bool) $stmt->fetch();
    }

    public static function create(array $data): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO events
                (slug, name_th, name_en, description_th, description_en, venue_name,
                 start_date, end_date, booking_open_at, booking_close_at,
                 banner_image, floorplan_image, layout_mode, is_published, created_by)
             VALUES
                (:slug, :name_th, :name_en, :description_th, :description_en, :venue_name,
                 :start_date, :end_date, :booking_open_at, :booking_close_at,
                 :banner_image, :floorplan_image, :layout_mode, :is_published, :created_by)'
        );
        $stmt->execute([
            'slug' => $data['slug'],
            'name_th' => $data['name_th'],
            'name_en' => $data['name_en'] ?: null,
            'description_th' => $data['description_th'] ?: null,
            'description_en' => $data['description_en'] ?: null,
            'venue_name' => $data['venue_name'] ?: null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'booking_open_at' => $data['booking_open_at'],
            'booking_close_at' => $data['booking_close_at'],
            'banner_image' => $data['banner_image'] ?? null,
            'floorplan_image' => $data['floorplan_image'] ?? null,
            'layout_mode' => $data['layout_mode'] ?? 'grid',
            'is_published' => $data['is_published'] ?? 0,
            'created_by' => $data['created_by'] ?? null,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $fields = [
            'slug', 'name_th', 'name_en', 'description_th', 'description_en', 'venue_name',
            'start_date', 'end_date', 'booking_open_at', 'booking_close_at', 'layout_mode', 'is_published',
        ];
        $set = [];
        $params = ['id' => $id];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $set[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        if (array_key_exists('banner_image', $data)) {
            $set[] = 'banner_image = :banner_image';
            $params['banner_image'] = $data['banner_image'];
        }
        if (array_key_exists('floorplan_image', $data)) {
            $set[] = 'floorplan_image = :floorplan_image';
            $params['floorplan_image'] = $data['floorplan_image'];
        }
        if (!$set) {
            return;
        }
        $sql = 'UPDATE events SET ' . implode(', ', $set) . ' WHERE id = :id';
        self::db()->prepare($sql)->execute($params);
    }

    public static function softDelete(int $id): void
    {
        $stmt = self::db()->prepare('UPDATE events SET deleted_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function markOpenNotified(int $id): void
    {
        $stmt = self::db()->prepare('UPDATE events SET open_notified_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function counts(): array
    {
        return self::db()->query(
            "SELECT
                SUM(CASE WHEN deleted_at IS NULL AND is_published = 1 THEN 1 ELSE 0 END) AS published,
                SUM(CASE WHEN deleted_at IS NULL AND is_published = 0 THEN 1 ELSE 0 END) AS drafts
             FROM events"
        )->fetch();
    }
}
