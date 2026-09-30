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

    public static function delete(int $id): void
    {
        $stmt = self::db()->prepare('DELETE FROM admin_users WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function countByRole(string $role): int
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) AS total FROM admin_users WHERE role = :role');
        $stmt->execute(['role' => $role]);
        return (int) $stmt->fetch()['total'];
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
        // Compares against a PHP-computed timestamp rather than SQL NOW() — MySQL's
        // NOW() follows the server's local OS timezone by default, which can silently
        // differ from PHP's (this app runs PHP in UTC; see date_default_timezone_set()
        // in index.php), making reset_token_expires_at (set from PHP's clock) compare
        // wrong against it by however many hours the two clocks are apart.
        $stmt = self::db()->prepare(
            'SELECT * FROM admin_users WHERE reset_token_hash = :hash AND reset_token_expires_at > :now'
        );
        $stmt->execute(['hash' => $tokenHash, 'now' => date('Y-m-d H:i:s')]);
        return $stmt->fetch() ?: null;
    }

    public static function resetPassword(int $id, string $passwordHash): void
    {
        $stmt = self::db()->prepare(
            'UPDATE admin_users SET password_hash = :hash, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = :id'
        );
        $stmt->execute(['hash' => $passwordHash, 'id' => $id]);
    }

    /** Stores a freshly generated secret without enabling 2FA yet — see SecurityController::enrollStart(). */
    public static function setPendingTotpSecret(int $id, string $secret): void
    {
        $stmt = self::db()->prepare('UPDATE admin_users SET totp_secret = :secret WHERE id = :id');
        $stmt->execute(['secret' => $secret, 'id' => $id]);
    }

    /** @param string[] $backupCodeHashes */
    public static function enableTotp(int $id, array $backupCodeHashes): void
    {
        $stmt = self::db()->prepare(
            'UPDATE admin_users SET totp_enabled = 1, totp_backup_codes = :codes WHERE id = :id'
        );
        $stmt->execute(['codes' => json_encode(array_values($backupCodeHashes)), 'id' => $id]);
    }

    public static function disableTotp(int $id): void
    {
        $stmt = self::db()->prepare(
            'UPDATE admin_users SET totp_enabled = 0, totp_secret = NULL, totp_backup_codes = NULL WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    /** Removes one matching backup code hash (one-time use) and returns whether it was found. */
    public static function consumeBackupCode(int $id, string $codeHash): bool
    {
        $user = self::find($id);
        $codes = $user ? (json_decode((string) $user['totp_backup_codes'], true) ?: []) : [];

        $index = array_search($codeHash, $codes, true);
        if ($index === false) {
            return false;
        }

        unset($codes[$index]);
        $stmt = self::db()->prepare('UPDATE admin_users SET totp_backup_codes = :codes WHERE id = :id');
        $stmt->execute(['codes' => json_encode(array_values($codes)), 'id' => $id]);

        return true;
    }
}
