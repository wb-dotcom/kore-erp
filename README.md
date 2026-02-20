# Kore ERP

**A self-hosted, installable ERP web application built with PHP + MySQL for Windows Server.**

Redeveloped from the K5 KORE proprietary system with a full guided setup wizard, modular architecture, and a clean Bootstrap 5 UI.

---

## Quick Start (After Cloning)

1. Place the project on your Windows Server (IIS or Apache/XAMPP)
2. 2. Point your web server document root to `/public`
   3. 3. Navigate to `/install/index.php` in your browser
      4. 4. Follow the 6-step setup wizard
         5. 5. **Delete the `/install` folder** after installation for security
           
            6. ---
           
            7. ## Project Documents
           
            8. | Document | Description |
            9. |---|---|
            10. | [PROJECT_README.md](PROJECT_README.md) | Full redevelopment plan, module specs, phases, tech stack |
            11. | [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) | Complete MySQL schema for all modules |
            12. | [PROGRESS.md](PROGRESS.md) | Session-by-session progress tracker — start here each session |
           
            13. ---
           
            14. ## Tech Stack
           
            15. - **Backend:** PHP 8.2+ / Laravel 11
                - - **Frontend:** Blade + Bootstrap 5 + Chart.js + FullCalendar.js
                  - - **Database:** MySQL 8.0
                    - - **Server:** Windows Server (IIS with URL Rewrite, or Apache)
                     
                      - ---

                      ## Modules

                      Dashboard · Proposals · Projects · Contacts/CRM · Timesheet · My Tasks · Approval Center · Project Schedule · Invoicing · Administration · User Auth

                      ---

                      ## Status: Phase 1 — Foundation In Progress

                      See [PROGRESS.md](PROGRESS.md) for current status and what to build next.
                      
