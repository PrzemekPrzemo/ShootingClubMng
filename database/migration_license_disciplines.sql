-- Migration: multi-discipline licenses
-- Run once in phpMyAdmin

CREATE TABLE IF NOT EXISTS `license_disciplines` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `license_id`    INT UNSIGNED NOT NULL,
  `discipline_id` INT UNSIGNED NOT NULL,
  UNIQUE KEY `uq_lic_disc` (`license_id`, `discipline_id`),
  FOREIGN KEY (`license_id`)    REFERENCES `licenses`(`id`)    ON DELETE CASCADE,
  FOREIGN KEY (`discipline_id`) REFERENCES `disciplines`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrate existing single-discipline data
INSERT IGNORE INTO license_disciplines (license_id, discipline_id)
SELECT id, discipline_id FROM licenses WHERE discipline_id IS NOT NULL;
