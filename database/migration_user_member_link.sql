-- Migration: explicit user → member link
-- Run once in phpMyAdmin

ALTER TABLE `users`
  ADD COLUMN `member_id` INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'Optional explicit link to a members record'
    AFTER `is_super_admin`,
  ADD CONSTRAINT `fk_users_member_id`
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`)
    ON DELETE SET NULL ON UPDATE CASCADE;

CREATE INDEX `idx_users_member_id` ON `users` (`member_id`);
