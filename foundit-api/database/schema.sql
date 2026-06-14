-- ======================================================================
-- FoundIt — MySQL schema + sample data
-- Run:  mysql -u root -p < database/schema.sql
-- (or import this file through phpMyAdmin / MySQL Workbench)
-- ======================================================================

DROP DATABASE IF EXISTS foundit;
CREATE DATABASE foundit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE foundit;

-- ---------- Table 1: users ----------
-- `role` powers role-based authorization: 'admin' users can moderate any item
-- and see the moderation dashboard; everyone else is a normal 'user'.
CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------- Table 2: items (belongs to a user) ----------
-- latitude/longitude hold the point chosen on the campus map picker (Leaflet).
-- ai_location_hints stores the JSON produced by the local probabilistic
-- "AI" scorer (likely places + reasons) — see App\Services\AiLocationService.
CREATE TABLE items (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  title         VARCHAR(150) NOT NULL,
  description   TEXT,
  category      VARCHAR(50)  NOT NULL,
  type          ENUM('lost','found') NOT NULL,
  location      VARCHAR(150) NOT NULL,
  status        ENUM('open','claimed','resolved') NOT NULL DEFAULT 'open',
  image_path    VARCHAR(255) DEFAULT NULL,
  latitude      DECIMAL(10, 7) DEFAULT NULL,
  longitude     DECIMAL(10, 7) DEFAULT NULL,
  ai_location_hints TEXT DEFAULT NULL,
  date_reported DATE NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_items_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------- Table 3: claims (belongs to an item AND a user) ----------
CREATE TABLE claims (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  item_id    INT NOT NULL,
  user_id    INT NOT NULL,
  message    TEXT NOT NULL,
  status     ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_claims_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
  CONSTRAINT fk_claims_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ======================================================================
-- Sample data
-- The password_hash below is a real bcrypt hash, so every sample user can
-- log in immediately after import with the password:  password123
-- (Generated with: php -r "echo password_hash('password123', PASSWORD_DEFAULT);")
-- ======================================================================

-- The admin account (role = 'admin') can reach the moderation dashboard at /admin.
INSERT INTO users (name, email, password_hash, role) VALUES
('Aisha Rahman', 'aisha@example.com', '$2y$10$AHuZPtc9cxNiUG.Qiz1R3eSBxccrFjlOTNyPfENU5ozFpY1LeDHKW', 'user'),
('Ben Chong',    'ben@example.com',   '$2y$10$AHuZPtc9cxNiUG.Qiz1R3eSBxccrFjlOTNyPfENU5ozFpY1LeDHKW', 'user'),
('Citra Dewi',   'citra@example.com', '$2y$10$AHuZPtc9cxNiUG.Qiz1R3eSBxccrFjlOTNyPfENU5ozFpY1LeDHKW', 'user'),
('Site Admin',   'admin@example.com', '$2y$10$AHuZPtc9cxNiUG.Qiz1R3eSBxccrFjlOTNyPfENU5ozFpY1LeDHKW', 'admin');

-- Coordinates roughly map to the UTM Skudai campus so the Leaflet picker has data to show.
INSERT INTO items (user_id, title, description, category, type, location, status, latitude, longitude, date_reported) VALUES
(1, 'Black Hydro Flask bottle', 'Found near the library entrance, has a blue band sticker.', 'Other', 'found', 'Main Library', 'open', 1.5599600, 103.6370900, '2026-06-08'),
(2, 'Student ID card - Ben C.', 'Lost my matric card somewhere around the cafeteria.', 'Documents', 'lost', 'Cafeteria Block C', 'open', 1.5575400, 103.6398200, '2026-06-09'),
(1, 'Casio scientific calculator', 'Found a calculator left in lecture hall DK1.', 'Electronics', 'found', 'DK1', 'open', 1.5584100, 103.6352700, '2026-06-09'),
(3, 'Set of keys with red tag', 'Lost a set of keys with a red keychain tag.', 'Keys', 'lost', 'Faculty parking', 'open', 1.5591200, 103.6341500, '2026-06-07');

INSERT INTO claims (item_id, user_id, message, status) VALUES
(1, 2, 'I think that is my bottle - the blue sticker is a band logo.', 'pending'),
(3, 3, 'That calculator is mine, my name is taped inside the cover.', 'pending');
