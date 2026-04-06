-- Migration v21: Member personal weapons and firearm permits

-- 1. Add firearm_permit_number column to members table
ALTER TABLE `members`
  ADD COLUMN IF NOT EXISTS `firearm_permit_number` VARCHAR(100) NULL
    COMMENT 'Numer pozwolenia na broń' AFTER `notes`;

-- 2. Create member_weapons table (personal weapons owned by club members)
CREATE TABLE IF NOT EXISTS `member_weapons` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `member_id`     INT UNSIGNED NOT NULL,
  `name`          VARCHAR(200) NOT NULL COMMENT 'Nazwa/model broni',
  `type`          ENUM('pistolet','rewolwer','karabin','strzelba','inne') NOT NULL DEFAULT 'inne',
  `serial_number` VARCHAR(100) NULL COMMENT 'Numer seryjny',
  `caliber`       VARCHAR(50)  NULL COMMENT 'Kaliber, np. 9mm, .22 LR',
  `manufacturer`  VARCHAR(100) NULL COMMENT 'Producent',
  `permit_number` VARCHAR(100) NULL COMMENT 'Numer pozwolenia na tę konkretną broń (jeśli inny niż główny)',
  `notes`         TEXT NULL,
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
  `created_by`    INT UNSIGNED NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`member_id`)  REFERENCES `members`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
