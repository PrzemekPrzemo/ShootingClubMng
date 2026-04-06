-- Migration v19: Custom calendar events

CREATE TABLE IF NOT EXISTS `calendar_events` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title`        VARCHAR(200) NOT NULL,
  `event_date`   DATE NOT NULL,
  `event_date_end` DATE NULL COMMENT 'Opcjonalna data zakończenia (dla wydarzeń wielodniowych)',
  `type`         ENUM('zawody_zewnetrzne','spotkanie','szkolenie','wyjazd','inne') NOT NULL DEFAULT 'inne',
  `location`     VARCHAR(200) NULL,
  `description`  TEXT NULL,
  `url`          VARCHAR(500) NULL COMMENT 'Link do strony zewnętrznej, regulaminu itp.',
  `color`        VARCHAR(20) NOT NULL DEFAULT 'secondary' COMMENT 'Bootstrap color name: primary, success, warning, danger, info, secondary',
  `is_public`    TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Widoczne w portalu zawodnika',
  `created_by`   INT UNSIGNED NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
