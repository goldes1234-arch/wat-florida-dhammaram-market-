ALTER TABLE events ADD COLUMN vendor_reminder_sent_at DATETIME NULL AFTER open_notified_at;
ALTER TABLE settings ADD COLUMN vendor_reminder_days_before SMALLINT UNSIGNED NOT NULL DEFAULT 3 AFTER booking_rate_limit_per_hour;
