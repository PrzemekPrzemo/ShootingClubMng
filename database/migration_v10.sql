-- Migration v10: Per-event fees (own weapon / club weapon) + weapon type per entry
-- Run via phpMyAdmin → SQL

-- 1. Add price columns to competition_events
ALTER TABLE `competition_events`
  ADD COLUMN IF NOT EXISTS `fee_own_weapon`  DECIMAL(8,2) NULL DEFAULT NULL AFTER `sort_order`,
  ADD COLUMN IF NOT EXISTS `fee_club_weapon` DECIMAL(8,2) NULL DEFAULT NULL AFTER `fee_own_weapon`;

-- 2. Add weapon type to competition_entries
ALTER TABLE `competition_entries`
  ADD COLUMN IF NOT EXISTS `weapon_type` ENUM('własna','klubowa') NOT NULL DEFAULT 'własna' AFTER `discount`;
