-- Migration: Add encrypted ID card fields to members table
-- Run once on target database

ALTER TABLE `members`
    ADD COLUMN `id_card_number` VARCHAR(255) NULL DEFAULT NULL
        COMMENT 'Numer dowodu osobistego (zaszyfrowany AES-256-CBC)'
        AFTER `firearm_permit_number`,
    ADD COLUMN `id_card_expiry` VARCHAR(255) NULL DEFAULT NULL
        COMMENT 'Data ważności dowodu (zaszyfrowana AES-256-CBC, format Y-m-d)'
        AFTER `id_card_number`;
