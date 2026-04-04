-- ============================================================
-- Migration v4 — Konfigurowalny cennik składek per klasa zawodnika
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ------------------------------------------------------------
-- 1. Rozszerzenie payment_types o kategorię i metadane
-- ------------------------------------------------------------
ALTER TABLE `payment_types`
  ADD COLUMN IF NOT EXISTS `category`     ENUM('skladka','pzss','pomzss','inne') NOT NULL DEFAULT 'inne' AFTER `name`,
  ADD COLUMN IF NOT EXISTS `description`  TEXT NULL AFTER `category`,
  ADD COLUMN IF NOT EXISTS `is_per_class` TINYINT(1) NOT NULL DEFAULT 0 AFTER `description`
    COMMENT 'Czy kwota zależy od klasy zawodnika?',
  ADD COLUMN IF NOT EXISTS `sort_order`   TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `is_per_class`;

-- Ustaw kategorie dla istniejących typów (heurystycznie po nazwie)
UPDATE `payment_types` SET `category` = 'skladka', `sort_order` = 1
  WHERE `name` LIKE '%składka%roczna%' OR `name` LIKE '%membership%';
UPDATE `payment_types` SET `category` = 'pzss', `sort_order` = 10
  WHERE `name` LIKE '%pzss%' OR `name` LIKE '%PZSS%';
UPDATE `payment_types` SET `category` = 'pomzss', `sort_order` = 11
  WHERE `name` LIKE '%pomzss%' OR `name` LIKE '%PomZSS%' OR `name` LIKE '%pomorski%';

-- ------------------------------------------------------------
-- 2. Tabela stawek składek per klasa zawodnika i rok
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fee_rates` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `payment_type_id` INT UNSIGNED    NOT NULL,
  `member_class_id` INT UNSIGNED    NULL COMMENT 'NULL = stawka domyślna (wszyscy bez klasy)',
  `year`            YEAR            NOT NULL,
  `amount`          DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `updated_by`      INT UNSIGNED    NOT NULL,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_fee_rate` (`payment_type_id`, `member_class_id`, `year`),
  FOREIGN KEY (`payment_type_id`) REFERENCES `payment_types`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_class_id`) REFERENCES `member_classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`updated_by`)      REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
