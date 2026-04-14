-- Migration: add booklet_number to member_weapons
-- Run once in phpMyAdmin

ALTER TABLE `member_weapons`
  ADD COLUMN `booklet_number` VARCHAR(50) NULL DEFAULT NULL
    COMMENT 'Numer książeczki / karty rejestracyjnej broni'
    AFTER `permit_number`;
