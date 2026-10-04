ALTER TABLE advertisements ADD COLUMN IF NOT EXISTS thumb_path VARCHAR(255) NULL AFTER image_path;

CREATE TABLE IF NOT EXISTS advertisement_images (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  advertisement_id INT UNSIGNED NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  thumb_path VARCHAR(255) NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_advertisement_images_ad (advertisement_id),
  CONSTRAINT fk_advertisement_images_ad FOREIGN KEY (advertisement_id) REFERENCES advertisements(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
