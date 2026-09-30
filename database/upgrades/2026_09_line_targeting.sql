ALTER TABLE settings ADD COLUMN line_channel_secret VARCHAR(100) NULL AFTER line_oa_channel_access_token;
ALTER TABLE vendors ADD COLUMN line_user_id VARCHAR(64) NULL AFTER notes;
ALTER TABLE vendors ADD UNIQUE KEY uq_vendors_line_user_id (line_user_id);
