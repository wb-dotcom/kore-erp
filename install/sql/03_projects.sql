-- ============================================================
-- KORE ERP — 03_projects.sql
-- Proposals, Projects, Deliverables, Milestones, Tasks, Assignments
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `work_types` (
  `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100)    NOT NULL,
  `created_at` TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_work_types_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `proposal_statuses` (
  `id`   INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50)     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prop_status_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `proposals` (
  `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `year`             INT             NOT NULL,
  `proposal_number`  INT             NOT NULL,
  `title`            VARCHAR(255)    NOT NULL,
  `company_id`       INT UNSIGNED    DEFAULT NULL,
  `sector_id`        INT UNSIGNED    DEFAULT NULL,
  `work_type_id`     INT UNSIGNED    DEFAULT NULL,
  `account_manager_id` INT UNSIGNED  DEFAULT NULL,
  `status_id`        INT UNSIGNED    NOT NULL,
  `po_number`        VARCHAR(100)    DEFAULT NULL,
  `description`      TEXT            DEFAULT NULL,
  `submitted_date`   DATE            DEFAULT NULL,
  `approved_date`    DATE            DEFAULT NULL,
  `notes`            TEXT            DEFAULT NULL,
  `created_by`       INT UNSIGNED    DEFAULT NULL,
  `created_at`       TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_proposals_company`  (`company_id`),
  KEY `idx_proposals_status`   (`status_id`),
  KEY `idx_proposals_year`     (`year`),
  CONSTRAINT `fk_proposals_company`     FOREIGN KEY (`company_id`)         REFERENCES `companies`          (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_proposals_sector`      FOREIGN KEY (`sector_id`)          REFERENCES `sectors`            (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_proposals_work_type`   FOREIGN KEY (`work_type_id`)       REFERENCES `work_types`         (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_proposals_manager`     FOREIGN KEY (`account_manager_id`) REFERENCES `users`             (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_proposals_status`      FOREIGN KEY (`status_id`)          REFERENCES `proposal_statuses` (`id`),
  CONSTRAINT `fk_proposals_created_by`  FOREIGN KEY (`created_by`)         REFERENCES `users`             (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_types` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100)    NOT NULL,
  `is_billable` TINYINT(1)      NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_project_types_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_statuses` (
  `id`   INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50)     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_proj_status_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `projects` (
  `id`                 INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `year`               INT             NOT NULL,
  `project_number`     VARCHAR(20)     NOT NULL,
  `title`              VARCHAR(255)    NOT NULL,
  `company_id`         INT UNSIGNED    DEFAULT NULL,
  `project_manager_id` INT UNSIGNED    DEFAULT NULL,
  `project_type_id`    INT UNSIGNED    DEFAULT NULL,
  `status_id`          INT UNSIGNED    NOT NULL,
  `proposal_id`        INT UNSIGNED    DEFAULT NULL,
  `start_date`         DATE            DEFAULT NULL,
  `end_date`           DATE            DEFAULT NULL,
  `total_budget`       DECIMAL(12,2)   DEFAULT 0.00,
  `notes`              TEXT            DEFAULT NULL,
  `created_by`         INT UNSIGNED    DEFAULT NULL,
  `created_at`         TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_project_number` (`project_number`),
  KEY `idx_projects_company`  (`company_id`),
  KEY `idx_projects_manager`  (`project_manager_id`),
  KEY `idx_projects_status`   (`status_id`),
  CONSTRAINT `fk_projects_company`     FOREIGN KEY (`company_id`)         REFERENCES `companies`        (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_projects_manager`     FOREIGN KEY (`project_manager_id`) REFERENCES `users`           (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_projects_type`        FOREIGN KEY (`project_type_id`)    REFERENCES `project_types`   (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_projects_status`      FOREIGN KEY (`status_id`)          REFERENCES `project_statuses`(`id`),
  CONSTRAINT `fk_projects_proposal`    FOREIGN KEY (`proposal_id`)        REFERENCES `proposals`       (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_projects_created_by`  FOREIGN KEY (`created_by`)         REFERENCES `users`           (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `deliverables` (
  `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED    NOT NULL,
  `name`       VARCHAR(255)    NOT NULL,
  `description` TEXT           DEFAULT NULL,
  `sort_order` INT             DEFAULT 0,
  `created_at` TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_deliverables_project` (`project_id`),
  CONSTRAINT `fk_deliverables_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `milestones` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `deliverable_id`  INT UNSIGNED    NOT NULL,
  `name`            VARCHAR(255)    NOT NULL,
  `description`     TEXT            DEFAULT NULL,
  `sort_order`      INT             DEFAULT 0,
  `created_at`      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_milestones_deliverable` (`deliverable_id`),
  CONSTRAINT `fk_milestones_deliverable` FOREIGN KEY (`deliverable_id`) REFERENCES `deliverables` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tasks` (
  `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `milestone_id` INT UNSIGNED    NOT NULL,
  `name`         VARCHAR(255)    NOT NULL,
  `description`  TEXT            DEFAULT NULL,
  `start_date`   DATE            DEFAULT NULL,
  `end_date`     DATE            DEFAULT NULL,
  `status`       ENUM('active','completed','overdue') DEFAULT 'active',
  `sort_order`   INT             DEFAULT 0,
  `created_at`   TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tasks_milestone` (`milestone_id`),
  KEY `idx_tasks_status` (`status`),
  CONSTRAINT `fk_tasks_milestone` FOREIGN KEY (`milestone_id`) REFERENCES `milestones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `task_assignments` (
  `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `task_id`      INT UNSIGNED    NOT NULL,
  `user_id`      INT UNSIGNED    NOT NULL,
  `role`         VARCHAR(100)    DEFAULT NULL,
  `budget_hours` DECIMAL(6,2)    DEFAULT 0.00,
  `actual_hours` DECIMAL(6,2)    DEFAULT 0.00,
  `notes`        TEXT            DEFAULT NULL,
  `created_at`   TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_task_assign_task` (`task_id`),
  KEY `idx_task_assign_user` (`user_id`),
  CONSTRAINT `fk_task_assign_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_task_assign_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `schedule_of_fees` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `role_name`       VARCHAR(100)    NOT NULL,
  `hourly_rate`     DECIMAL(10,2)   NOT NULL,
  `project_type_id` INT UNSIGNED    DEFAULT NULL,
  `effective_date`  DATE            DEFAULT NULL,
  `created_at`      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_fees_project_type` FOREIGN KEY (`project_type_id`) REFERENCES `project_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `system_templates` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(255)    NOT NULL,
  `work_type_id`  INT UNSIGNED    DEFAULT NULL,
  `template_data` JSON            DEFAULT NULL,
  `created_by`    INT UNSIGNED    DEFAULT NULL,
  `created_at`    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_templates_work_type`  FOREIGN KEY (`work_type_id`) REFERENCES `work_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_templates_created_by` FOREIGN KEY (`created_by`)   REFERENCES `users`      (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
