-- Migration v9: Competition entry fees & discounts
-- Run via phpMyAdmin → SQL before deploying this version.

-- 1. Entry fee per competition (PLN, can be NULL = no fee)
ALTER TABLE `competitions`
  ADD COLUMN IF NOT EXISTS `entry_fee` DECIMAL(8,2) NULL DEFAULT NULL AFTER `max_entries`;

-- 2. Per-entry discount (PLN, NULL = no discount, not shown to member)
ALTER TABLE `competition_entries`
  ADD COLUMN IF NOT EXISTS `discount` DECIMAL(8,2) NULL DEFAULT NULL AFTER `start_fee_paid`;

-- 3. Ensure "Opłata startowa" payment type exists for auto-created payment records
INSERT INTO `payment_types` (`name`, `category`, `description`, `is_active`, `is_per_class`, `sort_order`)
SELECT 'Opłata startowa', 'inne', 'Automatycznie tworzona przy potwierdzeniu opłaty za zawody', 1, 0, 99
WHERE NOT EXISTS (
  SELECT 1 FROM `payment_types` WHERE `name` = 'Opłata startowa'
);
