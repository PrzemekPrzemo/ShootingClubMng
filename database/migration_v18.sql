-- Migration v18: Trainings, Announcements, Feature flags

-- Trainings
CREATE TABLE IF NOT EXISTS `trainings` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title`            VARCHAR(200) NOT NULL,
  `training_date`    DATE NOT NULL,
  `time_start`       TIME NULL,
  `time_end`         TIME NULL,
  `lane`             VARCHAR(50) NULL,
  `instructor_id`    INT UNSIGNED NULL,
  `max_participants` SMALLINT UNSIGNED NULL,
  `notes`            TEXT NULL,
  `status`           ENUM('planowany','odbyl_sie','odwolany') NOT NULL DEFAULT 'planowany',
  `created_by`       INT UNSIGNED NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`instructor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`)   REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_attendees` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `training_id` INT UNSIGNED NOT NULL,
  `member_id`   INT UNSIGNED NOT NULL,
  `attended`    TINYINT(1) NOT NULL DEFAULT 0,
  `notes`       VARCHAR(200) NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_training_member` (`training_id`, `member_id`),
  FOREIGN KEY (`training_id`) REFERENCES `trainings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)   REFERENCES `members`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Announcements
CREATE TABLE IF NOT EXISTS `announcements` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title`        VARCHAR(200) NOT NULL,
  `body`         TEXT NOT NULL,
  `priority`     ENUM('normal','wazne','pilne') NOT NULL DEFAULT 'normal',
  `is_published` TINYINT(1) NOT NULL DEFAULT 0,
  `published_at` DATETIME NULL,
  `expires_at`   DATE NULL,
  `created_by`   INT UNSIGNED NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feature flags in settings
INSERT IGNORE INTO `settings` (`key`, `value`, `label`, `type`) VALUES
  ('feature_calendar',         '1', 'Moduł: Kalendarz',              'boolean'),
  ('feature_trainings',        '1', 'Moduł: Treningi',               'boolean'),
  ('feature_announcements',    '1', 'Moduł: Ogłoszenia',             'boolean'),
  ('feature_audit_log',        '1', 'Moduł: Dziennik audytu',        'boolean'),
  ('feature_member_card',      '1', 'Moduł: Karta zawodnika',        'boolean'),
  ('feature_stats_dashboard',  '1', 'Moduł: Statystyki zarządu',     'boolean'),
  ('feature_csv_import',       '0', 'Moduł: Import CSV (beta)',      'boolean'),
  ('feature_lane_bookings',    '0', 'Moduł: Rezerwacja stanowisk',   'boolean');
