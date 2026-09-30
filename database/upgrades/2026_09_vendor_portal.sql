ALTER TABLE vendors ADD COLUMN portal_token_hash VARCHAR(64) NULL AFTER line_user_id;
ALTER TABLE vendors ADD COLUMN portal_token_expires_at DATETIME NULL AFTER portal_token_hash;
