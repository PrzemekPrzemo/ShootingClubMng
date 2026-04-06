-- Migration v20: Calendar event categories
-- Replaces hardcoded ENUM type with manageable categories

-- 1. Create categories table
CREATE TABLE IF NOT EXISTS `calendar_event_categories` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `color`      VARCHAR(20)  NOT NULL DEFAULT 'secondary' COMMENT 'Bootstrap color: primary, success, warning, danger, info, secondary, dark',
  `icon`       VARCHAR(50)  NOT NULL DEFAULT 'calendar-event' COMMENT 'Bootstrap Icons name (without bi-)',
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Seed default categories (mapped from old ENUM values)
INSERT IGNORE INTO `calendar_event_categories` (`id`, `name`, `color`, `icon`, `is_active`, `sort_order`) VALUES
  (1, 'Zawody zewnętrzne', 'info',      'trophy',           1, 1),
  (2, 'Zebranie / spotkanie', 'primary', 'people',          1, 2),
  (3, 'Szkolenie / kurs',   'success',  'mortarboard',      1, 3),
  (4, 'Wyjazd',             'warning',  'geo-alt',          1, 4),
  (5, 'Inne',               'secondary','calendar-event',   1, 5);

-- 3. Add category_id column to calendar_events
ALTER TABLE `calendar_events`
  ADD COLUMN IF NOT EXISTS `category_id` INT UNSIGNED NULL
    AFTER `type`,
  ADD CONSTRAINT `fk_cal_event_category`
    FOREIGN KEY (`category_id`) REFERENCES `calendar_event_categories`(`id`)
    ON DELETE SET NULL;

-- 4. Migrate existing data: map old type ENUM → category_id
UPDATE `calendar_events` SET `category_id` = 1 WHERE `type` = 'zawody_zewnetrzne' AND `category_id` IS NULL;
UPDATE `calendar_events` SET `category_id` = 2 WHERE `type` = 'spotkanie'         AND `category_id` IS NULL;
UPDATE `calendar_events` SET `category_id` = 3 WHERE `type` = 'szkolenie'         AND `category_id` IS NULL;
UPDATE `calendar_events` SET `category_id` = 4 WHERE `type` = 'wyjazd'            AND `category_id` IS NULL;
UPDATE `calendar_events` SET `category_id` = 5 WHERE `type` IN ('inne','')        AND `category_id` IS NULL;
