ALTER TABLE admin_users ADD COLUMN totp_secret VARCHAR(32) NULL AFTER reset_token_expires_at;
ALTER TABLE admin_users ADD COLUMN totp_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER totp_secret;
ALTER TABLE admin_users ADD COLUMN totp_backup_codes TEXT NULL AFTER totp_enabled;
