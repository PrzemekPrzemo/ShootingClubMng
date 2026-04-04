-- Migration v8: Member Self-Service Portal
-- Run this via phpMyAdmin or CLI before deploying portal code.

-- 1. Add member authentication columns
ALTER TABLE `members`
  ADD COLUMN IF NOT EXISTS `password_hash`        VARCHAR(255) NULL     AFTER `notes`,
  ADD COLUMN IF NOT EXISTS `must_change_password`  TINYINT(1)  NOT NULL DEFAULT 1 AFTER `password_hash`;

-- 2. Allow NULL for registered_by on competition_entries (portal self-registration)
ALTER TABLE `competition_entries`
  MODIFY COLUMN `registered_by` INT UNSIGNED NULL;

-- 3. Allow NULL for created_by on member_medical_exams (portal uploads)
ALTER TABLE `member_medical_exams`
  MODIFY COLUMN `created_by` INT UNSIGNED NULL;

-- 4. Add start_fee_paid flag to competition_entries
ALTER TABLE `competition_entries`
  ADD COLUMN IF NOT EXISTS `start_fee_paid` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`;

-- 5. In-app notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `type`              VARCHAR(40)   NOT NULL,
  `title`             VARCHAR(200)  NOT NULL,
  `message`           TEXT          NULL,
  `for_roles`         VARCHAR(100)  NOT NULL DEFAULT 'admin,zarzad',
  `related_member_id` INT UNSIGNED  NULL,
  `data`              JSON          NULL,
  `is_read`           TINYINT(1)    NOT NULL DEFAULT 0,
  `created_at`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`related_member_id`) REFERENCES `members`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Competition entry events (which events a member registers for)
CREATE TABLE IF NOT EXISTS `competition_entry_events` (
  `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `competition_entry_id`  INT UNSIGNED NOT NULL,
  `competition_event_id`  INT UNSIGNED NOT NULL,
  UNIQUE KEY `uq_entry_event` (`competition_entry_id`, `competition_event_id`),
  FOREIGN KEY (`competition_entry_id`) REFERENCES `competition_entries`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`competition_event_id`) REFERENCES `competition_events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
