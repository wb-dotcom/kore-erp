-- ============================================================
-- KORE ERP — 06_invoicing.sql
-- Invoices and invoice line items
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `invoices` (
  `id`             INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `invoice_number` VARCHAR(50)     NOT NULL,
  `project_id`     INT UNSIGNED    NOT NULL,
  `company_id`     INT UNSIGNED    NOT NULL,
  `invoice_date`   DATE            NOT NULL,
  `due_date`       DATE            DEFAULT NULL,
  `status`         ENUM('draft','sent','paid','overdue') DEFAULT 'draft',
  `subtotal`       DECIMAL(12,2)   DEFAULT 0.00,
  `tax_rate`       DECIMAL(5,2)    DEFAULT 0.00,
  `tax_amount`     DECIMAL(12,2)   DEFAULT 0.00,
  `total`          DECIMAL(12,2)   DEFAULT 0.00,
  `paid_amount`    DECIMAL(12,2)   DEFAULT 0.00,
  `notes`          TEXT            DEFAULT NULL,
  `sent_at`        TIMESTAMP       NULL DEFAULT NULL,
  `paid_at`        TIMESTAMP       NULL DEFAULT NULL,
  `created_by`     INT UNSIGNED    DEFAULT NULL,
  `created_at`     TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_invoice_number` (`invoice_number`),
  KEY `idx_invoices_project` (`project_id`),
  KEY `idx_invoices_company` (`company_id`),
  KEY `idx_invoices_status`  (`status`),
  CONSTRAINT `fk_invoices_project`    FOREIGN KEY (`project_id`) REFERENCES `projects`  (`id`),
  CONSTRAINT `fk_invoices_company`    FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `fk_invoices_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`     (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `invoice_id`      INT UNSIGNED    NOT NULL,
  `deliverable_id`  INT UNSIGNED    DEFAULT NULL,
  `description`     VARCHAR(255)    NOT NULL,
  `quantity`        DECIMAL(8,2)    DEFAULT 1.00,
  `unit_price`      DECIMAL(10,2)   NOT NULL,
  `line_total`      DECIMAL(12,2)   NOT NULL,
  `sort_order`      INT             DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_invoice_items_invoice` (`invoice_id`),
  CONSTRAINT `fk_invoice_items_invoice`     FOREIGN KEY (`invoice_id`)     REFERENCES `invoices`     (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_invoice_items_deliverable` FOREIGN KEY (`deliverable_id`) REFERENCES `deliverables` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
