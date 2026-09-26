-- Adds a rotatable rectangle marker shape as an alternative to the round pin for the
-- photo-coordinate lot layout mode, so a lot's marker can be tilted to match an angled
-- parking/stall outline in the floorplan photo. Additive only, safe on a live database —
-- existing lots default to 'pin' shape with 0 rotation, rendering exactly as before.
-- Run: mysql -u root temple_market < database/upgrades/2026_09_photo_layout_box_rotation.sql

ALTER TABLE lots ADD COLUMN map_shape ENUM('pin','box') NOT NULL DEFAULT 'pin' AFTER map_size;
ALTER TABLE lots ADD COLUMN map_rotation DECIMAL(5,1) NOT NULL DEFAULT 0 AFTER map_shape;
