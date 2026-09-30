CREATE TABLE vendors (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  email VARCHAR(150) NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_vendors_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE lots ADD COLUMN reserved_vendor_id INT UNSIGNED NULL AFTER reserved_vendor_email;
ALTER TABLE lots ADD CONSTRAINT fk_lots_reserved_vendor FOREIGN KEY (reserved_vendor_id) REFERENCES vendors(id) ON DELETE SET NULL;

ALTER TABLE bookings ADD COLUMN vendor_id INT UNSIGNED NULL AFTER booker_email;
ALTER TABLE bookings ADD CONSTRAINT fk_bookings_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL;
ALTER TABLE bookings ADD INDEX idx_bookings_vendor (vendor_id);
