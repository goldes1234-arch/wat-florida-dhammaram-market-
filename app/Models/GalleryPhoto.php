<?php

namespace App\Models;

class GalleryPhoto extends Model
{
    public static function all(): array
    {
        return self::db()->query('SELECT * FROM gallery_photos ORDER BY sort_order, id')->fetchAll();
    }

    public static function count(?int $eventId = null): int
    {
        if ($eventId === null) {
            return (int) self::db()->query('SELECT COUNT(*) FROM gallery_photos')->fetchColumn();
        }
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM gallery_photos WHERE event_id = :event');
        $stmt->execute(['event' => $eventId]);
        return (int) $stmt->fetchColumn();
    }

    /** One page of photos, in the same order as all() (optionally only one event's album) — drives the public /gallery page. */
    public static function page(int $page, int $perPage, ?int $eventId = null): array
    {
        $perPage = max(1, $perPage);
        $sql = 'SELECT * FROM gallery_photos' . ($eventId !== null ? ' WHERE event_id = :event' : '')
            . ' ORDER BY sort_order, id LIMIT ' . $perPage . ' OFFSET ' . ((max(1, $page) - 1) * $perPage);
        $stmt = self::db()->prepare($sql);
        $stmt->execute($eventId !== null ? ['event' => $eventId] : []);
        return $stmt->fetchAll();
    }

    /** Events that have at least one photo, for the album filter chips. */
    public static function albums(): array
    {
        return self::db()->query(
            'SELECT events.id, events.name_th, events.name_en, COUNT(gallery_photos.id) AS photo_count
             FROM gallery_photos
             JOIN events ON events.id = gallery_photos.event_id AND events.deleted_at IS NULL
             GROUP BY events.id, events.name_th, events.name_en
             ORDER BY MAX(events.start_date) DESC'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM gallery_photos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** New photos go to the end of the current order. */
    public static function create(string $imagePath, ?string $thumbPath, ?string $caption, ?int $eventId = null): int
    {
        $next = (int) self::db()->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM gallery_photos')->fetchColumn();
        $stmt = self::db()->prepare(
            'INSERT INTO gallery_photos (image_path, thumb_path, caption, event_id, sort_order)
             VALUES (:image_path, :thumb_path, :caption, :event_id, :sort_order)'
        );
        $stmt->execute([
            'image_path' => $imagePath,
            'thumb_path' => $thumbPath,
            'caption' => $caption ?: null,
            'event_id' => $eventId ?: null,
            'sort_order' => $next,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function updateDetails(int $id, ?string $caption, ?int $eventId): void
    {
        self::db()->prepare('UPDATE gallery_photos SET caption = :caption, event_id = :event_id WHERE id = :id')
            ->execute(['caption' => $caption ?: null, 'event_id' => $eventId ?: null, 'id' => $id]);
    }

    /**
     * Swaps a photo with its neighbour ($direction -1 = earlier, +1 = later). Older photos all
     * share sort_order 0, so the whole list is renumbered 1..n first to make the swap well-defined.
     */
    public static function move(int $id, int $direction): void
    {
        $ids = array_map('intval', array_column(self::all(), 'id'));
        $pos = array_search($id, $ids, true);
        if ($pos === false) {
            return;
        }
        $target = $pos + ($direction < 0 ? -1 : 1);
        if ($target >= 0 && $target < count($ids)) {
            [$ids[$pos], $ids[$target]] = [$ids[$target], $ids[$pos]];
        }

        $pdo = self::db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE gallery_photos SET sort_order = :n WHERE id = :id');
            foreach ($ids as $i => $photoId) {
                $stmt->execute(['n' => $i + 1, 'id' => $photoId]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Applies a drag-and-drop order: the ids listed come first in that order, anything not listed keeps its relative place after them. */
    public static function setOrder(array $ids): void
    {
        $listed = array_values(array_unique(array_map('intval', $ids)));
        $rest = array_values(array_filter(
            array_map(static fn (array $r) => (int) $r['id'], self::all()),
            static fn (int $id) => !in_array($id, $listed, true)
        ));
        $final = array_merge($listed, $rest);

        $pdo = self::db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE gallery_photos SET sort_order = :n WHERE id = :id');
            foreach ($final as $i => $id) {
                $stmt->execute(['n' => $i + 1, 'id' => $id]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** sha1 of a stored image, used to spot the same photo uploaded twice (same source re-encodes to identical bytes). */
    public static function fingerprint(?string $relativePath): ?string
    {
        if (!$relativePath) {
            return null;
        }
        $root = realpath(BASE_PATH . '/uploads');
        $file = $root ? realpath($root . '/' . ltrim($relativePath, '/')) : false;
        return ($file && is_file($file) && str_starts_with($file, $root . DIRECTORY_SEPARATOR)) ? sha1_file($file) : null;
    }

    /** @param array<int,array> $photos @return array<int,int> photo id => id of the earlier photo it duplicates */
    public static function duplicatesAmong(array $photos): array
    {
        $seen = [];
        $dups = [];
        foreach ($photos as $photo) {
            $hash = self::fingerprint($photo['image_path']);
            if ($hash === null) {
                continue;
            }
            if (isset($seen[$hash])) {
                $dups[(int) $photo['id']] = $seen[$hash];
            } else {
                $seen[$hash] = (int) $photo['id'];
            }
        }
        return $dups;
    }

    public static function delete(int $id): void
    {
        self::db()->prepare('DELETE FROM gallery_photos WHERE id = :id')->execute(['id' => $id]);
    }
}
