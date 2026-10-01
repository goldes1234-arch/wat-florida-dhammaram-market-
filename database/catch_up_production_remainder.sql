ALTER TABLE bookings ADD COLUMN IF NOT EXISTS vendor_id INT UNSIGNED NULL AFTER booker_email;
ALTER TABLE bookings ADD INDEX IF NOT EXISTS idx_bookings_vendor (vendor_id);

ALTER TABLE settings ADD COLUMN IF NOT EXISTS line_channel_secret VARCHAR(100) NULL AFTER line_oa_channel_access_token;
ALTER TABLE vendors ADD COLUMN IF NOT EXISTS line_user_id VARCHAR(64) NULL AFTER notes;
ALTER TABLE vendors ADD UNIQUE KEY IF NOT EXISTS uq_vendors_line_user_id (line_user_id);

ALTER TABLE vendors ADD COLUMN IF NOT EXISTS portal_token_hash VARCHAR(64) NULL AFTER line_user_id;
ALTER TABLE vendors ADD COLUMN IF NOT EXISTS portal_token_expires_at DATETIME NULL AFTER portal_token_hash;

ALTER TABLE events ADD COLUMN IF NOT EXISTS layout_mode ENUM('grid','photo') NOT NULL DEFAULT 'grid' AFTER floorplan_image;
ALTER TABLE lots ADD COLUMN IF NOT EXISTS map_x DECIMAL(5,2) NULL AFTER grid_col;
ALTER TABLE lots ADD COLUMN IF NOT EXISTS map_y DECIMAL(5,2) NULL AFTER grid_col;

ALTER TABLE lots ADD COLUMN IF NOT EXISTS map_size ENUM('small','medium','large') NOT NULL DEFAULT 'medium' AFTER map_y;

ALTER TABLE lots ADD COLUMN IF NOT EXISTS map_shape ENUM('pin','box') NOT NULL DEFAULT 'pin' AFTER map_size;
ALTER TABLE lots ADD COLUMN IF NOT EXISTS map_rotation DECIMAL(5,1) NOT NULL DEFAULT 0 AFTER map_shape;
