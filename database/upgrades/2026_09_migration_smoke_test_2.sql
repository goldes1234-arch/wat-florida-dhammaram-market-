-- Second throwaway migration-runner smoke test, run after the production schema
-- remediation, to reconfirm the automatic post-deploy migration runner still
-- works end to end. Safe to drop any time.
CREATE TABLE IF NOT EXISTS _migration_smoke_test_2 (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
