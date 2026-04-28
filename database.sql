-- ============================================================
--  StudySwap Hub — database.sql
--  Import this file into phpMyAdmin or run:
--  mysql -u root -p studyswap_hub < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS `studyswap_hub`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `studyswap_hub`;

-- ── Users ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `first_name`   VARCHAR(80)  NOT NULL,
  `last_name`    VARCHAR(80)  NOT NULL,
  `email`        VARCHAR(160) NOT NULL UNIQUE,
  `phone`        VARCHAR(20)  DEFAULT NULL,
  `password`     VARCHAR(255) NOT NULL,
  `university`   VARCHAR(120) NOT NULL,
  `role`         ENUM('student','admin') NOT NULL DEFAULT 'student',
  `is_active`    TINYINT(1)   NOT NULL DEFAULT 1,
  `total_swaps`  INT UNSIGNED NOT NULL DEFAULT 0,
  `bio`          TEXT         DEFAULT NULL,
  `rating`       DECIMAL(3,1) NOT NULL DEFAULT 5.0,
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Books ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `books` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`       INT UNSIGNED NOT NULL,
  `title`         VARCHAR(220) NOT NULL,
  `author`        VARCHAR(160) NOT NULL,
  `edition`       VARCHAR(40)  DEFAULT NULL,
  `category`      ENUM('Engineering','Medical','Business','Science','Arts & Humanities','Law','Other') NOT NULL,
  `condition_val` ENUM('Like New','Good','Fair','Acceptable') NOT NULL,
  `listing_type`  ENUM('swap','sale','free') NOT NULL DEFAULT 'swap',
  `price`         DECIMAL(8,2) DEFAULT NULL,
  `swap_for`      VARCHAR(200) DEFAULT NULL,
  `university`    VARCHAR(120) NOT NULL,
  `description`   TEXT         DEFAULT NULL,
  `image`         VARCHAR(200) DEFAULT NULL,
  `file_path`     VARCHAR(255) DEFAULT NULL,
  `is_available`  TINYINT(1)   NOT NULL DEFAULT 1,
  `view_count`    INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Requests ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `requests` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `book_id`       INT UNSIGNED NOT NULL,
  `requester_id`  INT UNSIGNED NOT NULL,
  `owner_id`      INT UNSIGNED NOT NULL,
  `offer_book_id` INT UNSIGNED DEFAULT NULL,
  `message`       TEXT         DEFAULT NULL,
  `request_type`  ENUM('swap','sale','free') NOT NULL DEFAULT 'swap',
  `status`        ENUM('pending','accepted','declined','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`book_id`)       REFERENCES `books`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`requester_id`)  REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`owner_id`)      REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Swap History ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `swap_history` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `request_id`    INT UNSIGNED DEFAULT NULL,
  `giver_id`      INT UNSIGNED NOT NULL,
  `receiver_id`   INT UNSIGNED NOT NULL,
  `book_given`    INT UNSIGNED DEFAULT NULL,
  `book_received` INT UNSIGNED DEFAULT NULL,
  `swap_date`     DATE         NOT NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`giver_id`)    REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Wishlist ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `wishlist` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `book_id`    INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_wish` (`user_id`,`book_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_id`) REFERENCES `books`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Notifications ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `type`       VARCHAR(60)  NOT NULL DEFAULT 'system',
  `message`    TEXT         NOT NULL,
  `link`       VARCHAR(300) DEFAULT '',
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Default Admin Account ─────────────────────────────────────
-- Password: admin123  (change after first login!)
INSERT IGNORE INTO `users`
  (`first_name`,`last_name`,`email`,`password`,`university`,`role`,`is_active`)
VALUES
  ('Admin','StudySwap','admin@studyswap.pk',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'NUST Islamabad','admin',1);
