-- Migration: member_achievements
-- Adds achievement tracking per club member.
-- Run once against the application database.

CREATE TABLE IF NOT EXISTS `member_achievements` (
  `id`               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `member_id`        INT UNSIGNED     NOT NULL,
  `club_id`          INT UNSIGNED     NOT NULL,
  `achievement_type` VARCHAR(30)      NOT NULL,   -- see MemberAchievementModel::TYPES
  `place`            TINYINT UNSIGNED NULL,        -- 1/2/3 (NULL = no specific place)
  `year`             SMALLINT UNSIGNED NOT NULL,
  `competition_name` VARCHAR(200)     NULL,
  `notes`            TEXT             NULL,
  `created_by`       INT UNSIGNED     NULL,
  `created_at`       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ma_member` (`member_id`),
  KEY `idx_ma_club`   (`club_id`),
  CONSTRAINT `fk_ma_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ma_club`   FOREIGN KEY (`club_id`)   REFERENCES `clubs`   (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
