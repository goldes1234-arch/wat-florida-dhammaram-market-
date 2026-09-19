<?php

namespace App\Models;

class EventContact extends Model
{
    public static function forEvent(int $eventId): array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM event_contacts WHERE event_id = :event_id ORDER BY sort_order, id'
        );
        $stmt->execute(['event_id' => $eventId]);
        return $stmt->fetchAll();
    }

    public static function eventIdsWithContacts(): array
    {
        $stmt = self::db()->query('SELECT DISTINCT event_id FROM event_contacts');
        return array_map('intval', array_column($stmt->fetchAll(), 'event_id'));
    }

    /**
     * Replaces the full contact list for an event in one go — the admin form always
     * submits the complete set of up to 4 slots rather than individual add/edit/delete
     * actions, so a delete-then-reinsert keeps this in sync with no orphaned rows.
     */
    public static function replaceForEvent(int $eventId, array $contacts): void
    {
        $db = self::db();
        $db->beginTransaction();

        $db->prepare('DELETE FROM event_contacts WHERE event_id = :event_id')->execute(['event_id' => $eventId]);

        $stmt = $db->prepare(
            'INSERT INTO event_contacts (event_id, name, phone, contact_channel, sort_order)
             VALUES (:event_id, :name, :phone, :contact_channel, :sort_order)'
        );
        foreach (array_values($contacts) as $i => $contact) {
            $stmt->execute([
                'event_id' => $eventId,
                'name' => $contact['name'],
                'phone' => $contact['phone'],
                'contact_channel' => $contact['contact_channel'] ?: null,
                'sort_order' => $i,
            ]);
        }

        $db->commit();
    }
}
