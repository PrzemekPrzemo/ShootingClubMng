-- Migration v22: Member photo upload

ALTER TABLE `members`
  ADD COLUMN IF NOT EXISTS `photo_path` VARCHAR(255) NULL
    COMMENT 'Ścieżka do zdjęcia zawodnika (storage/photos/)' AFTER `notes`;
