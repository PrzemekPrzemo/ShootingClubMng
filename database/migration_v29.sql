-- migration_v29: multi-role support per club
-- User can have multiple roles in one club (e.g. zawodnik + zarzad)
-- Permissions derived from highest role.

-- 1. Extend role ENUM to include 'zawodnik' and 'admin'
ALTER TABLE `user_clubs`
  MODIFY `role` ENUM('admin','zarzad','sędzia','instruktor','zawodnik') NOT NULL DEFAULT 'zawodnik';

-- 2. Drop old UNIQUE(user_id, club_id) — was one role per user per club
ALTER TABLE `user_clubs` DROP INDEX `uq_user_club`;

-- 3. New UNIQUE(user_id, club_id, role) — allows multiple roles, prevents duplicates
ALTER TABLE `user_clubs` ADD UNIQUE KEY `uq_user_club_role` (`user_id`, `club_id`, `role`);
