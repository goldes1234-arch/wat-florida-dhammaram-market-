-- Audit: checks every column/table added by database/upgrades/*.sql against what
-- actually exists in THIS database. Run in phpMyAdmin's SQL tab and screenshot
-- every result set — MISSING rows are the ones that need fixing.

-- 1) Missing columns
SELECT expected.table_name, expected.column_name,
  CASE WHEN c.column_name IS NULL THEN 'MISSING' ELSE 'ok' END AS status
FROM (
  SELECT 'admin_users' AS table_name, 'totp_secret' AS column_name
  UNION ALL SELECT 'admin_users', 'totp_enabled'
  UNION ALL SELECT 'admin_users', 'totp_backup_codes'
  UNION ALL SELECT 'admin_users', 'checkin_link_token_hash'
  UNION ALL SELECT 'advertisements', 'description'
  UNION ALL SELECT 'advertisements', 'status'
  UNION ALL SELECT 'advertisements', 'contact_name'
  UNION ALL SELECT 'advertisements', 'contact_phone'
  UNION ALL SELECT 'events', 'layout_mode'
  UNION ALL SELECT 'lots', 'map_x'
  UNION ALL SELECT 'lots', 'map_y'
  UNION ALL SELECT 'lots', 'map_shape'
  UNION ALL SELECT 'lots', 'map_rotation'
  UNION ALL SELECT 'lots', 'map_size'
  UNION ALL SELECT 'settings', 'line_channel_secret'
  UNION ALL SELECT 'vendors', 'line_user_id'
  UNION ALL SELECT 'bookings', 'refunded_at'
  UNION ALL SELECT 'bookings', 'stripe_refund_id'
  UNION ALL SELECT 'settings', 'stripe_suspended'
  UNION ALL SELECT 'lots', 'reserved_vendor_id'
  UNION ALL SELECT 'bookings', 'vendor_id'
  UNION ALL SELECT 'events', 'vendor_reminder_sent_at'
  UNION ALL SELECT 'settings', 'vendor_reminder_days_before'
  UNION ALL SELECT 'vendors', 'portal_token_hash'
  UNION ALL SELECT 'vendors', 'portal_token_expires_at'
  UNION ALL SELECT 'lots', 'reserved_vendor_name'
  UNION ALL SELECT 'lots', 'reserved_vendor_phone'
  UNION ALL SELECT 'lots', 'reserved_vendor_email'
  UNION ALL SELECT 'lots', 'reserved_token'
  UNION ALL SELECT 'lots', 'reserved_at'
  UNION ALL SELECT 'lots', 'reserved_confirmed_at'
  UNION ALL SELECT 'settings', 'reserved_confirm_deadline_days'
) AS expected
LEFT JOIN information_schema.columns c
  ON c.table_schema = DATABASE()
  AND c.table_name = expected.table_name
  AND c.column_name = expected.column_name
ORDER BY status DESC, expected.table_name, expected.column_name;

-- 2) Missing tables
SELECT t.table_name AS expected_table,
  CASE WHEN it.table_name IS NULL THEN 'MISSING' ELSE 'ok' END AS status
FROM (
  SELECT 'advertisements' AS table_name
  UNION ALL SELECT 'download_categories'
  UNION ALL SELECT 'download_files'
  UNION ALL SELECT 'staff_event_access'
  UNION ALL SELECT 'vendors'
) t
LEFT JOIN information_schema.tables it
  ON it.table_schema = DATABASE() AND it.table_name = t.table_name
ORDER BY status DESC, t.table_name;

-- 3) Enum columns that were widened (MODIFY, not ADD) — need the actual definition,
-- not just presence, to confirm the new values are there.
SELECT table_name, column_name, column_type
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND ((table_name = 'admin_users' AND column_name = 'role')
    OR (table_name = 'lots' AND column_name = 'status'));
