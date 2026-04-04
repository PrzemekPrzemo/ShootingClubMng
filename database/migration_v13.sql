-- Migration v13: Add 'sędzia' role to users table
ALTER TABLE `users`
  MODIFY COLUMN `role` ENUM('admin','zarzad','instruktor','sędzia') NOT NULL DEFAULT 'instruktor';
