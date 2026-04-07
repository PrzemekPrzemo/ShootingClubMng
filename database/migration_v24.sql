-- Migration v24: Panel sędziego — wyniki z metryczki (serie/strzały)
-- Przechowuje dane per seria per zawodnik per konkurencja
-- Umożliwia weryfikację arytmetyczną sum z papierowej metryczki

CREATE TABLE IF NOT EXISTS `competition_series_results` (
  `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `competition_event_id` INT UNSIGNED NOT NULL,
  `member_id`            INT UNSIGNED NOT NULL,
  `series_number`        TINYINT UNSIGNED NOT NULL COMMENT '1-based index serii',
  `shots`                JSON NOT NULL            COMMENT 'Tablica wartości strzałów w serii',
  `series_total`         DECIMAL(6,2) NOT NULL DEFAULT 0 COMMENT 'Suma serii wpisana przez sędziego',
  `x_count`              TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Liczba X (dziesiątek wewnętrznych) w serii',
  `entered_by`           INT UNSIGNED NULL,
  `created_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_series` (`competition_event_id`,`member_id`,`series_number`),
  FOREIGN KEY (`competition_event_id`) REFERENCES `competition_events`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)            REFERENCES `members`(`id`)            ON DELETE CASCADE,
  FOREIGN KEY (`entered_by`)           REFERENCES `users`(`id`)              ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Wyniki z metryczki — serie i strzały per zawodnik/konkurencja';
