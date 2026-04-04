-- Migration v12: Add weapon fee fields to competition event templates
ALTER TABLE `discipline_event_templates`
  ADD COLUMN IF NOT EXISTS `fee_own_weapon`  DECIMAL(8,2) NULL DEFAULT NULL AFTER `max_score`,
  ADD COLUMN IF NOT EXISTS `fee_club_weapon` DECIMAL(8,2) NULL DEFAULT NULL AFTER `fee_own_weapon`;
