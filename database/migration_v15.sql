-- Migration v15: Equipment module (weapons, assignments, ammo)

CREATE TABLE IF NOT EXISTS `weapons` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`          VARCHAR(120) NOT NULL,
  `type`          ENUM('karabin','pistolet','strzelba','inne') NOT NULL DEFAULT 'inne',
  `serial_number` VARCHAR(80)  NULL,
  `caliber`       VARCHAR(30)  NULL,
  `manufacturer`  VARCHAR(80)  NULL,
  `purchase_date` DATE         NULL,
  `condition`     ENUM('dobry','wymaga_obslugi','uszkodzona','wycofana') NOT NULL DEFAULT 'dobry',
  `notes`         TEXT         NULL,
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `weapon_assignments` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `weapon_id`     INT UNSIGNED NOT NULL,
  `member_id`     INT UNSIGNED NOT NULL,
  `assigned_date` DATE         NOT NULL,
  `returned_date` DATE         NULL,
  `notes`         TEXT         NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`weapon_id`) REFERENCES `weapons`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ammo_stock` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `caliber`     VARCHAR(30)  NOT NULL,
  `type`        VARCHAR(60)  NULL COMMENT 'typ naboju np. FMJ, HP',
  `quantity`    INT          NOT NULL COMMENT 'dodatnie = przyjęcie, ujemne = wydanie',
  `notes`       TEXT         NULL,
  `recorded_at` DATE         NOT NULL,
  `recorded_by` INT UNSIGNED NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
