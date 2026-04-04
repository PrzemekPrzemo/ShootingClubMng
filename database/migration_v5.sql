-- Migration v5: Discipline event templates
-- Run once after migration_v4.sql

CREATE TABLE IF NOT EXISTS `discipline_event_templates` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `discipline_id` INT UNSIGNED NOT NULL,
  `name`          VARCHAR(120) NOT NULL,
  `shots_count`   TINYINT UNSIGNED NULL,
  `scoring_type`  ENUM('decimal','integer','hit_miss') NOT NULL DEFAULT 'decimal',
  `max_score`     DECIMAL(8,2) NULL COMMENT 'Maksymalny wynik (np. 600.0 dla 60×10)',
  `description`   VARCHAR(200) NULL,
  `sort_order`    TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`discipline_id`) REFERENCES `disciplines`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: typical PZSS/ISSF events
-- Note: discipline_id values reference whatever IDs exist in your disciplines table.
-- Run the INSERT below only if your disciplines table has matching short_codes.

INSERT INTO `discipline_event_templates`
  (`discipline_id`, `name`, `shots_count`, `scoring_type`, `max_score`, `description`, `sort_order`)
SELECT d.id, t.name, t.shots_count, t.scoring_type, t.max_score, t.description, t.sort_order
FROM `disciplines` d
JOIN (
  SELECT '10AP' AS code, '10m Pistolet Pneumatyczny — 60 strzałów (finał)'                AS name, 60  AS shots_count, 'decimal'  AS scoring_type, 600.0 AS max_score, 'Finał olimpijski — 60 strzałów dziesiętnych' AS description, 1 AS sort_order
  UNION ALL
  SELECT '10AP', '10m Pistolet Pneumatyczny — 40 strzałów',                                60, 'decimal',  400.0, '40 strzałów kwalifikacja',       2
  UNION ALL
  SELECT '10AK', '10m Karabin Pneumatyczny — 60 strzałów (finał)',                         60, 'decimal',  600.0, 'Finał olimpijski',               1
  UNION ALL
  SELECT '10AK', '10m Karabin Pneumatyczny — 40 strzałów',                                 40, 'decimal',  400.0, '40 strzałów kwalifikacja',       2
  UNION ALL
  SELECT 'PS',   '25m Pistolet Sportowy — 60 strzałów',                                    60, 'decimal',  600.0, '60 strzałów dziesiętna',         1
  UNION ALL
  SELECT 'PS',   '25m Pistolet Szybkostrzelny — 60 strzałów',                              60, 'integer',  600,   '60 strzałów całkowita',          2
  UNION ALL
  SELECT 'KS',   '50m Karabin Leżąc — 60 strzałów',                                       60, 'decimal',  600.0, 'Klasyczna konkurencja leżąc',    1
  UNION ALL
  SELECT 'KS',   '50m Karabin 3×40 — 120 strzałów',                                      120, 'decimal', 1200.0, '3 serie × 40: leżąc/stojąc/klęcząc', 2
  UNION ALL
  SELECT 'KS',   '50m Karabin 3×20 — 60 strzałów',                                        60, 'decimal',  600.0, 'Wersja skrócona 3 serie × 20',  3
  UNION ALL
  SELECT 'TR',   'Trap — 75 rzutków',                                                      75, 'hit_miss', NULL,  '3 serie × 25 rzutków',          1
  UNION ALL
  SELECT 'TR',   'Trap — 25 rzutków',                                                      25, 'hit_miss', NULL,  'Jedna seria',                   2
  UNION ALL
  SELECT 'SK',   'Skeet — 100 rzutków',                                                   100, 'hit_miss', NULL,  '4 serie × 25 rzutków',          1
  UNION ALL
  SELECT 'SK',   'Skeet — 25 rzutków',                                                     25, 'hit_miss', NULL,  'Jedna seria',                   2
  UNION ALL
  SELECT 'SD',   'Strzelanie dynamiczne — IPSC',                                          NULL, 'decimal',  NULL,  'Punktacja IPSC (alfa/charlie/delta)', 1
) t ON d.short_code = t.code
WHERE d.is_active = 1;
