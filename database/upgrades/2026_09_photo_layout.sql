-- Adds the opt-in "photo-coordinate" lot layout mode (see schema.sql for the fresh-install
-- version of these same columns). Run this against a database that was created before
-- this change; it is additive only and safe to run on a live database with existing data.
-- Run: mysql -u root temple_market < database/upgrades/2026_09_photo_layout.sql

ALTER TABLE events ADD COLUMN layout_mode ENUM('grid','photo') NOT NULL DEFAULT 'grid' AFTER floorplan_image;
ALTER TABLE lots ADD COLUMN map_x DECIMAL(5,2) NULL AFTER grid_col;
ALTER TABLE lots ADD COLUMN map_y DECIMAL(5,2) NULL AFTER grid_col;
