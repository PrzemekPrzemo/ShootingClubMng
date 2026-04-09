-- migration_v30: per-club dictionaries for discipline classes and member types

-- 1. Relax member_disciplines.class from ENUM to VARCHAR
ALTER TABLE `member_disciplines`
  MODIFY `class` VARCHAR(20) NULL;

-- 2. Discipline classes dictionary (global + per-club)
CREATE TABLE IF NOT EXISTS `discipline_classes` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `club_id`    INT UNSIGNED NULL DEFAULT NULL,
  `name`       VARCHAR(20)  NOT NULL,
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dc_club` (`club_id`),
  CONSTRAINT `fk_dc_club` FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `discipline_classes` (`club_id`, `name`, `sort_order`) VALUES
  (NULL, 'Master', 0),
  (NULL, 'A',      1),
  (NULL, 'B',      2),
  (NULL, 'C',      3),
  (NULL, 'D',      4);

-- 3. Relax members.member_type from ENUM to VARCHAR
ALTER TABLE `members`
  MODIFY `member_type` VARCHAR(50) NOT NULL DEFAULT 'rekreacyjny';

-- 4. Member types dictionary (global + per-club)
CREATE TABLE IF NOT EXISTS `member_types` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `club_id`    INT UNSIGNED NULL DEFAULT NULL,
  `name`       VARCHAR(50)  NOT NULL,
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mt_club` (`club_id`),
  CONSTRAINT `fk_mt_club` FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `member_types` (`club_id`, `name`, `sort_order`) VALUES
  (NULL, 'rekreacyjny', 0),
  (NULL, 'wyczynowy',   1);
