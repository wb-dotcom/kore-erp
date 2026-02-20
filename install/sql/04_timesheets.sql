-- ============================================================
-- KORE ERP — 04_timesheets.sql
-- Timesheet periods, timesheets, entries, PTO, time-off, holidays
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `timesheet_periods` (
  `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `start_date` DATE            NOT NULL,
  `end_date`   DATE            NOT NULL,
  `due_date`   DATE            DEFAULT NULL,
  `created_at` TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_periods_dates` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `timesheets` (
  `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED    NOT NULL,
  `period_id`    INT UNSIGNED    NOT NULL,
  `status`       ENUM('draft','submitted','approved','rejected') DEFAULT 'draft',
  `submitted_at` TIMESTAMP       NULL DEFAULT NULL,
  `total_hours`  DECIMAL(6,2)    DEFAULT 0.00,
  `notes`        TEXT            DEFAULT NULL,
  `created_at`   TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_timesheet_user_period` (`user_id`, `period_id`),
  KEY `idx_timesheets_status` (`status`),
  CONSTRAINT `fk_timesheets_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`             (`id`),
  CONSTRAINT `fk_timesheets_period` FOREIGN KEY (`period_id`) REFERENCES `timesheet_periods` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `timesheet_entries` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `timesheet_id`    INT UNSIGNED    NOT NULL,
  `project_id`      INT UNSIGNED    NOT NULL,
  `deliverable_id`  INT UNSIGNED    DEFAULT NULL,
  `milestone_id`    INT UNSIGNED    DEFAULT NULL,
  `task_id`         INT UNSIGNED    DEFAULT NULL,
  `entry_date`      DATE            NOT NULL,
  `hours`           DECIMAL(5,2)    NOT NULL,
  `entry_type`      ENUM('billable','non_billable','pto','unpaid','remote_work') DEFAULT 'billable',
  `notes`           TEXT            DEFAULT NULL,
  `created_at`      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_entries_timesheet`   (`timesheet_id`),
  KEY `idx_entries_project`     (`project_id`),
  KEY `idx_entries_date`        (`entry_date`),
  CONSTRAINT `fk_entries_timesheet`   FOREIGN KEY (`timesheet_id`)   REFERENCES `timesheets`   (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_entries_project`     FOREIGN KEY (`project_id`)     REFERENCES `projects`     (`id`),
  CONSTRAINT `fk_entries_deliverable` FOREIGN KEY (`deliverable_id`) REFERENCES `deliverables` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_entries_milestone`   FOREIGN KEY (`milestone_id`)   REFERENCES `milestones`   (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_entries_task`        FOREIGN KEY (`task_id`)        REFERENCES `tasks`        (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pto_policies` (
  `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`           INT UNSIGNED    NOT NULL,
  `annual_pto_hours`  DECIMAL(6,2)    DEFAULT 0.00,
  `carry_over_hours`  DECIMAL(6,2)    DEFAULT 0.00,
  `effective_date`    DATE            DEFAULT NULL,
  `created_at`        TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pto_user` (`user_id`),
  CONSTRAINT `fk_pto_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `time_off_requests` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`       INT UNSIGNED    NOT NULL,
  `request_type`  ENUM('pto','unpaid','remote_work') NOT NULL,
  `start_date`    DATE            NOT NULL,
  `end_date`      DATE            NOT NULL,
  `hours`         DECIMAL(6,2)    DEFAULT NULL,
  `reason`        TEXT            DEFAULT NULL,
  `status`        ENUM('pending','approved','rejected') DEFAULT 'pending',
  `approved_by`   INT UNSIGNED    DEFAULT NULL,
  `created_at`    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tor_user`   (`user_id`),
  KEY `idx_tor_status` (`status`),
  CONSTRAINT `fk_tor_user`     FOREIGN KEY (`user_id`)     REFERENCES `users` (`id`),
  CONSTRAINT `fk_tor_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `holidays` (
  `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`         VARCHAR(100)    NOT NULL,
  `holiday_date` DATE            NOT NULL,
  `year`         INT             NOT NULL,
  `created_at`   TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_holidays_year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
