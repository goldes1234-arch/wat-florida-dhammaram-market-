-- Adds the site-wide vendor/business advertisement ("ร้านค้าแนะนำ") feature — a new
-- table only, no changes to existing tables. Safe to run on a live database.
-- Run: mysql -u root temple_market < database/upgrades/2026_09_advertisements.sql

CREATE TABLE IF NOT EXISTS advertisements (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  business_name VARCHAR(150) NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  link_url VARCHAR(255) NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
