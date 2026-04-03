-- ============================================================
-- Shooting Club Management System - Database Schema
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

-- ------------------------------------------------------------
-- 1. users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username`     VARCHAR(60)  NOT NULL UNIQUE,
  `email`        VARCHAR(120) NOT NULL UNIQUE,
  `password`     VARCHAR(255) NOT NULL,
  `role`         ENUM('admin','zarzad','instruktor') NOT NULL DEFAULT 'instruktor',
  `full_name`    VARCHAR(120) NOT NULL,
  `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
  `last_login`   DATETIME     NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. member_age_categories  (configurable)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `member_age_categories` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`         VARCHAR(60) NOT NULL,
  `age_from`     TINYINT UNSIGNED NOT NULL,
  `age_to`       TINYINT UNSIGNED NOT NULL,
  `sort_order`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. disciplines
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `disciplines` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`         VARCHAR(100) NOT NULL,
  `short_code`   VARCHAR(20)  NOT NULL UNIQUE,
  `description`  TEXT         NULL,
  `is_active`    TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. members
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `members` (
  `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `member_number`      VARCHAR(20)  NOT NULL UNIQUE,
  `card_number`        VARCHAR(30)  NULL UNIQUE COMMENT 'numer karty dostępu',
  `first_name`         VARCHAR(60)  NOT NULL,
  `last_name`          VARCHAR(60)  NOT NULL,
  `pesel`              VARCHAR(11)  NULL,
  `birth_date`         DATE         NULL,
  `gender`             ENUM('M','K') NULL,
  `age_category_id`    INT UNSIGNED NULL,
  `member_type`        ENUM('rekreacyjny','wyczynowy') NOT NULL DEFAULT 'rekreacyjny',
  `email`              VARCHAR(120) NULL,
  `phone`              VARCHAR(20)  NULL,
  `address_street`     VARCHAR(150) NULL,
  `address_city`       VARCHAR(80)  NULL,
  `address_postal`     VARCHAR(10)  NULL,
  `join_date`          DATE         NOT NULL,
  `status`             ENUM('aktywny','zawieszony','wykreslony') NOT NULL DEFAULT 'aktywny',
  `notes`              TEXT         NULL,
  `created_by`         INT UNSIGNED NULL,
  `created_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`age_category_id`) REFERENCES `member_age_categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`)      REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. member_disciplines  (M:M)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `member_disciplines` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `member_id`      INT UNSIGNED NOT NULL,
  `discipline_id`  INT UNSIGNED NOT NULL,
  `class`          ENUM('Master','A','B','C','D') NULL,
  `instructor_id`  INT UNSIGNED NULL COMMENT 'user będący instruktorem',
  `joined_at`      DATE         NOT NULL,
  UNIQUE KEY `uq_member_discipline` (`member_id`, `discipline_id`),
  FOREIGN KEY (`member_id`)     REFERENCES `members`(`id`)     ON DELETE CASCADE,
  FOREIGN KEY (`discipline_id`) REFERENCES `disciplines`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`instructor_id`) REFERENCES `users`(`id`)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. member_medical_exams  (tylko wyczynowi)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `member_medical_exams` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `member_id`      INT UNSIGNED NOT NULL,
  `exam_date`      DATE         NOT NULL,
  `valid_until`    DATE         NOT NULL,
  `notes`          TEXT         NULL,
  `document_path`  VARCHAR(255) NULL,
  `created_by`     INT UNSIGNED NOT NULL,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`member_id`)  REFERENCES `members`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)   ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. licenses
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `licenses` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `member_id`       INT UNSIGNED NOT NULL,
  `license_type`    ENUM('zawodnicza','trenerska','patent') NOT NULL,
  `license_number`  VARCHAR(60)  NOT NULL,
  `discipline_id`   INT UNSIGNED NULL,
  `issue_date`      DATE         NOT NULL,
  `valid_until`     DATE         NOT NULL,
  `pzss_qr_code`    VARCHAR(255) NULL COMMENT 'QR/link PZSS 2026',
  `status`          ENUM('aktywna','wygasla','zawieszona') NOT NULL DEFAULT 'aktywna',
  `notes`           TEXT         NULL,
  `created_by`      INT UNSIGNED NOT NULL,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`member_id`)     REFERENCES `members`(`id`)     ON DELETE CASCADE,
  FOREIGN KEY (`discipline_id`) REFERENCES `disciplines`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`)    REFERENCES `users`(`id`)       ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. payment_types
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_types` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(80)          NOT NULL,
  `amount`      DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `is_active`   TINYINT(1)           NOT NULL DEFAULT 1,
  `created_at`  DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 9. payments
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `member_id`       INT UNSIGNED      NOT NULL,
  `payment_type_id` INT UNSIGNED      NOT NULL,
  `amount`          DECIMAL(10,2)     NOT NULL,
  `payment_date`    DATE              NOT NULL,
  `period_year`     YEAR             NOT NULL,
  `period_month`    TINYINT UNSIGNED  NULL COMMENT 'NULL = opłata roczna',
  `method`          ENUM('gotówka','przelew','karta','inny') NOT NULL DEFAULT 'gotówka',
  `reference`       VARCHAR(100)      NULL COMMENT 'numer przelewu / pokwitowania',
  `notes`           TEXT              NULL,
  `created_by`      INT UNSIGNED      NOT NULL,
  `created_at`      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`member_id`)       REFERENCES `members`(`id`)       ON DELETE CASCADE,
  FOREIGN KEY (`payment_type_id`) REFERENCES `payment_types`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`created_by`)      REFERENCES `users`(`id`)         ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 10. competitions
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `competitions` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`            VARCHAR(150)      NOT NULL,
  `discipline_id`   INT UNSIGNED      NULL,
  `competition_date` DATE             NOT NULL,
  `location`        VARCHAR(150)      NULL,
  `description`     TEXT              NULL,
  `status`          ENUM('planowane','otwarte','zamkniete','zakonczone') NOT NULL DEFAULT 'planowane',
  `max_entries`     SMALLINT UNSIGNED NULL,
  `created_by`      INT UNSIGNED      NOT NULL,
  `created_at`      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`discipline_id`) REFERENCES `disciplines`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`)    REFERENCES `users`(`id`)       ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 11. competition_groups
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `competition_groups` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `competition_id`  INT UNSIGNED NOT NULL,
  `name`            VARCHAR(80)  NOT NULL,
  `start_time`      DATETIME     NULL,
  `max_slots`       TINYINT UNSIGNED NULL,
  FOREIGN KEY (`competition_id`) REFERENCES `competitions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 12. competition_entries
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `competition_entries` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `competition_id`  INT UNSIGNED NOT NULL,
  `member_id`       INT UNSIGNED NOT NULL,
  `group_id`        INT UNSIGNED NULL,
  `class`           ENUM('Master','A','B','C','D') NULL,
  `status`          ENUM('zgloszony','potwierdzony','wycofany','zdyskwalifikowany') NOT NULL DEFAULT 'zgloszony',
  `registered_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `registered_by`   INT UNSIGNED NOT NULL,
  UNIQUE KEY `uq_entry` (`competition_id`, `member_id`),
  FOREIGN KEY (`competition_id`) REFERENCES `competitions`(`id`)       ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)      REFERENCES `members`(`id`)            ON DELETE CASCADE,
  FOREIGN KEY (`group_id`)       REFERENCES `competition_groups`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`registered_by`)  REFERENCES `users`(`id`)              ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 13. competition_results
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `competition_results` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `competition_id`  INT UNSIGNED    NOT NULL,
  `member_id`       INT UNSIGNED    NOT NULL,
  `group_id`        INT UNSIGNED    NULL,
  `score`           DECIMAL(8,2)    NULL,
  `place`           SMALLINT UNSIGNED NULL,
  `notes`           TEXT            NULL,
  `entered_by`      INT UNSIGNED    NOT NULL,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_result` (`competition_id`, `member_id`),
  FOREIGN KEY (`competition_id`) REFERENCES `competitions`(`id`)       ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)      REFERENCES `members`(`id`)            ON DELETE CASCADE,
  FOREIGN KEY (`group_id`)       REFERENCES `competition_groups`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`entered_by`)     REFERENCES `users`(`id`)              ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 14. settings
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `key`        VARCHAR(80)  NOT NULL PRIMARY KEY,
  `value`      TEXT         NULL,
  `label`      VARCHAR(120) NOT NULL,
  `type`       ENUM('text','number','boolean','json') NOT NULL DEFAULT 'text',
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 15. activity_log
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT UNSIGNED  NULL,
  `action`      VARCHAR(80)   NOT NULL,
  `entity`      VARCHAR(60)   NULL,
  `entity_id`   INT UNSIGNED  NULL,
  `details`     TEXT          NULL,
  `ip_address`  VARCHAR(45)   NULL,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Seed data
-- ============================================================

-- Default age categories
INSERT INTO `member_age_categories` (`name`, `age_from`, `age_to`, `sort_order`) VALUES
  ('Junior młodszy', 13, 15, 1),
  ('Junior',         16, 18, 2),
  ('Senior',         19, 49, 3),
  ('Weteran 50+',    50, 59, 4),
  ('Weteran 60+',    60, 255, 5);

-- Default disciplines
INSERT INTO `disciplines` (`name`, `short_code`) VALUES
  ('Pistolet sportowy',        'PS'),
  ('Karabin sportowy',         'KS'),
  ('Strzelanie dynamiczne',    'SD'),
  ('Trap',                     'TR'),
  ('Skeet',                    'SK'),
  ('Łucznictwo',               'LU');

-- Default payment types
INSERT INTO `payment_types` (`name`, `amount`) VALUES
  ('Składka roczna - rekreacyjny',   300.00),
  ('Składka roczna - wyczynowy',     500.00),
  ('Wpisowe jednorazowe',            100.00),
  ('Opłata za licencję',             150.00),
  ('Opłata za zawody',                50.00);

-- Default settings
INSERT INTO `settings` (`key`, `value`, `label`, `type`) VALUES
  ('club_name',                'Klub Strzelecki',         'Nazwa klubu',                               'text'),
  ('club_address',             '',                        'Adres klubu',                               'text'),
  ('club_email',               '',                        'E-mail klubu',                              'text'),
  ('club_phone',               '',                        'Telefon klubu',                             'text'),
  ('alert_payment_days',       '30',                      'Alert zaległości - dni przed terminem',     'number'),
  ('alert_license_days',       '60',                      'Alert licencji - dni przed wygaśnięciem',   'number'),
  ('alert_medical_days',       '30',                      'Alert badań - dni przed wygaśnięciem',      'number'),
  ('membership_fee_due_month', '3',                       'Miesiąc terminu składki (1-12)',            'number'),
  ('pzss_portal_url',          'https://system.pzss.pl', 'URL portalu PZSS',                          'text');

-- Admin user  (password: Admin1234! - zmień po pierwszym logowaniu!)
INSERT INTO `users` (`username`, `email`, `password`, `role`, `full_name`) VALUES
  ('admin', 'admin@klub.pl', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrator');
-- Uwaga: hash powyżej = 'password' z Laravel; przy wdrożeniu podmień na właściwy hash PHP password_hash()

SET foreign_key_checks = 1;
