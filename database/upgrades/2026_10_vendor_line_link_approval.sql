ALTER TABLE vendors ADD COLUMN IF NOT EXISTS line_pending_user_id VARCHAR(64) NULL AFTER line_user_id;
ALTER TABLE vendors ADD COLUMN IF NOT EXISTS line_link_requested_at DATETIME NULL AFTER line_pending_user_id;
