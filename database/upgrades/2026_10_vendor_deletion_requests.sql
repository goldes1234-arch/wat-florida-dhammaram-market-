ALTER TABLE vendors ADD COLUMN IF NOT EXISTS deletion_requested_at DATETIME NULL AFTER notes;
