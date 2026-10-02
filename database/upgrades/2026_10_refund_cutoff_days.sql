ALTER TABLE settings ADD COLUMN IF NOT EXISTS refund_cutoff_days SMALLINT UNSIGNED NOT NULL DEFAULT 10 AFTER cancellation_cutoff_days;
