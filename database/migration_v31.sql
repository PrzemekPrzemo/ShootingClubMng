-- migration_v31: Link user accounts to member (competitor) records per club
-- Allows staff users (zarząd, instruktor, sędzia) to switch to the member portal
-- Also adds staff password change support (users.password already exists)

ALTER TABLE `user_clubs`
  ADD COLUMN `linked_member_id` INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'Powiązany zawodnik w tym klubie (portal switching)'
    AFTER `is_active`;

ALTER TABLE `user_clubs`
  ADD INDEX `idx_uc_linked_member` (`linked_member_id`),
  ADD CONSTRAINT `fk_uc_linked_member`
    FOREIGN KEY (`linked_member_id`) REFERENCES `members`(`id`) ON DELETE SET NULL;
