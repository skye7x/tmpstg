-- ============================================================
-- tmpStg: clean database (structure only, NO data)
-- Import: cPanel -> phpMyAdmin -> select YOUR database -> Import
-- Safe to run on an empty database; existing tables are left untouched.
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- OPTIONAL full reset (DELETES ALL DATA). Uncomment only if you
-- want to wipe everything and start from scratch.
-- ------------------------------------------------------------
-- DROP TABLE IF EXISTS `files`;
-- DROP TABLE IF EXISTS `login_attempts`;
-- DROP TABLE IF EXISTS `register_attempts`;
-- DROP TABLE IF EXISTS `users`;

-- ------------------------------------------------------------
-- users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`        int(10) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(50)  NOT NULL,
  `lastname`  varchar(50)  NOT NULL,
  `email`     varchar(254) NOT NULL,
  `password`  varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- files (uploaded files; expire 5 minutes after upload)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `files` (
  `id`            bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`       int(10) UNSIGNED NOT NULL,
  `stored_name`   char(32)     NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `size`          int(10) UNSIGNED NOT NULL,
  `uploaded_at`   datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at`    datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_files_stored` (`stored_name`),
  KEY `idx_files_user` (`user_id`, `expires_at`),
  KEY `idx_files_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- login_attempts (rate limiting of logins)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id`           bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`        varchar(254) NOT NULL,
  `ip`           varchar(45)  NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_attempts_email` (`email`, `attempted_at`),
  KEY `idx_attempts_ip` (`ip`, `attempted_at`),
  KEY `idx_attempts_time` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- register_attempts (rate limiting of registrations, 5 per IP per hour)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `register_attempts` (
  `id`           bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip`           varchar(45) NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ra_ip` (`ip`, `attempted_at`),
  KEY `idx_ra_time` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
