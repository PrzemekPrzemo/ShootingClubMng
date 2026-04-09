-- Migration v28: Demo environment system
-- Adds is_demo flag + expiry to clubs, is_demo flag to users

ALTER TABLE `clubs`
  ADD COLUMN `is_demo`         TINYINT(1)   NOT NULL DEFAULT 0    AFTER `is_active`,
  ADD COLUMN `demo_expires_at` DATETIME     NULL                  AFTER `is_demo`,
  ADD COLUMN `demo_token`      VARCHAR(64)  NULL                  AFTER `demo_expires_at`,
  ADD UNIQUE  KEY `uq_demo_token` (`demo_token`);

ALTER TABLE `users`
  ADD COLUMN `is_demo` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_super_admin`;
