<?php

namespace App\Models;

class AdminUser extends Model
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM admin_users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM admin_users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public static function touchLogin(int $id): void
    {
        $stmt = self::db()->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function all(): array
    {
        return self::db()->query('SELECT * FROM admin_users ORDER BY name')->fetchAll();
    }

    public static function create(array $data): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO admin_users (name, email, password_hash, role, is_active)
             VALUES (:name, :email, :password_hash, :role, :is_active)'
        );
        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'role' => $data['role'] ?? 'staff',
            'is_active' => $data['is_active'] ?? 1,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function emailExists(string $email): bool
    {
        $stmt = self::db()->prepare('SELECT id FROM admin_users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        return (bool) $stmt->fetch();
    }

    public static function setActive(int $id, bool $active): void
    {
        $stmt = self::db()->prepare('UPDATE admin_users SET is_active = :active WHERE id = :id');
        $stmt->execute(['active' => $active ? 1 : 0, 'id' => $id]);
    }

    public static function setResetToken(int $id, string $tokenHash, string $expiresAt): void
    {
        $stmt = self::db()->prepare(
            'UPDATE admin_users SET reset_token_hash = :hash, reset_token_expires_at = :expires WHERE id = :id'
        );
        $stmt->execute(['hash' => $tokenHash, 'expires' => $expiresAt, 'id' => $id]);
    }

    public static function findByValidResetTokenHash(string $tokenHash): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM admin_users WHERE reset_token_hash = :hash AND reset_token_expires_at > NOW()'
        );
        $stmt->execute(['hash' => $tokenHash]);
        return $stmt->fetch() ?: null;
    }

    public static function resetPassword(int $id, string $passwordHash): void
    {
        $stmt = self::db()->prepare(
            'UPDATE admin_users SET password_hash = :hash, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = :id'
        );
        $stmt->execute(['hash' => $passwordHash, 'id' => $id]);
    }
}
