ALTER TABLE bookings ADD COLUMN refunded_at DATETIME NULL AFTER cancelled_by;
ALTER TABLE bookings ADD COLUMN stripe_refund_id VARCHAR(255) NULL AFTER refunded_at;
