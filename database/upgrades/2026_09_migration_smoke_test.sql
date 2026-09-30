-- Throwaway table to verify the automatic post-deploy migration runner actually
-- executes new files (not just marks them applied). Safe to drop any time.
CREATE TABLE IF NOT EXISTS _migration_smoke_test (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
