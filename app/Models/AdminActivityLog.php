<?php

namespace App\Models;

class AdminActivityLog extends Model
{
    public static function create(array $data): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO admin_activity_logs (admin_id, admin_name, action, subject_type, subject_id, description, ip_address)
             VALUES (:admin_id, :admin_name, :action, :subject_type, :subject_id, :description, :ip_address)'
        );
        $stmt->execute([
            'admin_id' => $data['admin_id'],
            'admin_name' => $data['admin_name'],
            'action' => $data['action'],
            'subject_type' => $data['subject_type'],
            'subject_id' => $data['subject_id'],
            'description' => $data['description'],
            'ip_address' => $data['ip_address'],
        ]);
        return (int) self::db()->lastInsertId();
    }

    /** @return array<int, array<string, mixed>> newest first */
    public static function recent(int $limit = 200): array
    {
        $stmt = self::db()->prepare('SELECT * FROM admin_activity_logs ORDER BY created_at DESC, id DESC LIMIT :limit');
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
