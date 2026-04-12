-- =============================================================================
-- Migration: Przelewy24 online payments
-- Requires: clubs, members tables
-- =============================================================================

CREATE TABLE IF NOT EXISTS `online_payments` (
  `id`               INT UNSIGNED      AUTO_INCREMENT PRIMARY KEY,
  `club_id`          INT UNSIGNED      NOT NULL,
  `member_id`        INT UNSIGNED      NOT NULL,

  -- What is being paid for
  `payment_type`     ENUM('fee','competition_entry','other') NOT NULL DEFAULT 'fee',
  `reference_id`     INT UNSIGNED      NULL COMMENT 'payments.id or competition_entries.id',
  `description`      VARCHAR(255)      NOT NULL,

  -- Money
  `amount`           DECIMAL(10,2)     NOT NULL,
  `currency`         CHAR(3)           NOT NULL DEFAULT 'PLN',

  -- P24 identifiers
  `p24_session_id`   VARCHAR(120)      NOT NULL UNIQUE COMMENT 'Our unique session ID sent to P24',
  `p24_order_id`     INT UNSIGNED      NULL     COMMENT 'P24 orderId returned in notification',
  `p24_token`        VARCHAR(255)      NULL     COMMENT 'Token for redirect URL',
  `p24_method_id`    SMALLINT UNSIGNED NULL     COMMENT 'Payment method chosen by user',

  -- Status
  `status`           ENUM('pending','verified','failed','cancelled') NOT NULL DEFAULT 'pending',

  -- Payer info captured at creation
  `payer_email`      VARCHAR(150)      NULL,
  `payer_name`       VARCHAR(120)      NULL,

  `created_at`       DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  KEY `idx_op_member`  (`member_id`),
  KEY `idx_op_club`    (`club_id`),
  KEY `idx_op_status`  (`status`),
  KEY `idx_op_session` (`p24_session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
