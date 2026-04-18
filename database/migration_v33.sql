-- Migration v33: pełna izolacja danych per klub
--
-- 1) Tabela `payments` nie miała dotychczas kolumny club_id — root cause
--    wycieku finansów między tenantami. Dodajemy i backfillujemy z members.
-- 2) `member_consents` i `competition_waitlist` były tylko pośrednio powiązane
--    z klubem (przez member_id / competition_id). Dodajemy bezpośredni club_id
--    dla prostego i bezpiecznego scope'owania zapytań.
-- 3) `trainings.is_public` — analogicznie do competitions/calendar_events,
--    flaga publikacji do portalu zawodnika.

-- ===========================================================================
-- 1. payments.club_id
-- ===========================================================================

ALTER TABLE `payments`
  ADD COLUMN `club_id` INT UNSIGNED NULL AFTER `member_id`,
  ADD INDEX `idx_payments_club_id` (`club_id`),
  ADD CONSTRAINT `fk_payments_club`
      FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE;

UPDATE `payments` p
  JOIN `members` m ON m.id = p.member_id
   SET p.club_id = m.club_id
 WHERE p.club_id IS NULL;

-- ===========================================================================
-- 2. member_consents.club_id
-- ===========================================================================

ALTER TABLE `member_consents`
  ADD COLUMN `club_id` INT UNSIGNED NULL AFTER `member_id`,
  ADD INDEX `idx_member_consents_club_id` (`club_id`),
  ADD CONSTRAINT `fk_member_consents_club`
      FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE;

UPDATE `member_consents` c
  JOIN `members` m ON m.id = c.member_id
   SET c.club_id = m.club_id
 WHERE c.club_id IS NULL;

-- ===========================================================================
-- 3. competition_waitlist.club_id
-- ===========================================================================

ALTER TABLE `competition_waitlist`
  ADD COLUMN `club_id` INT UNSIGNED NULL AFTER `competition_id`,
  ADD INDEX `idx_competition_waitlist_club_id` (`club_id`),
  ADD CONSTRAINT `fk_competition_waitlist_club`
      FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE;

UPDATE `competition_waitlist` w
  JOIN `competitions` c ON c.id = w.competition_id
   SET w.club_id = c.club_id
 WHERE w.club_id IS NULL;

-- ===========================================================================
-- 4. trainings.is_public (analogicznie do competitions/calendar_events)
-- ===========================================================================

ALTER TABLE `trainings`
  ADD COLUMN `is_public` TINYINT(1) NOT NULL DEFAULT 1
      COMMENT 'Widoczne dla zawodników w portalu' AFTER `club_id`;
