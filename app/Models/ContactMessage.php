<?php

namespace App\Models;

class ContactMessage extends Model
{
    public static function create(string $name, ?string $email, ?string $phone, string $message): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO contact_messages (name, email, phone, message) VALUES (:name, :email, :phone, :message)'
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email ?: null,
            'phone' => $phone ?: null,
            'message' => $message,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function allForAdmin(): array
    {
        $stmt = self::db()->query('SELECT * FROM contact_messages ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM contact_messages WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function markRead(int $id): void
    {
        self::db()->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = :id')->execute(['id' => $id]);
    }

    public static function unreadCount(): int
    {
        $stmt = self::db()->query('SELECT COUNT(*) AS total FROM contact_messages WHERE is_read = 0');
        return (int) $stmt->fetch()['total'];
    }
}
