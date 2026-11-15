-- Schemat bazy tmpStg (do odtworzenia projektu od zera)
-- Uruchom jako administrator MySQL (root) w phpMyAdmin -> zakładka SQL.

CREATE DATABASE IF NOT EXISTS tmpstg_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE tmpstg_db;

-- Użytkownicy
CREATE TABLE IF NOT EXISTS users (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  firstname  VARCHAR(50)  NOT NULL,
  lastname   VARCHAR(50)  NOT NULL,
  email      VARCHAR(254) NOT NULL,
  password   VARCHAR(255) NOT NULL,          -- hasz z password_hash(); 255 na zapas
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)          -- baza sama pilnuje unikalności emaila
) ENGINE=InnoDB;

-- Nieudane próby logowania (limit prób)
CREATE TABLE IF NOT EXISTS login_attempts (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email        VARCHAR(254) NOT NULL,
  ip           VARCHAR(45)  NOT NULL,        -- 45 znaków mieści IPv6
  attempted_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_attempts_email (email, attempted_at),
  KEY idx_attempts_ip (ip, attempted_at)
) ENGINE=InnoDB;

-- Użytkownik aplikacji z minimalnymi uprawnieniami (NIE root).
-- Zmień hasło na własne, długie i losowe, i wpisz je w connect.php.
CREATE USER IF NOT EXISTS 'tmpstg-app'@'localhost' IDENTIFIED BY 'ZMIEN_TO_HASLO';
GRANT SELECT, INSERT         ON tmpstg_db.users          TO 'tmpstg-app'@'localhost';
GRANT SELECT, INSERT, DELETE ON tmpstg_db.login_attempts TO 'tmpstg-app'@'localhost';
FLUSH PRIVILEGES;

-- Opcjonalne sprzątanie starych prób (np. cron / zdarzenie raz dziennie):
-- DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY);

-- MIGRACJA istniejącej bazy (jeśli nie tworzysz od zera): wykonaj tylko
-- CREATE TABLE login_attempts oraz dwa GRANT-y dla login_attempts powyżej.
