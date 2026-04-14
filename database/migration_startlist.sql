-- ============================================================
-- Start List Generator — schema migration
-- Run on top of the existing multi-club schema.
-- All tables use the sl_ prefix to avoid collisions.
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ----------------------------------------------------------
-- 1. sl_generators
--    One generator = one scheduling run (may optionally link
--    to an existing competition for reference).
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sl_generators` (
  `id`             INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
  `club_id`        INT UNSIGNED     NOT NULL,
  `name`           VARCHAR(150)     NOT NULL,
  `competition_id` INT UNSIGNED     NULL
                     COMMENT 'Optional link to competitions.id',
  `start_date`     DATE             NOT NULL,
  `start_time`     TIME             NOT NULL DEFAULT '09:00:00',
  `break_minutes`  TINYINT UNSIGNED NOT NULL DEFAULT 10
                     COMMENT 'Global gap between consecutive relays (minutes)',
  `status`         ENUM('draft','generated','published') NOT NULL DEFAULT 'draft',
  `created_by`     INT UNSIGNED     NOT NULL,
  `created_at`     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
                     ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_slg_club` (`club_id`),
  FOREIGN KEY (`club_id`)        REFERENCES `clubs`(`id`)        ON DELETE CASCADE,
  FOREIGN KEY (`competition_id`) REFERENCES `competitions`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`)     REFERENCES `users`(`id`)        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. sl_disciplines
--    Disciplines configured per generator run.
--    Fully independent from the global `disciplines` table.
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sl_disciplines` (
  `id`               INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
  `generator_id`     INT UNSIGNED     NOT NULL,
  `name`             VARCHAR(100)     NOT NULL,
  `code`             VARCHAR(20)      NOT NULL
                       COMMENT 'Short code used in CSV, e.g. ppn / pst / psp / kpn',
  `duration_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 30
                       COMMENT 'Duration of one relay including command stop (minutes)',
  `lanes_count`      TINYINT UNSIGNED NOT NULL DEFAULT 4
                       COMMENT 'Number of competitors per relay slot',
  `gender_mode`      ENUM('open','separate') NOT NULL DEFAULT 'open'
                       COMMENT 'open = mixed relay, separate = M and K split',
  `sort_order`       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY `uq_sld_code` (`generator_id`, `code`),
  FOREIGN KEY (`generator_id`) REFERENCES `sl_generators`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. sl_combos
--    A combo = disciplines that share a timeslot (run in
--    parallel on different lanes).
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sl_combos` (
  `id`             INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
  `generator_id`   INT UNSIGNED     NOT NULL,
  `name`           VARCHAR(100)     NOT NULL
                     COMMENT 'E.g. "ppn + kpn razem"',
  `max_per_relay`  TINYINT UNSIGNED NOT NULL DEFAULT 8
                     COMMENT 'Total combined competitor cap for the shared timeslot',
  FOREIGN KEY (`generator_id`) REFERENCES `sl_generators`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 4. sl_combo_items
--    Which disciplines belong to which combo.
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sl_combo_items` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `combo_id`      INT UNSIGNED NOT NULL,
  `discipline_id` INT UNSIGNED NOT NULL,
  UNIQUE KEY `uq_slci` (`combo_id`, `discipline_id`),
  FOREIGN KEY (`combo_id`)      REFERENCES `sl_combos`(`id`)      ON DELETE CASCADE,
  FOREIGN KEY (`discipline_id`) REFERENCES `sl_disciplines`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 5. sl_age_categories
--    Age categories scoped to a generator run.
--    Separate from global member_age_categories.
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sl_age_categories` (
  `id`           INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
  `generator_id` INT UNSIGNED     NOT NULL,
  `name`         VARCHAR(60)      NOT NULL,
  `age_from`     TINYINT UNSIGNED NOT NULL,
  `age_to`       TINYINT UNSIGNED NOT NULL,
  `sort_order`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  FOREIGN KEY (`generator_id`) REFERENCES `sl_generators`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 6. sl_competitors
--    Imported competitors (not necessarily club members).
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sl_competitors` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `generator_id`    INT UNSIGNED NOT NULL,
  `first_name`      VARCHAR(60)  NOT NULL,
  `last_name`       VARCHAR(60)  NOT NULL,
  `birth_date`      DATE         NULL,
  `gender`          ENUM('M','K') NULL,
  `age_category_id` INT UNSIGNED NULL
                      COMMENT 'Resolved sl_age_categories.id',
  INDEX `idx_slco_gen` (`generator_id`),
  FOREIGN KEY (`generator_id`)    REFERENCES `sl_generators`(`id`)    ON DELETE CASCADE,
  FOREIGN KEY (`age_category_id`) REFERENCES `sl_age_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 7. sl_competitor_disciplines
--    Which disciplines a competitor participates in.
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sl_competitor_disciplines` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `competitor_id` INT UNSIGNED NOT NULL,
  `discipline_id` INT UNSIGNED NOT NULL,
  UNIQUE KEY `uq_slcd` (`competitor_id`, `discipline_id`),
  FOREIGN KEY (`competitor_id`) REFERENCES `sl_competitors`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`discipline_id`) REFERENCES `sl_disciplines`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 8. sl_relays
--    Generated relay timeslots.
--    combo_id set when this slot was produced by a combo.
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sl_relays` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `generator_id`   INT UNSIGNED NOT NULL,
  `discipline_id`  INT UNSIGNED NOT NULL
                     COMMENT 'Primary discipline (first in combo for combo slots)',
  `combo_id`       INT UNSIGNED NULL,
  `slot_index`     SMALLINT UNSIGNED NOT NULL
                     COMMENT '1-based sequential slot number within this discipline',
  `start_datetime` DATETIME     NOT NULL,
  `end_datetime`   DATETIME     NOT NULL,
  INDEX `idx_slr_gen` (`generator_id`),
  FOREIGN KEY (`generator_id`)  REFERENCES `sl_generators`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`discipline_id`) REFERENCES `sl_disciplines`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`combo_id`)      REFERENCES `sl_combos`(`id`)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 9. sl_relay_entries
--    Assigns a competitor to a relay with their actual discipline.
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sl_relay_entries` (
  `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `relay_id`             INT UNSIGNED NOT NULL,
  `competitor_id`        INT UNSIGNED NOT NULL,
  `actual_discipline_id` INT UNSIGNED NOT NULL
                           COMMENT 'Real discipline for this entry (matters in combos)',
  `lane`                 TINYINT UNSIGNED NULL,
  UNIQUE KEY `uq_slre` (`relay_id`, `competitor_id`),
  FOREIGN KEY (`relay_id`)             REFERENCES `sl_relays`(`id`)      ON DELETE CASCADE,
  FOREIGN KEY (`competitor_id`)        REFERENCES `sl_competitors`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`actual_discipline_id`) REFERENCES `sl_disciplines`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
