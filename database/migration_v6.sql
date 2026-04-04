-- Migration v6: Role permissions matrix
-- Run after migration_v5.sql

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role`   VARCHAR(20) NOT NULL,
  `module` VARCHAR(40) NOT NULL,
  PRIMARY KEY (`role`, `module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default permissions (mirrors hardcoded defaults in RolePermissionModel::DEFAULTS)
INSERT IGNORE INTO `role_permissions` (`role`, `module`) VALUES
  ('admin',      'dashboard'),
  ('admin',      'members'),
  ('admin',      'licenses'),
  ('admin',      'finances'),
  ('admin',      'competitions'),
  ('admin',      'judges'),
  ('admin',      'club_fees'),
  ('admin',      'reports'),
  ('admin',      'config'),
  ('zarzad',     'dashboard'),
  ('zarzad',     'members'),
  ('zarzad',     'licenses'),
  ('zarzad',     'finances'),
  ('zarzad',     'competitions'),
  ('zarzad',     'judges'),
  ('zarzad',     'club_fees'),
  ('zarzad',     'reports'),
  ('zarzad',     'config'),
  ('instruktor', 'dashboard'),
  ('instruktor', 'members'),
  ('instruktor', 'licenses'),
  ('instruktor', 'competitions'),
  ('instruktor', 'reports');
