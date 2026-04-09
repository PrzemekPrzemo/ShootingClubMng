-- Migration v25: Multi-klub (multi-tenant)
-- Przebudowa systemu na obsługę wielu klubów strzeleckich.
-- Strategia: shared database, shared schema z dyskryminatorem club_id.
-- Słowniki (disciplines, member_classes itp.) mają club_id NULL = wpis globalny.

-- ===========================================================================
-- NOWE TABELE
-- ===========================================================================

CREATE TABLE IF NOT EXISTS `clubs` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`         VARCHAR(150) NOT NULL,
  `short_name`   VARCHAR(50)  NULL,
  `email`        VARCHAR(120) NULL,
  `phone`        VARCHAR(20)  NULL,
  `address`      TEXT         NULL,
  `nip`          VARCHAR(15)  NULL,
  `is_active`    TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Kluby strzeleckie zarządzane przez system';

CREATE TABLE IF NOT EXISTS `user_clubs` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `club_id`    INT UNSIGNED NOT NULL,
  `role`       ENUM('zarzad','instruktor','sędzia') NOT NULL DEFAULT 'instruktor',
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_user_club` (`user_id`, `club_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Powiązanie użytkowników z klubami (M:M z rolą)';

CREATE TABLE IF NOT EXISTS `club_settings` (
  `club_id`    INT UNSIGNED NOT NULL,
  `key`        VARCHAR(80)  NOT NULL,
  `value`      TEXT         NULL,
  `label`      VARCHAR(120) NOT NULL DEFAULT '',
  `type`       ENUM('text','number','boolean','json') NOT NULL DEFAULT 'text',
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`club_id`, `key`),
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Ustawienia per-klub (SMTP, powiadomienia, limity)';

CREATE TABLE IF NOT EXISTS `club_customization` (
  `club_id`       INT UNSIGNED  PRIMARY KEY,
  `logo_path`     VARCHAR(255)  NULL,
  `primary_color` VARCHAR(20)   NOT NULL DEFAULT '#0d6efd',
  `navbar_bg`     VARCHAR(20)   NOT NULL DEFAULT '#212529',
  `custom_css`    TEXT          NULL,
  `subdomain`     VARCHAR(80)   NULL UNIQUE COMMENT 'np. mks-gdansk → mks-gdansk.system.pl',
  `updated_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Customizacja wizualna per-klub (logo, kolory, subdomena)';

-- ===========================================================================
-- ZMIANY W TABELI users
-- ===========================================================================

ALTER TABLE `users`
  ADD COLUMN `is_super_admin` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Globalny administrator — ma dostęp do wszystkich klubów'
    AFTER `role`;

-- ===========================================================================
-- DODANIE club_id DO TABEL OPERACYJNYCH (NOT NULL, DEFAULT 1)
-- ===========================================================================

ALTER TABLE `members`
  ADD COLUMN `club_id` INT UNSIGNED NOT NULL DEFAULT 1
    COMMENT 'Klub macierzysty zawodnika' AFTER `id`,
  ADD FOREIGN KEY `fk_members_club` (`club_id`) REFERENCES `clubs`(`id`) ON DELETE RESTRICT,
  ADD INDEX `idx_members_club` (`club_id`);

ALTER TABLE `competitions`
  ADD COLUMN `club_id` INT UNSIGNED NOT NULL DEFAULT 1
    COMMENT 'Klub organizujący zawody' AFTER `id`,
  ADD FOREIGN KEY `fk_competitions_club` (`club_id`) REFERENCES `clubs`(`id`) ON DELETE RESTRICT,
  ADD INDEX `idx_competitions_club` (`club_id`);

ALTER TABLE `trainings`
  ADD COLUMN `club_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`,
  ADD FOREIGN KEY `fk_trainings_club` (`club_id`) REFERENCES `clubs`(`id`) ON DELETE RESTRICT,
  ADD INDEX `idx_trainings_club` (`club_id`);

ALTER TABLE `calendar_events`
  ADD COLUMN `club_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`,
  ADD FOREIGN KEY `fk_calendar_events_club` (`club_id`) REFERENCES `clubs`(`id`) ON DELETE RESTRICT,
  ADD INDEX `idx_calendar_events_club` (`club_id`);

ALTER TABLE `announcements`
  ADD COLUMN `club_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`,
  ADD FOREIGN KEY `fk_announcements_club` (`club_id`) REFERENCES `clubs`(`id`) ON DELETE RESTRICT,
  ADD INDEX `idx_announcements_club` (`club_id`);

ALTER TABLE `weapons`
  ADD COLUMN `club_id` INT UNSIGNED NOT NULL DEFAULT 1
    COMMENT 'Klub właściciel broni' AFTER `id`,
  ADD FOREIGN KEY `fk_weapons_club` (`club_id`) REFERENCES `clubs`(`id`) ON DELETE RESTRICT,
  ADD INDEX `idx_weapons_club` (`club_id`);

ALTER TABLE `ammo_stock`
  ADD COLUMN `club_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`,
  ADD FOREIGN KEY `fk_ammo_stock_club` (`club_id`) REFERENCES `clubs`(`id`) ON DELETE RESTRICT,
  ADD INDEX `idx_ammo_stock_club` (`club_id`);

ALTER TABLE `club_fees`
  ADD COLUMN `club_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`,
  ADD FOREIGN KEY `fk_club_fees_club` (`club_id`) REFERENCES `clubs`(`id`) ON DELETE RESTRICT,
  ADD INDEX `idx_club_fees_club` (`club_id`);

ALTER TABLE `payment_types`
  ADD COLUMN `club_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`,
  ADD FOREIGN KEY `fk_payment_types_club` (`club_id`) REFERENCES `clubs`(`id`) ON DELETE RESTRICT,
  ADD INDEX `idx_payment_types_club` (`club_id`);

ALTER TABLE `email_queue`
  ADD COLUMN `club_id` INT UNSIGNED NOT NULL DEFAULT 1
    COMMENT 'Klub — używany do wyboru konfiguracji SMTP' AFTER `id`,
  ADD FOREIGN KEY `fk_email_queue_club` (`club_id`) REFERENCES `clubs`(`id`) ON DELETE RESTRICT,
  ADD INDEX `idx_email_queue_club` (`club_id`);

ALTER TABLE `notifications`
  ADD COLUMN `club_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`,
  ADD FOREIGN KEY `fk_notifications_club` (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE,
  ADD INDEX `idx_notifications_club` (`club_id`);

ALTER TABLE `activity_log`
  ADD COLUMN `club_id` INT UNSIGNED NULL
    COMMENT 'NULL gdy akcja super admina bez kontekstu klubu' AFTER `id`,
  ADD FOREIGN KEY `fk_activity_log_club` (`club_id`) REFERENCES `clubs`(`id`) ON DELETE SET NULL,
  ADD INDEX `idx_activity_log_club` (`club_id`);

ALTER TABLE `calendar_event_categories`
  ADD COLUMN `club_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`,
  ADD FOREIGN KEY `fk_cal_cat_club` (`club_id`) REFERENCES `clubs`(`id`) ON DELETE RESTRICT,
  ADD INDEX `idx_cal_cat_club` (`club_id`);

-- ===========================================================================
-- SŁOWNIKI — club_id NULL (globalne) lub NOT NULL (per-klub)
-- ===========================================================================

ALTER TABLE `disciplines`
  ADD COLUMN `club_id` INT UNSIGNED NULL
    COMMENT 'NULL = globalny słownik admina; NOT NULL = pozycja per-klub' AFTER `id`,
  ADD FOREIGN KEY `fk_disciplines_club` (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE,
  ADD INDEX `idx_disciplines_club` (`club_id`);

ALTER TABLE `member_classes`
  ADD COLUMN `club_id` INT UNSIGNED NULL AFTER `id`,
  ADD FOREIGN KEY `fk_member_classes_club` (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE,
  ADD INDEX `idx_member_classes_club` (`club_id`);

ALTER TABLE `member_age_categories`
  ADD COLUMN `club_id` INT UNSIGNED NULL AFTER `id`,
  ADD FOREIGN KEY `fk_age_cat_club` (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE,
  ADD INDEX `idx_age_cat_club` (`club_id`);

ALTER TABLE `license_types`
  ADD COLUMN `club_id` INT UNSIGNED NULL AFTER `id`,
  ADD FOREIGN KEY `fk_license_types_club` (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE,
  ADD INDEX `idx_license_types_club` (`club_id`);

ALTER TABLE `medical_exam_types`
  ADD COLUMN `club_id` INT UNSIGNED NULL AFTER `id`,
  ADD FOREIGN KEY `fk_medical_exam_types_club` (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE,
  ADD INDEX `idx_medical_exam_types_club` (`club_id`);

-- ===========================================================================
-- role_permissions — zmiana PK na (club_id, role, module)
-- club_id NULL = globalny domyślny (dla wstecznej kompatybilności)
-- ===========================================================================

ALTER TABLE `role_permissions`
  ADD COLUMN `club_id` INT UNSIGNED NULL
    COMMENT 'NULL = globalny domyślny' FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`club_id`, `role`, `module`),
  ADD FOREIGN KEY `fk_role_perm_club` (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE;

-- Istniejące wpisy (bez club_id) stają się globalnymi domyślnymi
-- Nie wymagają UPDATE — wartość NULL jest prawidłowa

-- ===========================================================================
-- SEED — domyślny klub + super admin
-- ===========================================================================

INSERT INTO `clubs` (`id`, `name`, `short_name`, `is_active`) VALUES
  (1, 'Klub Domyślny', 'KD', 1);

INSERT INTO `club_customization` (`club_id`) VALUES (1);

-- Ustaw super admina
UPDATE `users` SET `is_super_admin` = 1 WHERE `username` = 'admin';

-- Domyślne ustawienia dla pierwszego klubu
INSERT INTO `club_settings` (`club_id`, `key`, `value`, `label`, `type`) VALUES
  (1, 'smtp_enabled',      '0',   'Własny SMTP klubu (0=wyłączony)',   'boolean'),
  (1, 'smtp_host',         '',    'Serwer SMTP',                       'text'),
  (1, 'smtp_port',         '587', 'Port SMTP',                         'number'),
  (1, 'smtp_secure',       'tls', 'Szyfrowanie (none/ssl/tls)',        'text'),
  (1, 'smtp_user',         '',    'Login SMTP',                        'text'),
  (1, 'smtp_pass_enc',     '',    'Hasło SMTP (szyfrowane)',           'text'),
  (1, 'smtp_from_email',   '',    'E-mail nadawcy',                    'text'),
  (1, 'smtp_from_name',    '',    'Nazwa nadawcy',                     'text'),
  (1, 'notify_comp_days',  '7',   'Powiadomienie o zawodach (dni przed)', 'number'),
  (1, 'notify_lic_days',   '30',  'Powiadomienie o licencji (dni przed)', 'number'),
  (1, 'notify_med_days',   '30',  'Powiadomienie o badaniach (dni przed)', 'number');

-- Globalne ustawienia systemowe (admin)
INSERT IGNORE INTO `settings` (`key`, `value`, `label`, `type`) VALUES
  ('allow_club_smtp',   '0', 'Zezwól klubom na własną konfigurację SMTP', 'boolean'),
  ('base_domain',       '',  'Domena bazowa systemu (np. system.pl)',      'text'),
  ('smtp_host',         '',  'Globalny serwer SMTP',                       'text'),
  ('smtp_port',         '587', 'Globalny port SMTP',                       'number'),
  ('smtp_secure',       'tls', 'Globalne szyfrowanie SMTP',                'text'),
  ('smtp_user',         '',  'Globalny login SMTP',                        'text'),
  ('smtp_pass_enc',     '',  'Globalne hasło SMTP (szyfrowane)',           'text');
