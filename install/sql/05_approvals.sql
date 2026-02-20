-- ============================================================
-- KORE ERP — 05_approvals.sql
-- Approval settings, approvals, expense requests
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `approval_settings` (
  `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `approval_type`    ENUM('timesheet','time_off','remote_work','expense') NOT NULL,
  `approver_user_id` INT UNSIGNED    NOT NULL,
  `department`       VARCHAR(100)    DEFAULT NULL,
  `level`            INT             DEFAULT 1,
  `created_at`       TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_approval_settings_type`     (`approval_type`),
  KEY `idx_approval_settings_approver` (`approver_user_id`),
  CONSTRAINT `fk_approval_settings_user` FOREIGN KEY (`approver_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `approvals` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `approval_type` ENUM('timesheet','time_off','remote_work','expense') NOT NULL,
  `reference_id`  INT UNSIGNED    NOT NULL,
  `approver_id`   INT UNSIGNED    DEFAULT NULL,
  `status`        ENUM('pending','approved','rejected') DEFAULT 'pending',
  `comments`      TEXT            DEFAULT NULL,
  `actioned_at`   TIMESTAMP       NULL DEFAULT NULL,
  `created_at`    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_approvals_type`      (`approval_type`),
  KEY `idx_approvals_reference` (`reference_id`),
  KEY `idx_approvals_status`    (`status`),
  CONSTRAINT `fk_approvals_approver` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expense_requests` (
  `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED    NOT NULL,
  `project_id`   INT UNSIGNED    DEFAULT NULL,
  `expense_date` DATE            NOT NULL,
  `category`     VARCHAR(100)    DEFAULT NULL,
  `amount`       DECIMAL(10,2)   NOT NULL,
  `description`  TEXT            DEFAULT NULL,
  `receipt_path` VARCHAR(255)    DEFAULT NULL,
  `status`       ENUM('pending','approved','rejected') DEFAULT 'pending',
  `approved_by`  INT UNSIGNED    DEFAULT NULL,
  `created_at`   TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_expense_user`    (`user_id`),
  KEY `idx_expense_project` (`project_id`),
  KEY `idx_expense_status`  (`status`),
  CONSTRAINT `fk_expense_user`     FOREIGN KEY (`user_id`)     REFERENCES `users`    (`id`),
  CONSTRAINT `fk_expense_project`  FOREIGN KEY (`project_id`)  REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_expense_approver` FOREIGN KEY (`approved_by`) REFERENCES `users`    (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
