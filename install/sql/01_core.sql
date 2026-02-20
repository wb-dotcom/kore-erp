-- ============================================================
-- KORE ERP — 01_core.sql
-- Core tables: roles, users, password_resets, system_settings
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `roles` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(50)     NOT NULL,
  `description` VARCHAR(255)    DEFAULT NULL,
  `created_at`  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id`             INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `first_name`     VARCHAR(100)    NOT NULL,
  `last_name`      VARCHAR(100)    NOT NULL,
  `email`          VARCHAR(150)    NOT NULL,
  `password`       VARCHAR(255)    NOT NULL,
  `role_id`        INT UNSIGNED    NOT NULL DEFAULT 3,
  `avatar`         VARCHAR(255)    DEFAULT NULL,
  `phone`          VARCHAR(30)     DEFAULT NULL,
  `department`     VARCHAR(100)    DEFAULT NULL,
  `hire_date`      DATE            DEFAULT NULL,
  `is_active`      TINYINT(1)      NOT NULL DEFAULT 1,
  `remember_token` VARCHAR(100)    DEFAULT NULL,
  `last_login`     TIMESTAMP       NULL DEFAULT NULL,
  `created_at`     TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role_id`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
  `email`      VARCHAR(150)    NOT NULL,
  `token`      VARCHAR(255)    NOT NULL,
  `created_at` TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_pr_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `system_settings` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `setting_key`   VARCHAR(100)    NOT NULL,
  `setting_value` TEXT            DEFAULT NULL,
  `created_at`    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED    DEFAULT NULL,
  `action`       VARCHAR(100)    NOT NULL,
  `module`       VARCHAR(50)     DEFAULT NULL,
  `reference_id` INT UNSIGNED    DEFAULT NULL,
  `details`      TEXT            DEFAULT NULL,
  `ip_address`   VARCHAR(45)     DEFAULT NULL,
  `created_at`   TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_log_user` (`user_id`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `calendar_events` (
  `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED    DEFAULT NULL,
  `title`      VARCHAR(255)    NOT NULL,
  `event_type` ENUM('business_travel','remote_work','time_off','company_event','other') DEFAULT 'other',
  `start_date` DATE            NOT NULL,
  `end_date`   DATE            DEFAULT NULL,
  `all_day`    TINYINT(1)      DEFAULT 1,
  `notes`      TEXT            DEFAULT NULL,
  `color`      VARCHAR(20)     DEFAULT NULL,
  `created_at` TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_events_user` (`user_id`),
  CONSTRAINT `fk_events_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
