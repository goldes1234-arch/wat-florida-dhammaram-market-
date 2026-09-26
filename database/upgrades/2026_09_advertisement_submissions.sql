-- Adds public self-service shop-ad submissions with an admin approval step.
-- Additive only, safe on a live database. Existing rows have no status yet —
-- backfill them to 'approved' so ads already live stay live.
-- Run: mysql -u root temple_market < database/upgrades/2026_09_advertisement_submissions.sql

ALTER TABLE advertisements ADD COLUMN description TEXT NULL AFTER business_name;
ALTER TABLE advertisements ADD COLUMN status ENUM('pending','approved') NOT NULL DEFAULT 'approved' AFTER link_url;
ALTER TABLE advertisements ADD COLUMN contact_name VARCHAR(150) NULL AFTER status;
ALTER TABLE advertisements ADD COLUMN contact_phone VARCHAR(30) NULL AFTER contact_name;
