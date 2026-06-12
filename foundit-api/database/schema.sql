-- ======================================================================
-- FoundIt — MySQL schema + sample data
-- Run:  mysql -u root -p < database/schema.sql
-- (or import this file through phpMyAdmin / MySQL Workbench)
-- ======================================================================

DROP DATABASE IF EXISTS foundit;
CREATE DATABASE foundit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE foundit;

-- ---------- Table 1: users ----------
CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------- Table 2: items (belongs to a user) ----------
CREATE TABLE items (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  title         VARCHAR(150) NOT NULL,
  description   TEXT,
  category      VARCHAR(50)  NOT NULL,
  type          ENUM('lost','found') NOT NULL,
  location      VARCHAR(150) NOT NULL,
  status        ENUM('open','claimed','resolved') NOT NULL DEFAULT 'open',
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

INSERT INTO users (name, email, password_hash) VALUES
('Aisha Rahman', 'aisha@example.com', '$2y$10$AHuZPtc9cxNiUG.Qiz1R3eSBxccrFjlOTNyPfENU5ozFpY1LeDHKW'),
('Ben Chong',    'ben@example.com',   '$2y$10$AHuZPtc9cxNiUG.Qiz1R3eSBxccrFjlOTNyPfENU5ozFpY1LeDHKW'),
('Citra Dewi',   'citra@example.com', '$2y$10$AHuZPtc9cxNiUG.Qiz1R3eSBxccrFjlOTNyPfENU5ozFpY1LeDHKW');

INSERT INTO items (user_id, title, description, category, type, location, status, date_reported) VALUES
(1, 'Black Hydro Flask bottle', 'Found near the library entrance, has a blue band sticker.', 'Other', 'found', 'Main Library', 'open', '2026-06-08'),
(2, 'Student ID card - Ben C.', 'Lost my matric card somewhere around the cafeteria.', 'Documents', 'lost', 'Cafeteria Block C', 'open', '2026-06-09'),
(1, 'Casio scientific calculator', 'Found a calculator left in lecture hall DK1.', 'Electronics', 'found', 'DK1', 'open', '2026-06-09'),
(3, 'Set of keys with red tag', 'Lost a set of keys with a red keychain tag.', 'Keys', 'lost', 'Faculty parking', 'open', '2026-06-07');

INSERT INTO claims (item_id, user_id, message, status) VALUES
(1, 2, 'I think that is my bottle - the blue sticker is a band logo.', 'pending'),
(3, 3, 'That calculator is mine, my name is taped inside the cover.', 'pending');
