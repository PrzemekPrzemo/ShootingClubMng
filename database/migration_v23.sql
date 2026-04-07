-- Migration v23: Allow NULL valid_until in licenses (patents have no expiry date)

ALTER TABLE `licenses`
  MODIFY COLUMN `valid_until` DATE NULL;
