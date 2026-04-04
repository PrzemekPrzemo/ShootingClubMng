-- Migration v16: Email queue table + notification settings

CREATE TABLE IF NOT EXISTS `email_queue` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `to_email`     VARCHAR(200) NOT NULL,
  `to_name`      VARCHAR(200) NOT NULL DEFAULT '',
  `subject`      VARCHAR(400) NOT NULL,
  `body_html`    TEXT         NOT NULL,
  `type`         VARCHAR(60)  NOT NULL COMMENT 'competition_reminder|payment_overdue|license_expiry|medical_expiry',
  `status`       ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `scheduled_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at`      DATETIME     NULL,
  `error`        TEXT         NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `settings` (`key`, `value`, `label`, `type`) VALUES
  ('mail_from_email',      '',   'E-mail nadawcy powiadomień',             'text'),
  ('mail_from_name',       '',   'Nazwa nadawcy powiadomień',              'text'),
  ('notify_competition_days', '7',  'Powiadomienie o zawodach (dni przed)', 'number'),
  ('notify_license_days',     '30', 'Powiadomienie o licencji (dni przed)', 'number'),
  ('notify_medical_days',     '30', 'Powiadomienie o badaniach (dni przed)', 'number');
