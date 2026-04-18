-- migration_v31: pricing model overhaul for subscription_plans
-- Aligns with shootero.pl/cennik/: base setup 3000 PLN one-time,
-- base yearly 1500 PLN, extra 200-member blocks 500 PLN/mies,
-- scaled modules per 200-member block, flat modules (setup + yearly).

-- Add new columns to subscription_plans
ALTER TABLE `subscription_plans`
    ADD COLUMN IF NOT EXISTS `category` ENUM('base','scaled','flat') NOT NULL DEFAULT 'base' AFTER `sort_order`,
    ADD COLUMN IF NOT EXISTS `price_setup` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Jednorazowa opłata wdrożeniowa (rok 1)' AFTER `category`,
    ADD COLUMN IF NOT EXISTS `price_yearly_recurring` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Roczna opłata (od 2. roku)' AFTER `price_setup`,
    ADD COLUMN IF NOT EXISTS `per_block` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Czy cena skaluje się per 200 członków',
    ADD COLUMN IF NOT EXISTS `block_size` INT UNSIGNED NOT NULL DEFAULT 200 COMMENT 'Wielkość bloku (standardowo 200 członków)',
    ADD COLUMN IF NOT EXISTS `icon` VARCHAR(40) NULL DEFAULT NULL COMMENT 'Bootstrap Icons class (np. bi-people)',
    ADD COLUMN IF NOT EXISTS `note` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Dodatkowa notatka/opis ceny';

-- Clear old seed plans (trial/basic/standard/premium) and insert new Shootero 2026 pricing
-- NOTE: existing club_subscriptions.plan ENUM still references old keys — we keep them so
-- existing subscriptions don't break. New plans use distinct keys (b-*, m-*, f-*).

-- BASE (category = base)
INSERT INTO subscription_plans
    (`key`, label, description, price_pln, price_annual, max_members, is_active, sort_order, category, price_setup, price_yearly_recurring, per_block, block_size, icon, note)
VALUES
    ('b-setup',    'Opłata bazowa — wdrożenie',       'Wdrożenie, szkolenie, konfiguracja (do 200 członków) — rok 1',
        0, 0, 200, 1, 1, 'base', 3000.00, 0.00, 0, 200, 'bi-rocket-takeoff', 'Jednorazowo, pierwszy rok'),
    ('b-yearly',   'Subskrypcja bazowa',              'Roczna opłata bazowa (kolejne lata)',
        125, 1500, 200, 1, 2, 'base', 0.00, 1500.00, 0, 200, 'bi-arrow-repeat', 'Od 2. roku'),
    ('b-extra',    'Dodatkowa paczka 200 członków',   'Każde kolejne 200 członków — 500 PLN/mies = 6000 PLN/rok',
        500, 6000, 200, 1, 3, 'base', 0.00, 6000.00, 1, 200, 'bi-people-fill', 'Za każde rozpoczęte 200 czł.')
ON DUPLICATE KEY UPDATE
    label=VALUES(label), description=VALUES(description),
    price_pln=VALUES(price_pln), price_annual=VALUES(price_annual),
    max_members=VALUES(max_members), is_active=VALUES(is_active), sort_order=VALUES(sort_order),
    category=VALUES(category), price_setup=VALUES(price_setup),
    price_yearly_recurring=VALUES(price_yearly_recurring), per_block=VALUES(per_block),
    block_size=VALUES(block_size), icon=VALUES(icon), note=VALUES(note);

-- SCALED MODULES (yearly price per 200-member block)
INSERT INTO subscription_plans
    (`key`, label, description, price_pln, price_annual, max_members, is_active, sort_order, category, price_setup, price_yearly_recurring, per_block, block_size, icon, note)
VALUES
    ('m-finances',     'Finanse i składki',            'Składki, wpłaty, raporty finansowe',       0, 290, NULL, 1, 10, 'scaled', 0.00, 290.00, 1, 200, 'bi-cash-stack',              '290 PLN / 200 czł. / rok'),
    ('m-competitions', 'Zawody i wyniki',              'Organizacja zawodów, scoring, protokoły', 0, 390, NULL, 1, 11, 'scaled', 0.00, 390.00, 1, 200, 'bi-trophy',                  '390 PLN / 200 czł. / rok'),
    ('m-equipment',    'Sprzęt i broń',                'Inwentarz broni klubowej + członkowskiej', 0, 190, NULL, 1, 12, 'scaled', 0.00, 190.00, 1, 200, 'bi-tools',                   '190 PLN / 200 czł. / rok'),
    ('m-trainings',    'Treningi i obecność',          'Harmonogram treningów + lista obecności', 0, 190, NULL, 1, 13, 'scaled', 0.00, 190.00, 1, 200, 'bi-calendar-event',          '190 PLN / 200 czł. / rok'),
    ('m-portal',       'Portal członka',               'Samoobsługowy portal zawodnika',           0, 290, NULL, 1, 14, 'scaled', 0.00, 290.00, 1, 200, 'bi-person-workspace',        '290 PLN / 200 czł. / rok'),
    ('m-reports',      'Raporty i analizy',            'Eksport CSV, raporty PZSS',                0, 190, NULL, 1, 15, 'scaled', 0.00, 190.00, 1, 200, 'bi-file-earmark-bar-graph',  '190 PLN / 200 czł. / rok')
ON DUPLICATE KEY UPDATE
    label=VALUES(label), description=VALUES(description),
    price_pln=VALUES(price_pln), price_annual=VALUES(price_annual),
    max_members=VALUES(max_members), is_active=VALUES(is_active), sort_order=VALUES(sort_order),
    category=VALUES(category), price_setup=VALUES(price_setup),
    price_yearly_recurring=VALUES(price_yearly_recurring), per_block=VALUES(per_block),
    block_size=VALUES(block_size), icon=VALUES(icon), note=VALUES(note);

-- FLAT MODULES (one-time setup + optional yearly recurring, NOT scaled per block)
INSERT INTO subscription_plans
    (`key`, label, description, price_pln, price_annual, max_members, is_active, sort_order, category, price_setup, price_yearly_recurring, per_block, block_size, icon, note)
VALUES
    ('f-federation',    'Opłaty federacyjne',                'Moduł federacyjny (PZSS fees)',           0, 600, NULL, 1, 20, 'flat', 400.00,  600.00,  0, 0, 'bi-bank',           '1000 PLN rok 1, 600 PLN kolejne lata'),
    ('f-sms',           'Komunikacja SMS',                   'Wysyłka SMS do klubowiczów',              0,   0, NULL, 1, 21, 'flat', 500.00,    0.00,  0, 0, 'bi-chat-dots',      '500 PLN wdrożenie + 0,05 PLN/SMS'),
    ('f-email-hosted',  'Powiadomienia email (hostowane)',   'Wysyłka z naszej infrastruktury',         0,1200, NULL, 1, 22, 'flat', 1000.00, 1200.00, 0, 0, 'bi-envelope-at',    '1000 PLN wdrożenie + 100 PLN/mies'),
    ('f-email-smtp',    'Email SMTP klubu',                  'Wysyłka z konta mailowego klubu',         0,   0, NULL, 1, 23, 'flat', 1000.00,    0.00, 0, 0, 'bi-envelope-gear',  '1000 PLN konfiguracja, bez opłat mies.')
ON DUPLICATE KEY UPDATE
    label=VALUES(label), description=VALUES(description),
    price_pln=VALUES(price_pln), price_annual=VALUES(price_annual),
    max_members=VALUES(max_members), is_active=VALUES(is_active), sort_order=VALUES(sort_order),
    category=VALUES(category), price_setup=VALUES(price_setup),
    price_yearly_recurring=VALUES(price_yearly_recurring), per_block=VALUES(per_block),
    block_size=VALUES(block_size), icon=VALUES(icon), note=VALUES(note);

-- Deactivate legacy plans (trial/basic/standard/premium) so they don't clutter admin view.
-- They remain in DB for any existing club_subscriptions references.
UPDATE subscription_plans SET is_active = 0, sort_order = 99
    WHERE `key` IN ('trial','basic','standard','premium');
