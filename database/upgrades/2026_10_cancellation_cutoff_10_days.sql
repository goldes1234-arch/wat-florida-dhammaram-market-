ALTER TABLE settings MODIFY cancellation_cutoff_days SMALLINT UNSIGNED NOT NULL DEFAULT 10;
UPDATE settings SET cancellation_cutoff_days = 10 WHERE cancellation_cutoff_days = 3;
