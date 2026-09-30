ALTER TABLE lots MODIFY COLUMN status ENUM('available','pending_payment','booked','disabled','reserved') NOT NULL DEFAULT 'available';
ALTER TABLE lots ADD COLUMN reserved_vendor_name VARCHAR(150) NULL AFTER status;
ALTER TABLE lots ADD COLUMN reserved_vendor_phone VARCHAR(30) NULL AFTER reserved_vendor_name;
ALTER TABLE lots ADD COLUMN reserved_vendor_email VARCHAR(150) NULL AFTER reserved_vendor_phone;
ALTER TABLE lots ADD COLUMN reserved_token VARCHAR(64) NULL AFTER reserved_vendor_email;
ALTER TABLE lots ADD COLUMN reserved_at DATETIME NULL AFTER reserved_token;
ALTER TABLE lots ADD COLUMN reserved_confirmed_at DATETIME NULL AFTER reserved_at;
ALTER TABLE lots ADD UNIQUE KEY uq_lots_reserved_token (reserved_token);

ALTER TABLE settings ADD COLUMN reserved_confirm_deadline_days SMALLINT UNSIGNED NOT NULL DEFAULT 10 AFTER cancellation_cutoff_days;
