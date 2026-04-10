-- Migration: switch club_fee_discount_class from member_class_id to discipline_class_id
-- Run ONCE after initial migration_fee_config.sql.
-- Safe to run if table has no data yet (new feature).

ALTER TABLE `club_fee_discount_class`
  DROP FOREIGN KEY `fk_cfdc_class`,
  DROP INDEX `uq_cfdc`,
  CHANGE `member_class_id` `discipline_class_id` INT UNSIGNED NOT NULL,
  ADD UNIQUE KEY `uq_cfdc` (`club_id`, `year`, `discipline_class_id`),
  ADD CONSTRAINT `fk_cfdc_dclass` FOREIGN KEY (`discipline_class_id`)
      REFERENCES `discipline_classes`(`id`) ON DELETE CASCADE;
