-- Migration v7: License types dictionary
-- Replaces hardcoded ENUM('zawodnicza','trenerska','patent') with a table

CREATE TABLE IF NOT EXISTS `license_types` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`             VARCHAR(80)  NOT NULL,
  `short_code`       VARCHAR(30)  NOT NULL,
  `description`      VARCHAR(200) NULL,
  `validity_months`  TINYINT UNSIGNED NULL COMMENT 'Domyślna ważność w miesiącach, NULL = brak ograniczeń',
  `is_active`        TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_short_code` (`short_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed the 3 existing types
INSERT IGNORE INTO `license_types` (`name`, `short_code`, `description`, `validity_months`, `sort_order`) VALUES
  ('Zawodnicza',  'zawodnicza', 'Licencja sportowa zawodnicza PZSS', 12, 1),
  ('Trenerska',   'trenerska',  'Licencja trenerska PZSS',           12, 2),
  ('Patent',      'patent',     'Patent strzelecki',                  NULL, 3);

-- Add license_type_id FK to licenses
ALTER TABLE `licenses`
  ADD COLUMN `license_type_id` INT UNSIGNED NULL AFTER `license_type`;

ALTER TABLE `licenses`
  ADD CONSTRAINT `fk_licenses_license_type`
  FOREIGN KEY (`license_type_id`) REFERENCES `license_types`(`id`);

-- Populate license_type_id from existing license_type values
UPDATE `licenses` l
  JOIN `license_types` lt ON lt.short_code = l.license_type
  SET l.license_type_id = lt.id;
