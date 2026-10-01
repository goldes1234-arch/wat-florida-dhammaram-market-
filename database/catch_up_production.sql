-- One-shot catch-up: replays every database/upgrades/*.sql statement in dependency
-- order, using MariaDB's native "IF NOT EXISTS" on every ADD COLUMN / ADD KEY /
-- CREATE TABLE so anything already applied is silently skipped. Safe to run more
-- than once. The handful of ADD CONSTRAINT (foreign key) lines don't support
-- IF NOT EXISTS in MariaDB — they're left plain, but each one is only ever
-- needed alongside a column confirmed missing in the same statement, so a
-- "duplicate" error on one of those specific lines would mean it was already
-- fine and can be ignored.

-- admin_2fa
ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS totp_secret VARCHAR(32) NULL AFTER reset_token_expires_at;
ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS totp_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER totp_secret;
ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS totp_backup_codes TEXT NULL AFTER totp_enabled;

-- checkin_link
ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS checkin_link_token_hash VARCHAR(64) NULL AFTER totp_backup_codes;

-- finance_role (MODIFY is always safe to re-run, no IF NOT EXISTS needed)
ALTER TABLE admin_users MODIFY COLUMN role ENUM('super_admin','staff','checkin','finance') NOT NULL DEFAULT 'staff';

-- advertisements + advertisement_submissions
CREATE TABLE IF NOT EXISTS advertisements (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  business_name VARCHAR(150) NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  link_url VARCHAR(255) NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE advertisements ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER business_name;
ALTER TABLE advertisements ADD COLUMN IF NOT EXISTS status ENUM('pending','approved') NOT NULL DEFAULT 'approved' AFTER link_url;
ALTER TABLE advertisements ADD COLUMN IF NOT EXISTS contact_name VARCHAR(150) NULL AFTER status;
ALTER TABLE advertisements ADD COLUMN IF NOT EXISTS contact_phone VARCHAR(30) NULL AFTER contact_name;

-- downloads
CREATE TABLE IF NOT EXISTS download_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS download_files (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  file_size INT UNSIGNED NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_download_files_category FOREIGN KEY (category_id) REFERENCES download_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- staff_event_access
CREATE TABLE IF NOT EXISTS staff_event_access (
  admin_id INT UNSIGNED NOT NULL,
  event_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (admin_id, event_id),
  CONSTRAINT fk_staff_event_access_admin FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
  CONSTRAINT fk_staff_event_access_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- stripe_refunds
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS refunded_at DATETIME NULL AFTER cancelled_by;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS stripe_refund_id VARCHAR(255) NULL AFTER refunded_at;

-- stripe_suspend
ALTER TABLE settings ADD COLUMN IF NOT EXISTS stripe_suspended TINYINT(1) NOT NULL DEFAULT 0 AFTER stripe_webhook_secret;

-- vendor_event_reminders
ALTER TABLE events ADD COLUMN IF NOT EXISTS vendor_reminder_sent_at DATETIME NULL AFTER open_notified_at;
ALTER TABLE settings ADD COLUMN IF NOT EXISTS vendor_reminder_days_before SMALLINT UNSIGNED NOT NULL DEFAULT 3 AFTER booking_rate_limit_per_hour;

-- vendor_reservations (must run before vendor_crm — it adds reserved_vendor_email,
-- which vendor_crm's reserved_vendor_id column is positioned AFTER)
ALTER TABLE lots MODIFY COLUMN status ENUM('available','pending_payment','booked','disabled','reserved') NOT NULL DEFAULT 'available';
ALTER TABLE lots ADD COLUMN IF NOT EXISTS reserved_vendor_name VARCHAR(150) NULL AFTER status;
ALTER TABLE lots ADD COLUMN IF NOT EXISTS reserved_vendor_phone VARCHAR(30) NULL AFTER reserved_vendor_name;
ALTER TABLE lots ADD COLUMN IF NOT EXISTS reserved_vendor_email VARCHAR(150) NULL AFTER reserved_vendor_phone;
ALTER TABLE lots ADD COLUMN IF NOT EXISTS reserved_token VARCHAR(64) NULL AFTER reserved_vendor_email;
ALTER TABLE lots ADD COLUMN IF NOT EXISTS reserved_at DATETIME NULL AFTER reserved_token;
ALTER TABLE lots ADD COLUMN IF NOT EXISTS reserved_confirmed_at DATETIME NULL AFTER reserved_at;
ALTER TABLE lots ADD UNIQUE KEY IF NOT EXISTS uq_lots_reserved_token (reserved_token);
ALTER TABLE settings ADD COLUMN IF NOT EXISTS reserved_confirm_deadline_days SMALLINT UNSIGNED NOT NULL DEFAULT 10 AFTER cancellation_cutoff_days;

-- vendor_crm (must run before line_targeting/vendor_portal — they ALTER this
-- vendors table)
CREATE TABLE IF NOT EXISTS vendors (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  email VARCHAR(150) NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_vendors_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE lots ADD COLUMN IF NOT EXISTS reserved_vendor_id INT UNSIGNED NULL AFTER reserved_vendor_email;
ALTER TABLE lots ADD CONSTRAINT fk_lots_reserved_vendor FOREIGN KEY (reserved_vendor_id) REFERENCES vendors(id) ON DELETE SET NULL;

ALTER TABLE bookings ADD COLUMN IF NOT EXISTS vendor_id INT UNSIGNED NULL AFTER booker_email;
ALTER TABLE bookings ADD CONSTRAINT fk_bookings_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL;
ALTER TABLE bookings ADD INDEX IF NOT EXISTS idx_bookings_vendor (vendor_id);

-- line_targeting (must run before vendor_portal — portal_token_hash is
-- positioned AFTER line_user_id)
ALTER TABLE settings ADD COLUMN IF NOT EXISTS line_channel_secret VARCHAR(100) NULL AFTER line_oa_channel_access_token;
ALTER TABLE vendors ADD COLUMN IF NOT EXISTS line_user_id VARCHAR(64) NULL AFTER notes;
ALTER TABLE vendors ADD UNIQUE KEY IF NOT EXISTS uq_vendors_line_user_id (line_user_id);

-- vendor_portal
ALTER TABLE vendors ADD COLUMN IF NOT EXISTS portal_token_hash VARCHAR(64) NULL AFTER line_user_id;
ALTER TABLE vendors ADD COLUMN IF NOT EXISTS portal_token_expires_at DATETIME NULL AFTER portal_token_hash;

-- photo_layout (must run before pin_size, which must run before box_rotation)
ALTER TABLE events ADD COLUMN IF NOT EXISTS layout_mode ENUM('grid','photo') NOT NULL DEFAULT 'grid' AFTER floorplan_image;
ALTER TABLE lots ADD COLUMN IF NOT EXISTS map_x DECIMAL(5,2) NULL AFTER grid_col;
ALTER TABLE lots ADD COLUMN IF NOT EXISTS map_y DECIMAL(5,2) NULL AFTER grid_col;

-- photo_layout_pin_size
ALTER TABLE lots ADD COLUMN IF NOT EXISTS map_size ENUM('small','medium','large') NOT NULL DEFAULT 'medium' AFTER map_y;

-- photo_layout_box_rotation
ALTER TABLE lots ADD COLUMN IF NOT EXISTS map_shape ENUM('pin','box') NOT NULL DEFAULT 'pin' AFTER map_size;
ALTER TABLE lots ADD COLUMN IF NOT EXISTS map_rotation DECIMAL(5,1) NOT NULL DEFAULT 0 AFTER map_shape;
