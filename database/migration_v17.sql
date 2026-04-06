-- Migration v17: Add judge license type
-- Run once in phpMyAdmin or via CLI

INSERT IGNORE INTO `license_types` (`name`, `short_code`, `validity_months`, `is_active`, `sort_order`)
VALUES ('Licencja sędziowska', 'sedziowska', 12, 1, 4);
