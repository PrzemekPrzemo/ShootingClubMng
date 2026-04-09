-- Migration v26: Onboarding, RODO, subskrypcje
-- Dodaje tabele i kolumny wymagane przez moduły komercyjne:
--   1. Samoregistracja klubu (trial_ends_at, registration_token w clubs)
--   2. Narzędzia RODO (member_consents, anonymized_at w members)
--   3. Subskrypcje klubowe (club_subscriptions)
--   4. Lista rezerwowa na zawody (competition_waitlist)
--   5. Szablony e-mail per klub (email_templates)

-- ===========================================================================
-- 1. ONBOARDING: pola w tabeli clubs
-- ===========================================================================

ALTER TABLE `clubs`
    ADD COLUMN IF NOT EXISTS `trial_ends_at`       DATE         NULL    COMMENT 'Koniec okresu próbnego; NULL = bez ograniczeń',
    ADD COLUMN IF NOT EXISTS `registration_token`  VARCHAR(64)  NULL    COMMENT 'Token potwierdzenia rejestracji e-mail';

-- ===========================================================================
-- 2. RODO: zgody i tabela anonymizacji
-- ===========================================================================

CREATE TABLE IF NOT EXISTS `member_consents` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `member_id`    INT UNSIGNED NOT NULL,
  `consent_type` VARCHAR(80)  NOT NULL COMMENT 'Typ zgody: data_processing, marketing, photo, etc.',
  `granted_at`   DATETIME     NULL,
  `revoked_at`   DATETIME     NULL,
  `ip_address`   VARCHAR(45)  NULL,
  `notes`        TEXT         NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `member_consents_member_idx` (`member_id`),
  KEY `member_consents_type_idx` (`consent_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Zgody RODO zawodników';

ALTER TABLE `members`
    ADD COLUMN IF NOT EXISTS `anonymized_at`   DATETIME NULL    COMMENT 'Wypełnione gdy dane zostały zanonimizowane (prawo do zapomnienia)',
    ADD COLUMN IF NOT EXISTS `gdpr_export_at`  DATETIME NULL    COMMENT 'Data ostatniego eksportu danych (art. 20 RODO)';

-- ===========================================================================
-- 3. SUBSKRYPCJE KLUBOWE
-- ===========================================================================

CREATE TABLE IF NOT EXISTS `club_subscriptions` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`     INT UNSIGNED NOT NULL,
  `plan`        ENUM('trial','basic','standard','premium') NOT NULL DEFAULT 'trial',
  `valid_until` DATE         NULL    COMMENT 'NULL = bezterminowo (legacy / manual)',
  `max_members` INT UNSIGNED NULL    COMMENT 'NULL = bez limitu',
  `status`      ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
  `notes`       TEXT         NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `club_subscriptions_club_uniq` (`club_id`),
  CONSTRAINT `fk_clubsub_club` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Subskrypcje/plany SaaS per klub';

-- Domyślna subskrypcja premium dla istniejącego klubu (id=1)
INSERT IGNORE INTO `club_subscriptions` (`club_id`, `plan`, `valid_until`, `status`)
VALUES (1, 'premium', NULL, 'active');

-- ===========================================================================
-- 4. LISTA REZERWOWA NA ZAWODY
-- ===========================================================================

CREATE TABLE IF NOT EXISTS `competition_waitlist` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `competition_id` INT UNSIGNED NOT NULL,
  `member_id`      INT UNSIGNED NOT NULL,
  `position`       INT UNSIGNED NOT NULL DEFAULT 1,
  `notified_at`    DATETIME     NULL     COMMENT 'Kiedy wysłano powiadomienie o wolnym miejscu',
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `waitlist_comp_member` (`competition_id`, `member_id`),
  KEY `waitlist_comp_pos` (`competition_id`, `position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Lista rezerwowa na zawody';

-- Dodaj max_entries i is_public do competitions jeśli jeszcze nie ma
ALTER TABLE `competitions`
    ADD COLUMN IF NOT EXISTS `max_entries` INT UNSIGNED NULL COMMENT 'Maks. liczba zgłoszeń; NULL = bez limitu',
    ADD COLUMN IF NOT EXISTS `is_public`   TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Czy wyniki są publiczne (widoczne bez logowania)';

-- ===========================================================================
-- 5. SZABLONY E-MAIL PER KLUB
-- ===========================================================================

CREATE TABLE IF NOT EXISTS `email_templates` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`         INT UNSIGNED NULL     COMMENT 'NULL = globalny szablon domyślny',
  `template_type`   VARCHAR(60)  NOT NULL COMMENT 'competition_reminder | license_expiry | payment_reminder | medical_reminder | welcome',
  `subject`         VARCHAR(200) NOT NULL,
  `body_html`       MEDIUMTEXT   NOT NULL,
  `variables_hint`  TEXT         NULL     COMMENT 'Dostępne zmienne: {{member_name}}, {{competition_name}}, ...',
  `is_active`       TINYINT(1)   NOT NULL DEFAULT 1,
  `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `email_tpl_club_type` (`club_id`, `template_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Konfigurowalne szablony e-mail per klub';

-- Domyślne globalne szablony (club_id NULL)
INSERT IGNORE INTO `email_templates` (`club_id`, `template_type`, `subject`, `body_html`, `variables_hint`) VALUES
(NULL, 'competition_reminder',
 'Przypomnienie o zawodach: {{competition_name}}',
 '<p>Drogi/a {{member_name}},</p><p>Przypominamy, że <strong>{{competition_date}}</strong> odbędą się zawody <strong>{{competition_name}}</strong> w {{competition_location}}.</p><p>Jesteś zgłoszony/a do startu. Prosimy o stawienie się punktualnie.</p><p>Pozdrawiamy,<br>{{club_name}}</p>',
 '{{member_name}}, {{competition_name}}, {{competition_date}}, {{competition_location}}, {{club_name}}'
),
(NULL, 'license_expiry',
 'Twoja licencja PZSS wkrótce wygasa',
 '<p>Drogi/a {{member_name}},</p><p>Informujemy, że Twoja licencja PZSS nr <strong>{{license_number}}</strong> wygasa <strong>{{valid_until}}</strong>.</p><p>Prosimy o jej odnowienie w sekretariacie klubu.</p><p>Pozdrawiamy,<br>{{club_name}}</p>',
 '{{member_name}}, {{license_number}}, {{valid_until}}, {{club_name}}'
),
(NULL, 'payment_reminder',
 'Przypomnienie o zaległej składce',
 '<p>Drogi/a {{member_name}},</p><p>Informujemy, że posiadasz zaległą składkę za okres {{period}}.</p><p>Prosimy o uregulowanie należności.</p><p>Pozdrawiamy,<br>{{club_name}}</p>',
 '{{member_name}}, {{period}}, {{amount}}, {{club_name}}'
),
(NULL, 'medical_reminder',
 'Przypomnienie o badaniu lekarskim',
 '<p>Drogi/a {{member_name}},</p><p>Informujemy, że Twoje badanie lekarskie <strong>{{exam_type}}</strong> wygasa <strong>{{valid_until}}</strong>.</p><p>Prosimy o wykonanie badania i dostarczenie zaświadczenia.</p><p>Pozdrawiamy,<br>{{club_name}}</p>',
 '{{member_name}}, {{exam_type}}, {{valid_until}}, {{club_name}}'
),
(NULL, 'welcome',
 'Witamy w {{club_name}}!',
 '<p>Drogi/a {{member_name}},</p><p>Witamy w <strong>{{club_name}}</strong>! Twoje konto zostało aktywowane.</p><p>Możesz zalogować się do portalu zawodnika pod adresem: <a href="{{portal_url}}">{{portal_url}}</a></p><p>Pozdrawiamy,<br>Zarząd {{club_name}}</p>',
 '{{member_name}}, {{club_name}}, {{portal_url}}'
);
