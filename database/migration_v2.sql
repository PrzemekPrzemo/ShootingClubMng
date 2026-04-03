-- ============================================================
-- Migration v2 — Słowniki, Klasy zawodników, Konkurencje
-- Uruchomić raz na serwerze: mysql ... < migration_v2.sql
-- lub wkleić do phpMyAdmin
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ------------------------------------------------------------
-- 1. Słownik dodatkowych klas zawodników (admin-konfigurowalny)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `member_classes` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(60)  NOT NULL,
  `short_code` VARCHAR(20)  NOT NULL UNIQUE,
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `member_classes` (`name`, `short_code`, `sort_order`) VALUES
  ('Junior PZSS',    'JUN',  1),
  ('Senior PZSS',    'SEN',  2),
  ('Weteran',        'WET',  3),
  ('Zawodnik Open',  'OPEN', 4);

-- ------------------------------------------------------------
-- 2. Dodatkowa klasa do profilu zawodnika
-- ------------------------------------------------------------
ALTER TABLE `members`
  ADD COLUMN IF NOT EXISTS `member_class_id` INT UNSIGNED NULL AFTER `age_category_id`;

-- Dodaj FK tylko jeśli nie istnieje
SET @fk_exists = (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'members'
    AND CONSTRAINT_NAME = 'members_member_class_id_fk'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql = IF(@fk_exists = 0,
  'ALTER TABLE `members` ADD CONSTRAINT `members_member_class_id_fk` FOREIGN KEY (`member_class_id`) REFERENCES `member_classes`(`id`) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 3. Konkurencje wewnątrz zawodów
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `competition_events` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `competition_id`  INT UNSIGNED NOT NULL,
  `name`            VARCHAR(120) NOT NULL COMMENT 'np. 10m Pistolet Pneumatyczny',
  `shots_count`     TINYINT UNSIGNED NULL COMMENT 'Liczba strzałów, np. 60',
  `scoring_type`    ENUM('decimal','integer','hit_miss') NOT NULL DEFAULT 'decimal',
  `sort_order`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  FOREIGN KEY (`competition_id`) REFERENCES `competitions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. Wyniki per zawodnik per konkurencja
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `competition_event_results` (
  `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `competition_event_id` INT UNSIGNED   NOT NULL,
  `member_id`            INT UNSIGNED   NOT NULL,
  `score`                DECIMAL(8,2)   NULL,
  `score_inner`          TINYINT UNSIGNED NULL COMMENT 'Wewnętrzne 10 / X-count',
  `place`                SMALLINT UNSIGNED NULL,
  `notes`                TEXT           NULL,
  `entered_by`           INT UNSIGNED   NOT NULL,
  `created_at`           DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_event_result` (`competition_event_id`, `member_id`),
  FOREIGN KEY (`competition_event_id`) REFERENCES `competition_events`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)            REFERENCES `members`(`id`)            ON DELETE CASCADE,
  FOREIGN KEY (`entered_by`)           REFERENCES `users`(`id`)              ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;

-- ============================================================
-- Gotowe. Uruchom teraz aplikację i sprawdź /config/disciplines
-- oraz /config/member-classes
-- ============================================================
