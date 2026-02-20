# Kore ERP — Project Redevelopment Plan

## Overview
Kore ERP is a full redevelopment of the K5 KORE proprietary ERP system as a self-hosted, installable web application. It will run on a Windows Server with PHP and MySQL, and includes a guided setup wizard for first-time installation.

---

## Tech Stack

| Component | Technology |
|---|---|
| Backend | PHP 8.2+ / Laravel 11 |
| Frontend | Blade Templates + Bootstrap 5 |
| Database | MySQL 8.0 |
| Charts | Chart.js |
| Calendar/Gantt | FullCalendar.js |
| Server | Windows Server (IIS or Apache/XAMPP) |
| Auth | Laravel Breeze (session-based) |
| Email | SMTP / PHP Mailer |

---

## Application Modules

### 1. Setup Wizard (install/)
A web-based installer that runs on first deployment.
- Step 1: Welcome & System Requirements Check (PHP version, extensions, MySQL)
- - Step 2: Database Configuration (host, name, user, password — test connection)
  - - Step 3: Admin Account Creation (name, email, password)
    - - Step 4: Company Settings (name, logo, fiscal year, timezone)
      - - Step 5: Install & Seed (runs migrations, seeds lookup tables)
        - - Step 6: Complete (launch app, delete install folder warning)
         
          - ### 2. Authentication & User Management
          - - Login / Logout with session management
            - - Role-based access control: Admin, Manager, Employee
              - - User profiles with avatar, role, department
                - - Password reset via email
                 
                  - ### 3. Dashboard
                  - Four views switchable by tab:
                  - - **Employee Dashboard**: Missing timesheets alert, rejected timesheets, next 7 days calendar events, billable vs non-billable hours (personal)
                    - - **Business Dashboard**: Company-wide billable/non-billable hours, time by project breakdown, all employees
                      - - **Financial Dashboard**: Total budget, total billed, total collected, outstanding invoices, monthly billing & collection trends chart, top clients by billing
                        - - **KPI Dashboard**: Resource utilization, project health metrics, employee performance
                         
                          - ### 4. Proposals Module
                          - - Create, edit, delete proposals
                            - - Fields: Year, Title, Company, Sector, Type, Account Manager, PO#, Status (Approved/Pending/Lost)
                              - - Status badges and counters (Approved: X, Pending: X, Lost: X)
                                - - Filters: Year, Sector, Company, Account Manager
                                  - - Search within proposals
                                    - - Link proposals to projects upon approval
                                     
                                      - ### 5. Projects Module
                                      - - Create and manage projects
                                        - - Fields: Year, Project Number, Title, Client, Project Manager, Start Date, End Date, Project Type (Billable/Non-Billable), Status (Active/Closed)
                                          - - Filters: Year, Project Type, Client, Project Manager, Status
                                            - - Sub-levels: Deliverables > Milestones > Tasks > Roles
                                              - - Budget hours per task/role
                                                - - Link to proposals
                                                 
                                                  - ### 6. Contacts & Companies (CRM)
                                                  - - Contacts list: Name, Company, Region, Sector, Phone, Email
                                                    - - View Companies mode
                                                      - - Filters: Region, Sector, Company, Contact Type
                                                        - - Add/Edit/Delete contacts and companies
                                                          - - Company fields: Name, Sector, Region, Website, Address
                                                            - - Contact types management (configurable)
                                                             
                                                              - ### 7. Timesheet Module
                                                              - - Weekly timesheet view (Mon–Fri tabs)
                                                                - - Select project, deliverable, milestone, task
                                                                  - - Enter hours, add notes
                                                                    - - Submit timesheet for approval
                                                                      - - View YTD time-off (PTO Hours, Unpaid, Remote Work)
                                                                        - - Calendar view integration
                                                                         
                                                                          - ### 8. My Tasks
                                                                          - - View tasks assigned to current user
                                                                            - - Filter: Active / Completed / All
                                                                              - - Columns: Project, Deliverable, Milestone, Task, Role, Start-End, Actual Hours, Budget Hours, Budget Consumption %, Notes
                                                                                - - Update task notes and actual hours
                                                                                 
                                                                                  - ### 9. Approval Center
                                                                                  - Four tabs:
                                                                                  - - **Timesheet Submissions**: Review, approve, reject employee timesheets with admin console (filter by status, employee, approver, date range)
                                                                                    - - **Time Off Requests**: Approve/reject time off, view PTO balance
                                                                                      - - **Remote Work Requests**: Approve/reject remote work requests
                                                                                        - - **Expense Requests**: Review and approve expense submissions
                                                                                         
                                                                                          - ### 10. Project Schedule
                                                                                          - Three views:
                                                                                          - - **Project Schedule**: Gantt-style chart filtered by project/employee, showing tasks with Active (blue), Overdue (red), Completed (green) indicators
                                                                                            - - **Employee Schedule**: View schedule by employee
                                                                                              - - **Resources Dashboard**: Utilization metrics per employee
                                                                                               
                                                                                                - ### 11. Invoicing
                                                                                                - - Create and manage invoices linked to projects
                                                                                                  - - Invoice line items tied to project deliverables/milestones
                                                                                                    - - Invoice statuses: Draft, Sent, Paid, Overdue
                                                                                                      - - PDF generation
                                                                                                        - - Email invoice to client
                                                                                                         
                                                                                                          - ### 12. Administration Module
                                                                                                          - - **User Admin Console**: Create, edit, deactivate users; assign roles
                                                                                                            - - **Group Admin Console**: Create permission groups
                                                                                                              - - **Project Admin Console**: Manage project types, deliverable templates
                                                                                                                - - **Timesheet Admin Console**: Period management, approval chains
                                                                                                                  - - **Approval Settings**: Configure approvers for Timesheets, Time Off, Remote Work, Expense
                                                                                                                    - - **PTO Administration**: Employee PTO policies, time off logs, holiday calendar management
                                                                                                                      - - **Schedule of Fees**: Rate management per role/project type
                                                                                                                        - - **System Templates**: Deliverable template library
                                                                                                                          - - **Miscellaneous Settings**: Sectors, Regions, Contact Types, Work Types
                                                                                                                            - - **Activity Reports**: User activity logs, usage metrics by market/region/user type
                                                                                                                              - - **Email Integration**: SMTP configuration
                                                                                                                               
                                                                                                                                - ---
                                                                                                                                
                                                                                                                                ## Database Architecture (Summary)
                                                                                                                                See DATABASE_SCHEMA.md for full schema.
                                                                                                                                
                                                                                                                                Key tables:
                                                                                                                                - users, roles, user_roles
                                                                                                                                - - companies, contacts, contact_types, sectors, regions
                                                                                                                                  - - proposals, proposal_types
                                                                                                                                    - - projects, deliverables, milestones, tasks, task_roles
                                                                                                                                      - - timesheets, timesheet_entries
                                                                                                                                        - - approvals, approval_settings
                                                                                                                                          - - pto_policies, time_off_requests, holidays
                                                                                                                                            - - invoices, invoice_items
                                                                                                                                              - - schedule_of_fees
                                                                                                                                                - - system_settings, activity_logs
                                                                                                                                                 
                                                                                                                                                  - ---
                                                                                                                                                  
                                                                                                                                                  ## Installation Requirements
                                                                                                                                                  - PHP 8.2+ with extensions: PDO, PDO_MySQL, mbstring, openssl, tokenizer, xml, ctype, json, bcmath
                                                                                                                                                  - - MySQL 8.0+
                                                                                                                                                    - - Composer
                                                                                                                                                      - - Web Server: IIS (with URL Rewrite module) or Apache with mod_rewrite
                                                                                                                                                        - - 50MB disk space minimum
                                                                                                                                                         
                                                                                                                                                          - ---
                                                                                                                                                          
                                                                                                                                                          ## Development Phases
                                                                                                                                                          
                                                                                                                                                          ### Phase 1 — Foundation (Session 1-2)
                                                                                                                                                          - [x] System analysis
                                                                                                                                                          - [ ] - [x] GitHub repo setup
                                                                                                                                                          - [ ] - [x] Project planning documents
                                                                                                                                                          - [ ] - [ ] Database schema
                                                                                                                                                          - [ ] - [ ] Setup wizard
                                                                                                                                                          - [ ] - [ ] Laravel project scaffold
                                                                                                                                                          - [ ] - [ ] Auth system
                                                                                                                                                         
                                                                                                                                                          - [ ] ### Phase 2 — Core Modules (Session 3-5)
                                                                                                                                                          - [ ] - [ ] Dashboard (all 4 views)
                                                                                                                                                          - [ ] - [ ] User management
                                                                                                                                                          - [ ] - [ ] Contacts & Companies
                                                                                                                                                          - [ ] - [ ] Administration settings
                                                                                                                                                         
                                                                                                                                                          - [ ] ### Phase 3 — Operations (Session 6-8)
                                                                                                                                                          - [ ] - [ ] Proposals module
                                                                                                                                                          - [ ] - [ ] Projects module (with deliverables/tasks)
                                                                                                                                                          - [ ] - [ ] Timesheet module
                                                                                                                                                          - [ ] - [ ] My Tasks
                                                                                                                                                         
                                                                                                                                                          - [ ] ### Phase 4 — Approvals & Scheduling (Session 9-10)
                                                                                                                                                          - [ ] - [ ] Approval Center (all 4 tabs)
                                                                                                                                                          - [ ] - [ ] Project Schedule (Gantt)
                                                                                                                                                          - [ ] - [ ] PTO & HR features
                                                                                                                                                         
                                                                                                                                                          - [ ] ### Phase 5 — Financial (Session 11-12)
                                                                                                                                                          - [ ] - [ ] Invoicing module
                                                                                                                                                          - [ ] - [ ] Financial dashboard
                                                                                                                                                          - [ ] - [ ] KPI dashboard
                                                                                                                                                          - [ ] - [ ] Reports
                                                                                                                                                         
                                                                                                                                                          - [ ] ### Phase 6 — Polish & Deploy (Session 13-14)
                                                                                                                                                          - [ ] - [ ] Setup wizard completion
                                                                                                                                                          - [ ] - [ ] PDF generation
                                                                                                                                                          - [ ] - [ ] Email notifications
                                                                                                                                                          - [ ] - [ ] Windows Server deployment docs
                                                                                                                                                          - [ ] - [ ] Testing and bug fixes
                                                                                                                                                         
                                                                                                                                                          - [ ] ---
                                                                                                                                                         
                                                                                                                                                          - [ ] ## Naming Conventions
                                                                                                                                                          - [ ] - Controllers: PascalCase (e.g., ProposalController)
                                                                                                                                                          - [ ] - Models: PascalCase singular (e.g., Project, Timesheet)
                                                                                                                                                          - [ ] - Views: snake_case (e.g., project_list.blade.php)
                                                                                                                                                          - [ ] - Database tables: snake_case plural (e.g., timesheet_entries)
                                                                                                                                                          - [ ] - Routes: kebab-case (e.g., /project-schedule)
                                                                                                                                                         
                                                                                                                                                          - [ ] ---
                                                                                                                                                         
                                                                                                                                                          - [ ] *Last updated: Session 1 — 2026-02-20*
                                                                                                                                                          - [ ] 
