ALTER TABLE settings ADD COLUMN stripe_suspended TINYINT(1) NOT NULL DEFAULT 0 AFTER stripe_webhook_secret;
