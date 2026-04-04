-- Migration v11: Move weapon_type from entry level to per-event level
ALTER TABLE `competition_entry_events`
  ADD COLUMN IF NOT EXISTS `weapon_type` ENUM('własna','klubowa') NOT NULL DEFAULT 'własna';
