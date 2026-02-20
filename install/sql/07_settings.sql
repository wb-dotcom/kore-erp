-- ============================================================
-- KORE ERP — 07_settings.sql
-- Default seed data inserted after all tables are created
-- ============================================================

-- Default Roles
INSERT IGNORE INTO `roles` (`name`, `description`) VALUES
  ('Admin',    'Full system access - all modules and settings'),
  ('Manager',  'Project and team management, approvals'),
  ('Employee', 'Standard employee: timesheets, tasks, requests');

-- Default Proposal Statuses
INSERT IGNORE INTO `proposal_statuses` (`name`) VALUES
  ('Approved'), ('Pending'), ('Lost');

-- Default Project Types
INSERT IGNORE INTO `project_types` (`name`, `is_billable`) VALUES
  ('Billable',     1),
  ('Non-Billable', 0),
  ('Internal',     0);

-- Default Project Statuses
INSERT IGNORE INTO `project_statuses` (`name`) VALUES
  ('Active'), ('Closed'), ('On Hold');

-- Default Sectors (from K5 KORE analysis)
INSERT IGNORE INTO `sectors` (`name`) VALUES
  ('Architecture'),
  ('Automotive'),
  ('Building Technology'),
  ('Commercial Real Estate'),
  ('Dealer'),
  ('Education'),
  ('Healthcare'),
  ('Hospitality'),
  ('Industrial'),
  ('Mixed Use'),
  ('Retail'),
  ('Other');

-- Default Regions
INSERT IGNORE INTO `regions` (`name`) VALUES
  ('North America'),
  ('Northeast'),
  ('Southeast'),
  ('Midwest'),
  ('Southwest'),
  ('West Coast'),
  ('International');

-- Default Contact Types
INSERT IGNORE INTO `contact_types` (`name`) VALUES
  ('Client'),
  ('Prospect'),
  ('Vendor'),
  ('Partner'),
  ('Contractor'),
  ('Other');

-- Default Work Types (from K5 KORE)
INSERT IGNORE INTO `work_types` (`name`) VALUES
  ('Architecture & Interior Group'),
  ('Building Technology Services'),
  ('Construction Administration'),
  ('Consulting'),
  ('Design'),
  ('Engineering'),
  ('Project Management'),
  ('Other');

-- Default System Settings (app configuration keys)
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`) VALUES
  ('app_name',          'Kore ERP'),
  ('app_version',       '1.0.0'),
  ('app_installed',     '1'),
  ('date_format',       'M/d/Y'),
  ('time_format',       'g:i A'),
  ('currency_symbol',   '$'),
  ('currency_code',     'USD'),
  ('week_start',        'Monday'),
  ('timesheet_period',  'weekly'),
  ('smtp_host',         ''),
  ('smtp_port',         '587'),
  ('smtp_encryption',   'tls'),
  ('smtp_username',     ''),
  ('smtp_from_name',    'Kore ERP'),
  ('smtp_from_email',   ''),
  ('invoices_prefix',   'INV-'),
  ('invoices_next_num', '1001'),
  ('tax_rate_default',  '0.00');
