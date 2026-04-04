-- ============================================================
-- Migration v3 — Typy badań lekarskich, Opłaty PZSS, Sędziowie
-- Uruchomić raz na serwerze: mysql ... < migration_v3.sql
-- lub wkleić do phpMyAdmin
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ------------------------------------------------------------
-- 1. Słownik typów badań lekarskich (konfigurowalny przez admina)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `medical_exam_types` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`            VARCHAR(100) NOT NULL,
  `required_for`    ENUM('patent','license','both') NOT NULL DEFAULT 'both',
  `validity_months` TINYINT UNSIGNED NOT NULL DEFAULT 12,
  `sort_order`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`       TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `medical_exam_types` (`name`, `required_for`, `validity_months`, `sort_order`) VALUES
  ('Badanie ogólne (lekarz medycyny sportowej)', 'both',    12, 1),
  ('Badanie psychologiczne',                      'both',    12, 2),
  ('Badanie psychiatryczne',                      'both',    24, 3),
  ('Badanie okulistyczne',                        'both',    12, 4);

-- ------------------------------------------------------------
-- 2. Rozszerzenie tabeli badań lekarskich
-- ------------------------------------------------------------
ALTER TABLE `member_medical_exams`
  ADD COLUMN IF NOT EXISTS `exam_type_id` INT UNSIGNED NULL AFTER `member_id`,
  ADD COLUMN IF NOT EXISTS `file_path` VARCHAR(255) NULL AFTER `notes`;

-- Dodaj FK do exam_type tylko jeśli nie istnieje
SET @fk_exam_type = (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'member_medical_exams'
    AND CONSTRAINT_NAME = 'fk_exam_type'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);
SET @sql = IF(@fk_exam_type = 0,
  'ALTER TABLE `member_medical_exams` ADD CONSTRAINT `fk_exam_type` FOREIGN KEY (`exam_type_id`) REFERENCES `medical_exam_types`(`id`) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 3. Licencje sędziowskie
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `judge_licenses` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `member_id`       INT UNSIGNED NOT NULL,
  `judge_class`     ENUM('III','II','I','P') NOT NULL,
  `discipline_id`   INT UNSIGNED NULL,
  `license_number`  VARCHAR(60) NULL,
  `issue_date`      DATE NOT NULL,
  `valid_until`     DATE NOT NULL,
  `fee_paid_year`   YEAR NULL COMMENT 'Rok w którym zapłacono 50 PLN do PomZSS',
  `fee_paid_date`   DATE NULL,
  `notes`           TEXT NULL,
  `created_by`      INT UNSIGNED NOT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`member_id`)     REFERENCES `members`(`id`)     ON DELETE CASCADE,
  FOREIGN KEY (`discipline_id`) REFERENCES `disciplines`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`)    REFERENCES `users`(`id`)        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. Sędziowie przypisani do zawodów
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `competition_judges` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `competition_id`  INT UNSIGNED NOT NULL,
  `member_id`       INT UNSIGNED NOT NULL,
  `role`            ENUM('glowny','liniowy','obliczeniowy','bezpieczenstwa','protokolant') NOT NULL DEFAULT 'liniowy',
  `notes`           VARCHAR(150) NULL,
  UNIQUE KEY `uq_comp_judge` (`competition_id`, `member_id`, `role`),
  FOREIGN KEY (`competition_id`) REFERENCES `competitions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)      REFERENCES `members`(`id`)      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. Opłaty klubowe wobec PZSS/PomZSS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `club_fees` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `year`         YEAR NOT NULL,
  `fee_type`     ENUM(
    'licencja_pzss',
    'czlonek_pzss',
    'czlonek_pomzss',
    'licencje_zawodnicze',
    'licencje_sedziowskie'
  ) NOT NULL,
  `amount_due`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `due_date`     DATE NOT NULL,
  `paid_date`    DATE NULL,
  `paid_amount`  DECIMAL(10,2) NULL,
  `reference`    VARCHAR(100) NULL COMMENT 'Numer przelewu',
  `notes`        TEXT NULL,
  `created_by`   INT UNSIGNED NOT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_club_fee` (`year`, `fee_type`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
