ALTER TABLE admin_users MODIFY COLUMN role ENUM('super_admin','staff','checkin','finance') NOT NULL DEFAULT 'staff';
