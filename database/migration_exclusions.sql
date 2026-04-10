-- ============================================================
-- Tabela wykluczeń globalnych wpisów słownika per klub
-- Uruchom raz przed wdrożeniem funkcji wykluczania wpisów.
-- ============================================================

CREATE TABLE IF NOT EXISTS `club_dictionary_exclusions` (
  `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `club_id`     INT UNSIGNED     NOT NULL,
  `dictionary`  VARCHAR(60)      NOT NULL COMMENT 'Klucz słownika: categories, disciplines, member_classes, ...',
  `entry_id`    INT UNSIGNED     NOT NULL COMMENT 'ID wykluczonego globalnego wpisu',
  `created_at`  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cde` (`club_id`, `dictionary`, `entry_id`),
  KEY `idx_cde_lookup` (`club_id`, `dictionary`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
