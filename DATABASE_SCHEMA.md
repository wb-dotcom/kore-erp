# Kore ERP — MySQL Database Schema

> Full database design for all modules. Use this as the master reference for migrations.
> > Database name: `kore_erp` (configurable during setup wizard)
> >
> > ---
> >
> > ## TABLE: system_settings
> > Stores global application configuration set during setup wizard.
> > ```sql
> > CREATE TABLE system_settings (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   setting_key VARCHAR(100) NOT NULL UNIQUE,
> >   setting_value TEXT,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
> >   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
> > );
> > -- Keys: company_name, company_logo, timezone, fiscal_year_start, app_installed, smtp_host, smtp_port, smtp_user, smtp_from_email
> > ```
> >
> > ---
> >
> > ## TABLE: roles
> > ```sql
> > CREATE TABLE roles (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   name VARCHAR(50) NOT NULL UNIQUE,
> >   description VARCHAR(255),
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
> > );
> > -- Default roles: Admin, Manager, Employee
> > ```
> >
> > ## TABLE: users
> > ```sql
> > CREATE TABLE users (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   first_name VARCHAR(100) NOT NULL,
> >   last_name VARCHAR(100) NOT NULL,
> >   email VARCHAR(150) NOT NULL UNIQUE,
> >   password VARCHAR(255) NOT NULL,
> >   role_id INT NOT NULL,
> >   avatar VARCHAR(255),
> >   phone VARCHAR(30),
> >   department VARCHAR(100),
> >   hire_date DATE,
> >   is_active TINYINT(1) DEFAULT 1,
> >   remember_token VARCHAR(100),
> >   last_login TIMESTAMP,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
> >   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
> >   FOREIGN KEY (role_id) REFERENCES roles(id)
> > );
> > ```
> >
> > ## TABLE: password_resets
> > ```sql
> > CREATE TABLE password_resets (
> >   email VARCHAR(150) NOT NULL,
> >   token VARCHAR(255) NOT NULL,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
> >   INDEX (email)
> > );
> > ```
> >
> > ---
> >
> > ## TABLE: sectors
> > ```sql
> > CREATE TABLE sectors (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   name VARCHAR(100) NOT NULL UNIQUE,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
> > );
> > ```
> >
> > ## TABLE: regions
> > ```sql
> > CREATE TABLE regions (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   name VARCHAR(100) NOT NULL UNIQUE,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
> > );
> > ```
> >
> > ## TABLE: contact_types
> > ```sql
> > CREATE TABLE contact_types (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   name VARCHAR(100) NOT NULL UNIQUE,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
> > );
> > ```
> >
> > ## TABLE: companies
> > ```sql
> > CREATE TABLE companies (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   name VARCHAR(200) NOT NULL,
> >   sector_id INT,
> >   region_id INT,
> >   website VARCHAR(255),
> >   address_line1 VARCHAR(255),
> >   address_line2 VARCHAR(255),
> >   city VARCHAR(100),
> >   state VARCHAR(100),
> >   zip VARCHAR(20),
> >   country VARCHAR(100),
> >   phone VARCHAR(30),
> >   is_active TINYINT(1) DEFAULT 1,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
> >   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
> >   FOREIGN KEY (sector_id) REFERENCES sectors(id),
> >   FOREIGN KEY (region_id) REFERENCES regions(id)
> > );
> > ```
> >
> > ## TABLE: contacts
> > ```sql
> > CREATE TABLE contacts (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   first_name VARCHAR(100) NOT NULL,
> >   last_name VARCHAR(100) NOT NULL,
> >   company_id INT,
> >   contact_type_id INT,
> >   email VARCHAR(150),
> >   business_phone VARCHAR(30),
> >   mobile_phone VARCHAR(30),
> >   title VARCHAR(100),
> >   notes TEXT,
> >   is_active TINYINT(1) DEFAULT 1,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
> >   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
> >   FOREIGN KEY (company_id) REFERENCES companies(id),
> >   FOREIGN KEY (contact_type_id) REFERENCES contact_types(id)
> > );
> > ```
> >
> > ---
> >
> > ## TABLE: work_types
> > ```sql
> > CREATE TABLE work_types (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   name VARCHAR(100) NOT NULL UNIQUE,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
> > );
> > -- Examples: Architecture & Interior Group, Building Technology Services
> > ```
> >
> > ## TABLE: proposal_statuses
> > ```sql
> > CREATE TABLE proposal_statuses (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   name VARCHAR(50) NOT NULL UNIQUE
> > );
> > -- Values: Approved, Pending, Lost
> > ```
> >
> > ## TABLE: proposals
> > ```sql
> > CREATE TABLE proposals (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   year INT NOT NULL,
> >   proposal_number INT NOT NULL,
> >   title VARCHAR(255) NOT NULL,
> >   company_id INT,
> >   sector_id INT,
> >   work_type_id INT,
> >   account_manager_id INT,
> >   status_id INT NOT NULL,
> >   po_number VARCHAR(100),
> >   description TEXT,
> >   submitted_date DATE,
> >   approved_date DATE,
> >   notes TEXT,
> >   created_by INT,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
> >   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
> >   FOREIGN KEY (company_id) REFERENCES companies(id),
> >   FOREIGN KEY (sector_id) REFERENCES sectors(id),
> >   FOREIGN KEY (work_type_id) REFERENCES work_types(id),
> >   FOREIGN KEY (account_manager_id) REFERENCES users(id),
> >   FOREIGN KEY (status_id) REFERENCES proposal_statuses(id),
> >   FOREIGN KEY (created_by) REFERENCES users(id)
> > );
> > ```
> >
> > ---
> >
> > ## TABLE: project_types
> > ```sql
> > CREATE TABLE project_types (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   name VARCHAR(100) NOT NULL UNIQUE,
> >   is_billable TINYINT(1) DEFAULT 1
> > );
> > -- Values: Billable, Non-Billable
> > ```
> >
> > ## TABLE: project_statuses
> > ```sql
> > CREATE TABLE project_statuses (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   name VARCHAR(50) NOT NULL UNIQUE
> > );
> > -- Values: Active, Closed, On Hold
> > ```
> >
> > ## TABLE: projects
> > ```sql
> > CREATE TABLE projects (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   year INT NOT NULL,
> >   project_number VARCHAR(20) NOT NULL UNIQUE,
> >   title VARCHAR(255) NOT NULL,
> >   company_id INT,
> >   project_manager_id INT,
> >   project_type_id INT,
> >   status_id INT NOT NULL,
> >   proposal_id INT,
> >   start_date DATE,
> >   end_date DATE,
> >   total_budget DECIMAL(12,2) DEFAULT 0,
> >   notes TEXT,
> >   created_by INT,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
> >   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
> >   FOREIGN KEY (company_id) REFERENCES companies(id),
> >   FOREIGN KEY (project_manager_id) REFERENCES users(id),
> >   FOREIGN KEY (project_type_id) REFERENCES project_types(id),
> >   FOREIGN KEY (status_id) REFERENCES project_statuses(id),
> >   FOREIGN KEY (proposal_id) REFERENCES proposals(id),
> >   FOREIGN KEY (created_by) REFERENCES users(id)
> > );
> > ```
> >
> > ## TABLE: deliverables
> > ```sql
> > CREATE TABLE deliverables (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   project_id INT NOT NULL,
> >   name VARCHAR(255) NOT NULL,
> >   description TEXT,
> >   sort_order INT DEFAULT 0,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
> >   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
> >   FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
> > );
> > ```
> >
> > ## TABLE: milestones
> > ```sql
> > CREATE TABLE milestones (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   deliverable_id INT NOT NULL,
> >   name VARCHAR(255) NOT NULL,
> >   description TEXT,
> >   sort_order INT DEFAULT 0,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
> >   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
> >   FOREIGN KEY (deliverable_id) REFERENCES deliverables(id) ON DELETE CASCADE
> > );
> > ```
> >
> > ## TABLE: task_categories
> > ```sql
> > CREATE TABLE task_categories (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   name VARCHAR(100) NOT NULL UNIQUE
> > );
> > ```
> >
> > ## TABLE: tasks
> > ```sql
> > CREATE TABLE tasks (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   milestone_id INT NOT NULL,
> >   name VARCHAR(255) NOT NULL,
> >   description TEXT,
> >   start_date DATE,
> >   end_date DATE,
> >   status ENUM('active','completed','overdue') DEFAULT 'active',
> >   sort_order INT DEFAULT 0,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
> >   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
> >   FOREIGN KEY (milestone_id) REFERENCES milestones(id) ON DELETE CASCADE
> > );
> > ```
> >
> > ## TABLE: roles_schedule_of_fees (billable roles)
> > ```sql
> > CREATE TABLE schedule_of_fees (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   role_name VARCHAR(100) NOT NULL,
> >   hourly_rate DECIMAL(10,2) NOT NULL,
> >   project_type_id INT,
> >   effective_date DATE,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
> >   FOREIGN KEY (project_type_id) REFERENCES project_types(id)
> > );
> > ```
> >
> > ## TABLE: task_assignments
> > ```sql
> > CREATE TABLE task_assignments (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   task_id INT NOT NULL,
> >   user_id INT NOT NULL,
> >   role VARCHAR(100),
> >   budget_hours DECIMAL(6,2) DEFAULT 0,
> >   actual_hours DECIMAL(6,2) DEFAULT 0,
> >   notes TEXT,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
> >   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
> >   FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
> >   FOREIGN KEY (user_id) REFERENCES users(id)
> > );
> > ```
> >
> > ---
> >
> > ## TABLE: timesheet_periods
> > ```sql
> > CREATE TABLE timesheet_periods (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   start_date DATE NOT NULL,
> >   end_date DATE NOT NULL,
> >   due_date DATE,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
> > );
> > ```
> >
> > ## TABLE: timesheets
> > ```sql
> > CREATE TABLE timesheets (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   user_id INT NOT NULL,
> >   period_id INT NOT NULL,
> >   status ENUM('draft','submitted','approved','rejected') DEFAULT 'draft',
> >   submitted_at TIMESTAMP,
> >   total_hours DECIMAL(6,2) DEFAULT 0,
> >   notes TEXT,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
> >   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
> >   FOREIGN KEY (user_id) REFERENCES users(id),
> >   FOREIGN KEY (period_id) REFERENCES timesheet_periods(id)
> > );
> > ```
> >
> > ## TABLE: timesheet_entries
> > ```sql
> > CREATE TABLE timesheet_entries (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   timesheet_id INT NOT NULL,
> >   project_id INT NOT NULL,
> >   deliverable_id INT,
> >   milestone_id INT,
> >   task_id INT,
> >   entry_date DATE NOT NULL,
> >   hours DECIMAL(5,2) NOT NULL,
> >   entry_type ENUM('billable','non_billable','pto','unpaid','remote_work') DEFAULT 'billable',
> >   notes TEXT,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
> >   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
> >   FOREIGN KEY (timesheet_id) REFERENCES timesheets(id) ON DELETE CASCADE,
> >   FOREIGN KEY (project_id) REFERENCES projects(id),
> >   FOREIGN KEY (deliverable_id) REFERENCES deliverables(id),
> >   FOREIGN KEY (milestone_id) REFERENCES milestones(id),
> >   FOREIGN KEY (task_id) REFERENCES tasks(id)
> > );
> > ```
> >
> > ---
> >
> > ## TABLE: approval_settings
> > ```sql
> > CREATE TABLE approval_settings (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   approval_type ENUM('timesheet','time_off','remote_work','expense') NOT NULL,
> >   approver_user_id INT NOT NULL,
> >   department VARCHAR(100),
> >   level INT DEFAULT 1,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
> >   FOREIGN KEY (approver_user_id) REFERENCES users(id)
> > );
> > ```
> >
> > ## TABLE: approvals
> > ```sql
> > CREATE TABLE approvals (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   approval_type ENUM('timesheet','time_off','remote_work','expense') NOT NULL,
> >   reference_id INT NOT NULL,
> >   approver_id INT,
> >   status ENUM('pending','approved','rejected') DEFAULT 'pending',
> >   comments TEXT,
> >   actioned_at TIMESTAMP,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
> >   FOREIGN KEY (approver_id) REFERENCES users(id)
> > );
> > ```
> >
> > ---
> >
> > ## TABLE: pto_policies
> > ```sql
> > CREATE TABLE pto_policies (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   user_id INT NOT NULL UNIQUE,
> >   annual_pto_hours DECIMAL(6,2) DEFAULT 0,
> >   carry_over_hours DECIMAL(6,2) DEFAULT 0,
> >   effective_date DATE,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
> >   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
> >   FOREIGN KEY (user_id) REFERENCES users(id)
> > );
> > ```
> >
> > ## TABLE: time_off_requests
> > ```sql
> > CREATE TABLE time_off_requests (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   user_id INT NOT NULL,
> >   request_type ENUM('pto','unpaid','remote_work') NOT NULL,
> >   start_date DATE NOT NULL,
> >   end_date DATE NOT NULL,
> >   hours DECIMAL(6,2),
> >   reason TEXT,
> >   status ENUM('pending','approved','rejected') DEFAULT 'pending',
> >   approved_by INT,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
> >   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
> >   FOREIGN KEY (user_id) REFERENCES users(id),
> >   FOREIGN KEY (approved_by) REFERENCES users(id)
> > );
> > ```
> >
> > ## TABLE: holidays
> > ```sql
> > CREATE TABLE holidays (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   name VARCHAR(100) NOT NULL,
> >   holiday_date DATE NOT NULL,
> >   year INT NOT NULL,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
> > );
> > ```
> >
> > ---
> >
> > ## TABLE: expense_requests
> > ```sql
> > CREATE TABLE expense_requests (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   user_id INT NOT NULL,
> >   project_id INT,
> >   expense_date DATE NOT NULL,
> >   category VARCHAR(100),
> >   amount DECIMAL(10,2) NOT NULL,
> >   description TEXT,
> >   receipt_path VARCHAR(255),
> >   status ENUM('pending','approved','rejected') DEFAULT 'pending',
> >   approved_by INT,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
> >   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
> >   FOREIGN KEY (user_id) REFERENCES users(id),
> >   FOREIGN KEY (project_id) REFERENCES projects(id),
> >   FOREIGN KEY (approved_by) REFERENCES users(id)
> > );
> > ```
> >
> > ---
> >
> > ## TABLE: invoices
> > ```sql
> > CREATE TABLE invoices (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   invoice_number VARCHAR(50) NOT NULL UNIQUE,
> >   project_id INT NOT NULL,
> >   company_id INT NOT NULL,
> >   invoice_date DATE NOT NULL,
> >   due_date DATE,
> >   status ENUM('draft','sent','paid','overdue') DEFAULT 'draft',
> >   subtotal DECIMAL(12,2) DEFAULT 0,
> >   tax_rate DECIMAL(5,2) DEFAULT 0,
> >   tax_amount DECIMAL(12,2) DEFAULT 0,
> >   total DECIMAL(12,2) DEFAULT 0,
> >   paid_amount DECIMAL(12,2) DEFAULT 0,
> >   notes TEXT,
> >   sent_at TIMESTAMP,
> >   paid_at TIMESTAMP,
> >   created_by INT,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
> >   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
> >   FOREIGN KEY (project_id) REFERENCES projects(id),
> >   FOREIGN KEY (company_id) REFERENCES companies(id),
> >   FOREIGN KEY (created_by) REFERENCES users(id)
> > );
> > ```
> >
> > ## TABLE: invoice_items
> > ```sql
> > CREATE TABLE invoice_items (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   invoice_id INT NOT NULL,
> >   deliverable_id INT,
> >   description VARCHAR(255) NOT NULL,
> >   quantity DECIMAL(8,2) DEFAULT 1,
> >   unit_price DECIMAL(10,2) NOT NULL,
> >   line_total DECIMAL(12,2) NOT NULL,
> >   sort_order INT DEFAULT 0,
> >   FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
> >   FOREIGN KEY (deliverable_id) REFERENCES deliverables(id)
> > );
> > ```
> >
> > ---
> >
> > ## TABLE: calendar_events
> > ```sql
> > CREATE TABLE calendar_events (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   user_id INT,
> >   title VARCHAR(255) NOT NULL,
> >   event_type ENUM('business_travel','remote_work','time_off','company_event','other') DEFAULT 'other',
> >   start_date DATE NOT NULL,
> >   end_date DATE,
> >   all_day TINYINT(1) DEFAULT 1,
> >   notes TEXT,
> >   color VARCHAR(20),
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
> >   FOREIGN KEY (user_id) REFERENCES users(id)
> > );
> > ```
> >
> > ---
> >
> > ## TABLE: activity_logs
> > ```sql
> > CREATE TABLE activity_logs (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   user_id INT,
> >   action VARCHAR(100) NOT NULL,
> >   module VARCHAR(50),
> >   reference_id INT,
> >   details TEXT,
> >   ip_address VARCHAR(45),
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
> >   FOREIGN KEY (user_id) REFERENCES users(id)
> > );
> > ```
> >
> > ## TABLE: system_templates
> > ```sql
> > CREATE TABLE system_templates (
> >   id INT AUTO_INCREMENT PRIMARY KEY,
> >   name VARCHAR(255) NOT NULL,
> >   work_type_id INT,
> >   template_data JSON,
> >   created_by INT,
> >   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
> >   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
> >   FOREIGN KEY (work_type_id) REFERENCES work_types(id),
> >   FOREIGN KEY (created_by) REFERENCES users(id)
> > );
> > ```
> >
> > ---
> >
> > ## Relationships Summary
> >
> > ```
> > users -> roles (many-to-one)
> > companies -> sectors, regions (many-to-one)
> > contacts -> companies, contact_types (many-to-one)
> > proposals -> companies, sectors, work_types, users (many-to-one)
> > projects -> companies, users, project_types, project_statuses, proposals (many-to-one)
> > deliverables -> projects (many-to-one)
> > milestones -> deliverables (many-to-one)
> > tasks -> milestones (many-to-one)
> > task_assignments -> tasks, users (many-to-one)
> > timesheets -> users, timesheet_periods (many-to-one)
> > timesheet_entries -> timesheets, projects, deliverables, milestones, tasks (many-to-one)
> > approvals -> users (many-to-one)
> > time_off_requests -> users (many-to-one)
> > invoices -> projects, companies, users (many-to-one)
> > invoice_items -> invoices, deliverables (many-to-one)
> > ```
> >
> > ---
> >
> > *Last updated: Session 1 — 2026-02-20*
> > 
