-- Migration v27: rate_limits, 2FA, sms_queue, subscription_plans, ads, impersonation log
-- Run after migration_v26.sql

-- ── Rate limiting ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS rate_limits (
    `key`       VARCHAR(128) NOT NULL,
    attempts    TINYINT UNSIGNED NOT NULL DEFAULT 1,
    reset_at    DATETIME NOT NULL,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 2FA / TOTP ────────────────────────────────────────────────────────────
ALTER TABLE users
    ADD COLUMN totp_secret  VARCHAR(64)  NULL AFTER password,
    ADD COLUMN totp_enabled TINYINT(1)   NOT NULL DEFAULT 0 AFTER totp_secret;

CREATE TABLE IF NOT EXISTS totp_backup_codes (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    code_hash  VARCHAR(64)  NOT NULL,
    used_at    DATETIME     NULL,
    PRIMARY KEY (id),
    KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── SMS queue ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sms_queue (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    club_id      INT UNSIGNED NOT NULL,
    to_phone     VARCHAR(20)  NOT NULL,
    to_name      VARCHAR(100) NULL,
    message      VARCHAR(480) NOT NULL,
    type         VARCHAR(50)  NOT NULL DEFAULT 'general',
    status       ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    scheduled_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at      DATETIME     NULL,
    error        VARCHAR(255) NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_status (status),
    KEY idx_club   (club_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add phone to members if missing
ALTER TABLE members
    ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL AFTER email;

-- ── Subscription plans (edytowalne przez superadmina) ─────────────────────
CREATE TABLE IF NOT EXISTS subscription_plans (
    `key`         VARCHAR(30)  NOT NULL,
    label         VARCHAR(80)  NOT NULL,
    max_members   INT UNSIGNED NULL COMMENT 'NULL = nieograniczone',
    price_pln     DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    price_annual  DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Roczna (opcjonalna)',
    description   TEXT         NULL,
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    sort_order    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO subscription_plans (`key`, label, max_members, price_pln, price_annual, sort_order) VALUES
('trial',    'Próbny (Trial)', 50,   0.00,   0.00,   0),
('basic',    'Basic',          50,   49.00,  490.00, 1),
('standard', 'Standard',       200,  99.00,  990.00, 2),
('premium',  'Premium',        NULL, 199.00, 1990.00,3);

-- ── Billing invoices (model rozliczeń) ───────────────────────────────────
CREATE TABLE IF NOT EXISTS billing_invoices (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    club_id        INT UNSIGNED NOT NULL,
    plan_key       VARCHAR(30)  NOT NULL,
    amount_pln     DECIMAL(8,2) NOT NULL,
    period_from    DATE         NOT NULL,
    period_to      DATE         NOT NULL,
    status         ENUM('draft','issued','paid','cancelled') NOT NULL DEFAULT 'draft',
    invoice_number VARCHAR(30)  NULL,
    notes          TEXT         NULL,
    issued_at      DATETIME     NULL,
    paid_at        DATETIME     NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_club   (club_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Ads (system reklam) ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ads (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title       VARCHAR(120) NOT NULL,
    content     TEXT         NOT NULL COMMENT 'HTML lub plain text',
    image_path  VARCHAR(255) NULL,
    link_url    VARCHAR(500) NULL,
    target      SET('club_ui','member_portal') NOT NULL DEFAULT 'club_ui,member_portal',
    club_id     INT UNSIGNED NULL COMMENT 'NULL = wszystkie kluby',
    plan_keys   VARCHAR(200) NULL COMMENT 'CSV planów, NULL = wszystkie',
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    starts_at   DATE         NULL,
    ends_at     DATE         NULL,
    sort_order  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Impersonation log ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS impersonation_log (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_user_id   INT UNSIGNED NOT NULL,
    target_type     ENUM('club_user','member') NOT NULL,
    target_id       INT UNSIGNED NOT NULL,
    target_club_id  INT UNSIGNED NULL,
    started_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ended_at        DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_admin (admin_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
