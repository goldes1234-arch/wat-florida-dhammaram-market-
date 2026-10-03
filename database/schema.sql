-- ระบบจองพื้นที่ขายของ — schema
-- Run: mysql -u root temple_market < database/schema.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS booking_status_logs;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS lots;
DROP TABLE IF EXISTS interest_subscribers;
DROP TABLE IF EXISTS event_photos;
DROP TABLE IF EXISTS waitlist_entries;
DROP TABLE IF EXISTS event_contacts;
DROP TABLE IF EXISTS zones;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS admin_activity_logs;
DROP TABLE IF EXISTS admin_users;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS booking_rate_limits;
DROP TABLE IF EXISTS gallery_photos;
DROP TABLE IF EXISTS advertisements;
DROP TABLE IF EXISTS social_links;
DROP TABLE IF EXISTS settings;

SET FOREIGN_KEY_CHECKS = 1;

-- Single-row system configuration.
CREATE TABLE settings (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
  org_name VARCHAR(150) NOT NULL DEFAULT '',
  org_address VARCHAR(255) NULL,
  org_phone VARCHAR(30) NULL,
  org_email VARCHAR(150) NULL,
  logo_path VARCHAR(255) NULL,
  hero_banner_image VARCHAR(255) NULL,
  facebook_url VARCHAR(255) NULL,
  line_oa_id VARCHAR(100) NULL,
  google_maps_url VARCHAR(255) NULL,
  website_url VARCHAR(255) NULL,
  youtube_url VARCHAR(255) NULL,
  bank_name VARCHAR(100) NULL,
  bank_account_name VARCHAR(150) NULL,
  bank_account_number VARCHAR(50) NULL,
  promptpay_id VARCHAR(50) NULL,
  currency_code CHAR(3) NOT NULL DEFAULT 'THB',
  default_locale ENUM('th','en') NOT NULL DEFAULT 'th',
  cancellation_cutoff_days SMALLINT UNSIGNED NOT NULL DEFAULT 10,
  -- Stripe refunds are only allowed this many days (or more) before the event starts, measured
  -- from the moment an admin presses refund — see BookingService::isRefundWindowOpen().
  refund_cutoff_days SMALLINT UNSIGNED NOT NULL DEFAULT 10,
  reserved_confirm_deadline_days SMALLINT UNSIGNED NOT NULL DEFAULT 10,
  booking_rate_limit_per_hour SMALLINT UNSIGNED NOT NULL DEFAULT 5,
  vendor_reminder_days_before SMALLINT UNSIGNED NOT NULL DEFAULT 3,
  stripe_publishable_key VARCHAR(255) NULL,
  stripe_secret_key VARCHAR(255) NULL,
  stripe_webhook_secret VARCHAR(255) NULL,
  stripe_suspended TINYINT(1) NOT NULL DEFAULT 0,
  stripe_pass_fee_to_customer TINYINT(1) NOT NULL DEFAULT 0,
  stripe_fee_percent DECIMAL(5,2) NOT NULL DEFAULT 2.90,
  stripe_fee_fixed DECIMAL(10,2) NOT NULL DEFAULT 0.30,
  line_oa_channel_access_token VARCHAR(255) NULL,
  -- Channel Secret from the SAME Messaging API channel as the access token above —
  -- used to verify that /line/webhook requests genuinely came from LINE (HMAC-SHA256
  -- over the raw request body) before trusting them, not a separate LINE Login channel.
  line_channel_secret VARCHAR(100) NULL,
  smtp_host VARCHAR(150) NULL,
  smtp_port SMALLINT UNSIGNED NULL,
  smtp_encryption ENUM('tls','ssl','none') NOT NULL DEFAULT 'tls',
  smtp_username VARCHAR(150) NULL,
  smtp_password VARCHAR(255) NULL,
  smtp_from_email VARCHAR(150) NULL,
  smtp_from_name VARCHAR(150) NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_settings_singleton CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Free-form extra social/contact links beyond the fixed fields on settings (e.g. TikTok, Instagram).
CREATE TABLE social_links (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  label VARCHAR(50) NOT NULL,
  url VARCHAR(255) NOT NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Standalone photos for the public home page's "our event atmosphere" gallery,
-- shown alongside (not instead of) each published event's own banner image.
CREATE TABLE gallery_photos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  image_path VARCHAR(255) NOT NULL,
  -- ~600px preview used by the mosaic/grid so they stay light; NULL for older photos (the full image is used).
  thumb_path VARCHAR(255) NULL,
  caption VARCHAR(150) NULL,
  -- Optional album: the event these photos were taken at (drives the filter chips on /gallery).
  event_id INT UNSIGNED NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Site-wide vendor/business promotional listings ("ร้านค้าแนะนำ"), shown on the home
-- page and every event detail page. Admin-added rows are 'approved' immediately;
-- rows submitted by the public via the "list your shop" form start 'pending' and
-- only appear on the public site once an admin approves them.
CREATE TABLE advertisements (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  business_name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  image_path VARCHAR(255) NOT NULL,
  link_url VARCHAR(255) NULL,
  status ENUM('pending','approved') NOT NULL DEFAULT 'approved',
  contact_name VARCHAR(150) NULL,
  contact_phone VARCHAR(30) NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Downloadable resources ("ดาวน์โหลด" nav dropdown) — admin creates categories
-- freely (no fixed list) and uploads files under each; a category only appears in
-- the public nav once it has at least one file, same "don't show it empty" rule
-- used for advertisements/gallery.
CREATE TABLE download_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE download_files (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  file_size INT UNSIGNED NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_download_files_category FOREIGN KEY (category_id) REFERENCES download_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Simple insert-per-attempt anti-spam log for guest-facing forms.
CREATE TABLE booking_rate_limits (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(45) NOT NULL,
  action VARCHAR(30) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_rate_limit_lookup (ip_address, action, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Public "contact us" inquiries (email / phone / LINE), reviewable by staff in the admin panel.
CREATE TABLE contact_messages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NULL,
  phone VARCHAR(30) NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_contact_has_reach CHECK (email IS NOT NULL OR phone IS NOT NULL),
  INDEX idx_contact_messages_read (is_read, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin/staff back-office accounts.
CREATE TABLE admin_users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(30) NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('super_admin','staff','checkin','finance') NOT NULL DEFAULT 'staff',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  reset_token_hash VARCHAR(64) NULL,
  reset_token_expires_at DATETIME NULL,
  -- Self-service two-factor auth (see App\Core\Totp, admin/security) — totp_secret is
  -- written during enrollment before totp_enabled flips on, so an abandoned enrollment
  -- never actually gates login. totp_backup_codes is a JSON array of hashed one-time
  -- codes, each removed from the array the moment it's used.
  totp_secret VARCHAR(32) NULL,
  totp_enabled TINYINT(1) NOT NULL DEFAULT 0,
  totp_backup_codes TEXT NULL,
  -- Long-lived no-password login link for front-of-house "checkin" accounts (see
  -- Public\CheckinLinkController) — overwritten (implicitly revoking the old link)
  -- each time a super_admin regenerates it from the staff page. No expiry by design:
  -- it's meant to be bookmarked/scanned repeatedly across many events.
  checkin_link_token_hash VARCHAR(64) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit trail for consequential admin actions (deletions, account changes) —
-- separate from booking_status_logs, which only covers booking transitions.
CREATE TABLE admin_activity_logs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  admin_id INT UNSIGNED NULL,
  admin_name VARCHAR(100) NULL,
  action VARCHAR(50) NOT NULL,
  subject_type VARCHAR(50) NOT NULL,
  subject_id INT UNSIGNED NULL,
  description VARCHAR(500) NOT NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_activity_log_admin FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
  INDEX idx_activity_log_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events / fairs that lots are booked under.
CREATE TABLE events (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(160) NOT NULL UNIQUE,
  name_th VARCHAR(200) NOT NULL,
  name_en VARCHAR(200) NULL,
  description_th TEXT NULL,
  description_en TEXT NULL,
  venue_name VARCHAR(200) NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  booking_open_at DATETIME NOT NULL,
  booking_close_at DATETIME NOT NULL,
  banner_image VARCHAR(255) NULL,
  floorplan_image VARCHAR(255) NULL,
  layout_mode ENUM('grid','photo') NOT NULL DEFAULT 'grid',
  is_published TINYINT(1) NOT NULL DEFAULT 0,
  open_notified_at DATETIME NULL,
  -- Set once EventReminderService has sent the "event is coming up" reminder to
  -- this event's confirmed bookers/vendors, so the daily cron run never sends it
  -- twice (see settings.vendor_reminder_days_before).
  vendor_reminder_sent_at DATETIME NULL,
  deleted_at DATETIME NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_events_admin FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL,
  CONSTRAINT chk_events_dates CHECK (end_date >= start_date),
  CONSTRAINT chk_events_booking_window CHECK (booking_close_at >= booking_open_at),
  INDEX idx_events_published_window (is_published, booking_open_at, booking_close_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional per-staff event restriction (see App\Core\EventAccess) — a 'staff'
-- admin with zero rows here can see/manage every event, same as before this
-- table existed; assigning at least one row switches that account to an
-- allow-list of only those events. super_admin and checkin ignore this table
-- entirely (see EventAccess::allowed()).
CREATE TABLE staff_event_access (
  admin_id INT UNSIGNED NOT NULL,
  event_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (admin_id, event_id),
  CONSTRAINT fk_staff_event_access_admin FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
  CONSTRAINT fk_staff_event_access_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional per-event pricing zones (a lot may or may not belong to one).
CREATE TABLE zones (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  default_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_zones_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  UNIQUE KEY uq_zone_name_per_event (event_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Up to a handful of named contact people per event (organizer/coordinator), shown
-- publicly so vendors know who to reach with questions about that specific event.
CREATE TABLE event_contacts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  contact_channel VARCHAR(150) NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_event_contacts_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  INDEX idx_event_contacts_event (event_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- "Notify me if a lot opens up" signups for events that are fully booked out.
CREATE TABLE waitlist_entries (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  email VARCHAR(150) NULL,
  notified_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_waitlist_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  INDEX idx_waitlist_event (event_id, notified_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Extra promotional photos attached to a single event (up to 6, managed from the event edit page).
CREATE TABLE event_photos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_event_photos_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  INDEX idx_event_photos_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- "Notify me" waiting list for not-yet-open events.
CREATE TABLE interest_subscribers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  email VARCHAR(150) NULL,
  phone VARCHAR(30) NULL,
  notified_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_subscribers_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  CONSTRAINT chk_subscriber_contact CHECK (email IS NOT NULL OR phone IS NOT NULL),
  INDEX idx_subscribers_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A regular/recurring vendor's persistent identity, independent of any one lot
-- reservation or booking — built up automatically as admins reserve lots for
-- vendors (see lots.reserved_vendor_id, bookings.vendor_id) so a vendor's
-- history accumulates across events without any extra admin data entry.
CREATE TABLE vendors (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  email VARCHAR(150) NULL,
  notes TEXT NULL,
  -- Default 'what I sell' for this regular vendor, copied onto each confirmed reservation.
  items_for_sale VARCHAR(200) NULL,
  -- Set when the vendor asks (via their self-service portal) for their data to be
  -- deleted — surfaced to admins to action manually (see VendorController::destroy()),
  -- not auto-deleted, since bookings/payment history may need to be kept regardless.
  deletion_requested_at DATETIME NULL,
  -- Set once the vendor links their LINE account by messaging the temple's LINE
  -- Official Account with their phone number (see LineWebhookController) — lets
  -- admins push a targeted message to this one vendor instead of only broadcasting
  -- to every OA follower.
  line_user_id VARCHAR(64) NULL,
  -- A LINE account that messaged the OA with this vendor's phone number but isn't
  -- linked yet: a phone number alone proves nothing, so an admin must approve the
  -- request (see VendorController::approveLineLink) before it becomes line_user_id.
  line_pending_user_id VARCHAR(64) NULL,
  line_link_requested_at DATETIME NULL,
  -- Self-service portal access (see VendorPortalController) — a short-lived,
  -- hashed magic-link token a linked vendor gets by messaging the LINE OA with a
  -- keyword (see LineWebhookController), mirroring how admin password resets work.
  portal_token_hash VARCHAR(64) NULL,
  portal_token_expires_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_vendors_phone (phone),
  UNIQUE KEY uq_vendors_line_user_id (line_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Individual sellable stalls within an event.
CREATE TABLE lots (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  zone_id INT UNSIGNED NULL,
  code VARCHAR(20) NOT NULL,
  grid_row SMALLINT UNSIGNED NULL,
  grid_col SMALLINT UNSIGNED NULL,
  map_x DECIMAL(5,2) NULL,
  map_y DECIMAL(5,2) NULL,
  map_size ENUM('small','medium','large') NOT NULL DEFAULT 'medium',
  map_shape ENUM('pin','box') NOT NULL DEFAULT 'pin',
  map_rotation DECIMAL(5,1) NOT NULL DEFAULT 0,
  photo VARCHAR(255) NULL,
  price DECIMAL(10,2) NOT NULL,
  status ENUM('available','pending_payment','booked','disabled','reserved') NOT NULL DEFAULT 'available',
  -- A lot held for a regular/recurring vendor ahead of the normal public booking
  -- flow. Set together when status becomes 'reserved'; reserved_confirmed_at is
  -- filled in only once the vendor actually confirms (see ReservationService) and
  -- is kept even after the lot becomes a real booking, as a record that this one
  -- started life as a vendor reservation. An unconfirmed reservation is released
  -- back to 'available' automatically once the event is within
  -- settings.reserved_confirm_deadline_days.
  reserved_vendor_name VARCHAR(150) NULL,
  reserved_vendor_phone VARCHAR(30) NULL,
  reserved_vendor_email VARCHAR(150) NULL,
  -- Links to the persistent vendor record this reservation is/was for — cleared
  -- along with the rest of the reserved_* fields on cancel/expiry (a reservation
  -- that never became a real booking isn't meaningful vendor history), but carried
  -- into bookings.vendor_id once confirmed, which is where that history actually lives.
  reserved_vendor_id INT UNSIGNED NULL,
  reserved_token VARCHAR(64) NULL,
  reserved_at DATETIME NULL,
  reserved_confirmed_at DATETIME NULL,
  deleted_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_lots_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  CONSTRAINT fk_lots_zone FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE SET NULL,
  CONSTRAINT fk_lots_reserved_vendor FOREIGN KEY (reserved_vendor_id) REFERENCES vendors(id) ON DELETE SET NULL,
  UNIQUE KEY uq_lot_code_per_event (event_id, code),
  UNIQUE KEY uq_lots_reserved_token (reserved_token),
  INDEX idx_lots_event_status (event_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Guest bookings against a lot.
CREATE TABLE bookings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  booking_code VARCHAR(20) NOT NULL UNIQUE,
  event_id INT UNSIGNED NOT NULL,
  lot_id INT UNSIGNED NOT NULL,
  booker_name VARCHAR(150) NOT NULL,
  booker_phone VARCHAR(30) NOT NULL,
  booker_email VARCHAR(150) NULL,
  -- Only set when this booking originated from a confirmed vendor reservation
  -- (see ReservationService::confirm) — NULL for an ordinary public booking. This
  -- is the permanent link a vendor's booking history is built from.
  vendor_id INT UNSIGNED NULL,
  shop_photo VARCHAR(255) NULL,
  -- What the stall sells (free text, shown to visitors on the event page). Required for
  -- public bookings; for regular vendors it is copied from vendors.items_for_sale.
  items_for_sale VARCHAR(200) NULL,
  payment_method ENUM('onsite_cash','bank_transfer','stripe') NOT NULL,
  status ENUM('pending_payment','booked','rejected','cancelled') NOT NULL DEFAULT 'pending_payment',
  price_at_booking DECIMAL(10,2) NOT NULL,
  currency_code CHAR(3) NOT NULL,
  stripe_checkout_session_id VARCHAR(255) NULL,
  stripe_payment_intent_id VARCHAR(255) NULL,
  confirmed_at DATETIME NULL,
  checked_in_at DATETIME NULL,
  checked_in_by INT UNSIGNED NULL,
  cancelled_at DATETIME NULL,
  cancelled_by ENUM('guest','admin') NULL,
  -- Set only by an admin-triggered Stripe refund (see BookingService::refund()) —
  -- never automatic on cancel/reject, since that's an irreversible money movement
  -- an admin should trigger deliberately, not as a side effect of freeing up a lot.
  refunded_at DATETIME NULL,
  stripe_refund_id VARCHAR(255) NULL,
  admin_note VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_bookings_event FOREIGN KEY (event_id) REFERENCES events(id),
  CONSTRAINT fk_bookings_lot FOREIGN KEY (lot_id) REFERENCES lots(id),
  CONSTRAINT fk_bookings_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL,
  INDEX idx_bookings_event_status (event_id, status),
  INDEX idx_bookings_lookup (booker_phone, booker_email),
  INDEX idx_bookings_lot (lot_id),
  INDEX idx_bookings_vendor (vendor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Full audit trail of booking status transitions.
CREATE TABLE booking_status_logs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  booking_id INT UNSIGNED NOT NULL,
  from_status VARCHAR(20) NULL,
  to_status VARCHAR(20) NOT NULL,
  changed_by_type ENUM('guest','admin','system') NOT NULL,
  changed_by_admin_id INT UNSIGNED NULL,
  note VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_logs_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  CONSTRAINT fk_logs_admin FOREIGN KEY (changed_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
  INDEX idx_logs_booking (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tracks which database/upgrades/*.sql files MigrationService has already run,
-- so GET /cron/post-deploy can safely auto-apply new ones on every deploy
-- without ever re-running (and erroring on) one that already went in. This
-- schema.sql already reflects every upgrade file's changes, so on a fresh
-- install MigrationService correctly treats them all as already applied rather
-- than replaying them.
CREATE TABLE schema_migrations (
  filename VARCHAR(255) NOT NULL PRIMARY KEY,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
