-- Adds a selectable pin size (small/medium/large) for the photo-coordinate lot layout
-- mode (see database/upgrades/2026_09_photo_layout.sql for the base feature). Additive
-- only and safe to run on a live database — existing lots default to 'medium', which
-- renders at the same fixed size pins already used before this column existed.
-- Run: mysql -u root temple_market < database/upgrades/2026_09_photo_layout_pin_size.sql

ALTER TABLE lots ADD COLUMN map_size ENUM('small','medium','large') NOT NULL DEFAULT 'medium' AFTER map_y;
