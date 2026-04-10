-- Migration: per-club fee calculator configuration
-- Creates 4 new tables; does NOT alter existing tables.
-- Run once against the application database.

-- 1. Base annual fees per member type per year
CREATE TABLE IF NOT EXISTS `club_fee_config` (
  `id`                 INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `club_id`            INT UNSIGNED     NOT NULL,
  `year`               SMALLINT UNSIGNED NOT NULL,
  `member_type`        VARCHAR(50)      NOT NULL,   -- matches members.member_type string
  `max_annual_fee`     DECIMAL(8,2)     NOT NULL DEFAULT 0.00,
  `early_payment_fee`  DECIMAL(8,2)     NOT NULL DEFAULT 0.00,  -- full year paid by end of February
  `updated_at`         TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cfc` (`club_id`, `year`, `member_type`),
  CONSTRAINT `fk_cfc_club` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Discounts per sports class (member_class_id)
CREATE TABLE IF NOT EXISTS `club_fee_discount_class` (
  `id`               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `club_id`          INT UNSIGNED     NOT NULL,
  `year`             SMALLINT UNSIGNED NOT NULL,
  `member_class_id`  INT UNSIGNED     NOT NULL,
  `discount_amount`  DECIMAL(8,2)     NOT NULL DEFAULT 0.00,
  `updated_at`       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cfdc` (`club_id`, `year`, `member_class_id`),
  CONSTRAINT `fk_cfdc_club`  FOREIGN KEY (`club_id`)         REFERENCES `clubs`          (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cfdc_class` FOREIGN KEY (`member_class_id`) REFERENCES `member_classes`  (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Discounts per achievement type
CREATE TABLE IF NOT EXISTS `club_fee_discount_achieve` (
  `id`               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `club_id`          INT UNSIGNED     NOT NULL,
  `year`             SMALLINT UNSIGNED NOT NULL,
  `achievement_type` VARCHAR(30)      NOT NULL,   -- key from MemberAchievementModel::TYPES
  `discount_amount`  DECIMAL(8,2)     NOT NULL DEFAULT 0.00,
  `updated_at`       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cfda` (`club_id`, `year`, `achievement_type`),
  CONSTRAINT `fk_cfda_club` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Calculated fee assignments per member per year (result of recalculation)
CREATE TABLE IF NOT EXISTS `member_fee_assignments` (
  `id`                INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `member_id`         INT UNSIGNED     NOT NULL,
  `club_id`           INT UNSIGNED     NOT NULL,
  `year`              SMALLINT UNSIGNED NOT NULL,
  `base_annual_fee`   DECIMAL(8,2)     NOT NULL DEFAULT 0.00,
  `discount_class`    DECIMAL(8,2)     NOT NULL DEFAULT 0.00,
  `discount_achieve`  DECIMAL(8,2)     NOT NULL DEFAULT 0.00,
  `final_annual_fee`  DECIMAL(8,2)     NOT NULL DEFAULT 0.00,
  `monthly_fee`       DECIMAL(8,2)     NOT NULL DEFAULT 0.00,
  `early_payment_fee` DECIMAL(8,2)     NOT NULL DEFAULT 0.00,
  `calculated_at`     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mfa` (`member_id`, `year`),
  KEY `idx_mfa_club_year` (`club_id`, `year`),
  CONSTRAINT `fk_mfa_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mfa_club`   FOREIGN KEY (`club_id`)   REFERENCES `clubs`   (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
