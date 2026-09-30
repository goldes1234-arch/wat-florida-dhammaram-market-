ALTER TABLE admin_users ADD COLUMN checkin_link_token_hash VARCHAR(64) NULL AFTER totp_backup_codes;
