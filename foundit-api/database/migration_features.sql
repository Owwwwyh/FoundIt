-- ======================================================================
-- FoundIt — migration for the three add-on features
--   1. Admin role + moderation dashboard
--   2. Campus map location picker (latitude / longitude)
--   3. Local "AI" location scorer  (ai_location_hints)
--
-- Run this against an EXISTING `foundit` database (one that was created
-- before these features existed) so you don't have to drop your data:
--
--     mysql -u root -p foundit < database/migration_features.sql
--
-- It is safe to re-run: each ALTER/UPDATE is written to be idempotent.
-- ======================================================================

USE foundit;

-- ---------- 1. Admin role ----------
-- Add the role column only if it isn't there yet.
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role');
SET @sql := IF(@col = 0,
  "ALTER TABLE users ADD COLUMN role ENUM('user','admin') NOT NULL DEFAULT 'user' AFTER password_hash",
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Promote a default admin account. If admin@example.com already exists it is
-- promoted; otherwise it is created (password: password123).
INSERT INTO users (name, email, password_hash, role)
VALUES ('Site Admin', 'admin@example.com',
        '$2y$10$AHuZPtc9cxNiUG.Qiz1R3eSBxccrFjlOTNyPfENU5ozFpY1LeDHKW', 'admin')
ON DUPLICATE KEY UPDATE role = 'admin';

-- ---------- 2. Campus map coordinates ----------
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'items' AND COLUMN_NAME = 'latitude');
SET @sql := IF(@col = 0,
  'ALTER TABLE items ADD COLUMN latitude DECIMAL(10,7) DEFAULT NULL AFTER image_path',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'items' AND COLUMN_NAME = 'longitude');
SET @sql := IF(@col = 0,
  'ALTER TABLE items ADD COLUMN longitude DECIMAL(10,7) DEFAULT NULL AFTER latitude',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ---------- 3. AI location hints ----------
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'items' AND COLUMN_NAME = 'ai_location_hints');
SET @sql := IF(@col = 0,
  'ALTER TABLE items ADD COLUMN ai_location_hints TEXT DEFAULT NULL AFTER longitude',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
