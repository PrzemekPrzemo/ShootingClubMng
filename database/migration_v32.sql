-- Migration v32: add is_board_linked flag to members
-- Member with this flag pays 50% of base annual fee, no other discounts apply.
-- Editable only by admin / zarząd via the member edit form.

ALTER TABLE `members`
  ADD COLUMN `is_board_linked` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Spięcie z kontem zarządu klubu — 50% składki bez innych zniżek'
    AFTER `notes`;
