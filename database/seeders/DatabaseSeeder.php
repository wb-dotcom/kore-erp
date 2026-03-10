<?php

namespace Database\Seeders;

use App\Models\ContactType;
use App\Models\ProjectStatus;
use App\Models\ProjectType;
use App\Models\ProposalStatus;
use App\Models\Region;
use App\Models\Role;
use App\Models\ScheduleOfFee;
use App\Models\Sector;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WorkType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a fresh Kore ERP installation with all required reference data.
 *
 * Run with: php artisan db:seed
 *
 * Idempotent: uses firstOrCreate() so running multiple times is safe.
 * Does NOT truncate tables — existing data is preserved.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Kore ERP reference data…');

        $this->seedRoles();
        $this->seedProjectStatuses();
        $this->seedProposalStatuses();
        $this->seedProjectTypes();
        $this->seedSectors();
        $this->seedWorkTypes();
        $this->seedRegions();
        $this->seedContactTypes();
        $this->seedScheduleOfFees();
        $this->seedSystemSettings();
        $this->seedAdminUser();

        $this->command->info('✓ Seeding complete.');
    }

    // ── Roles ──────────────────────────────────────────────────────────────────

    private function seedRoles(): void
    {
        $roles = [
            ['name' => 'Admin',    'description' => 'Full system access — manage users, settings, all data'],
            ['name' => 'Manager',  'description' => 'Manage projects, approve timesheets and time-off requests'],
            ['name' => 'Employee', 'description' => 'Standard access — timesheets, tasks, personal data only'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }

        $this->command->line('  ✓ Roles seeded (' . count($roles) . ')');
    }

    // ── Project Statuses ───────────────────────────────────────────────────────

    private function seedProjectStatuses(): void
    {
        $statuses = [
            ['name' => 'In Progress', 'color' => '#3b82f6'],
            ['name' => 'On Hold',     'color' => '#f59e0b'],
            ['name' => 'Complete',    'color' => '#22c55e'],
            ['name' => 'Cancelled',   'color' => '#ef4444'],
            ['name' => 'Draft',       'color' => '#9ca3af'],
        ];

        foreach ($statuses as $status) {
            ProjectStatus::firstOrCreate(['name' => $status['name']], $status);
        }

        $this->command->line('  ✓ Project statuses seeded (' . count($statuses) . ')');
    }

    // ── Proposal Statuses ──────────────────────────────────────────────────────

    private function seedProposalStatuses(): void
    {
        $statuses = [
            ['name' => 'Draft'],
            ['name' => 'Submitted'],
            ['name' => 'Under Review'],
            ['name' => 'Approved'],
            ['name' => 'Rejected'],
            ['name' => 'Withdrawn'],
        ];

        foreach ($statuses as $status) {
            ProposalStatus::firstOrCreate(['name' => $status['name']], $status);
        }

        $this->command->line('  ✓ Proposal statuses seeded (' . count($statuses) . ')');
    }

    // ── Project Types ──────────────────────────────────────────────────────────

    private function seedProjectTypes(): void
    {
        $types = [
            ['name' => 'New Construction',      'description' => 'Ground-up new building projects'],
            ['name' => 'Renovation',             'description' => 'Renovation and retrofit of existing structures'],
            ['name' => 'Interior Design',        'description' => 'Interior fit-out and design'],
            ['name' => 'Feasibility Study',      'description' => 'Pre-design feasibility and due diligence'],
            ['name' => 'Master Planning',        'description' => 'Campus or urban master planning'],
            ['name' => 'Landscape Architecture', 'description' => 'Exterior landscape and site design'],
            ['name' => 'Historic Preservation',  'description' => 'Heritage and conservation projects'],
            ['name' => 'Consulting',             'description' => 'Advisory and consulting engagements'],
        ];

        foreach ($types as $type) {
            ProjectType::firstOrCreate(['name' => $type['name']], $type);
        }

        $this->command->line('  ✓ Project types seeded (' . count($types) . ')');
    }

    // ── Sectors ────────────────────────────────────────────────────────────────

    private function seedSectors(): void
    {
        $sectors = [
            'Residential',
            'Commercial',
            'Healthcare',
            'Education',
            'Hospitality',
            'Industrial',
            'Cultural & Civic',
            'Mixed Use',
            'Retail',
            'Government',
            'Transportation',
        ];

        foreach ($sectors as $name) {
            Sector::firstOrCreate(['name' => $name]);
        }

        $this->command->line('  ✓ Sectors seeded (' . count($sectors) . ')');
    }

    // ── Work Types ─────────────────────────────────────────────────────────────

    private function seedWorkTypes(): void
    {
        $types = [
            ['name' => 'Architecture',     'description' => 'Full architectural services'],
            ['name' => 'Interior Design',  'description' => 'Interior design and specification'],
            ['name' => 'Landscape',        'description' => 'Landscape architecture services'],
            ['name' => 'Urban Planning',   'description' => 'Urban design and planning'],
            ['name' => 'Engineering',      'description' => 'Structural or MEP engineering'],
            ['name' => 'Consulting',       'description' => 'Expert advisory services'],
            ['name' => 'Construction Administration', 'description' => 'CA / site supervision'],
        ];

        foreach ($types as $type) {
            WorkType::firstOrCreate(['name' => $type['name']], $type);
        }

        $this->command->line('  ✓ Work types seeded (' . count($types) . ')');
    }

    // ── Regions ────────────────────────────────────────────────────────────────

    private function seedRegions(): void
    {
        $regions = [
            'Northeast',
            'Southeast',
            'Midwest',
            'Southwest',
            'West',
            'Northwest',
            'International',
        ];

        foreach ($regions as $name) {
            Region::firstOrCreate(['name' => $name]);
        }

        $this->command->line('  ✓ Regions seeded (' . count($regions) . ')');
    }

    // ── Contact Types ──────────────────────────────────────────────────────────

    private function seedContactTypes(): void
    {
        $types = [
            'Client',
            'Consultant',
            'Contractor',
            'Subcontractor',
            'Vendor / Supplier',
            'Authority Having Jurisdiction',
            'Media / Press',
            'Internal',
        ];

        foreach ($types as $name) {
            ContactType::firstOrCreate(['name' => $name]);
        }

        $this->command->line('  ✓ Contact types seeded (' . count($types) . ')');
    }

    // ── Schedule of Fees ───────────────────────────────────────────────────────

    private function seedScheduleOfFees(): void
    {
        // Only seed if no rates exist — admin can customise from the UI
        if (ScheduleOfFee::count() > 0) {
            $this->command->line('  — Schedule of fees already configured, skipping.');
            return;
        }

        $rates = [
            ['role_name' => 'Principal',                    'hourly_rate' => 275.00],
            ['role_name' => 'Senior Architect',             'hourly_rate' => 200.00],
            ['role_name' => 'Architect',                    'hourly_rate' => 165.00],
            ['role_name' => 'Associate Architect',          'hourly_rate' => 140.00],
            ['role_name' => 'Architectural Designer',       'hourly_rate' => 115.00],
            ['role_name' => 'Project Manager',              'hourly_rate' => 185.00],
            ['role_name' => 'Project Architect',            'hourly_rate' => 155.00],
            ['role_name' => 'Interior Designer',            'hourly_rate' => 130.00],
            ['role_name' => 'Senior Interior Designer',     'hourly_rate' => 160.00],
            ['role_name' => 'Landscape Architect',          'hourly_rate' => 145.00],
            ['role_name' => 'Urban Planner',                'hourly_rate' => 150.00],
            ['role_name' => 'BIM Coordinator',              'hourly_rate' => 120.00],
            ['role_name' => 'Technical Designer',           'hourly_rate' => 110.00],
            ['role_name' => 'Intern Architect',             'hourly_rate' => 90.00],
            ['role_name' => 'Administrative',               'hourly_rate' => 85.00],
        ];

        foreach ($rates as $rate) {
            ScheduleOfFee::create($rate);
        }

        $this->command->line('  ✓ Schedule of fees seeded (' . count($rates) . ' rates)');
    }

    // ── System Settings ────────────────────────────────────────────────────────

    private function seedSystemSettings(): void
    {
        $defaults = [
            'app_name'          => 'Kore ERP',
            'currency_symbol'   => '$',
            'currency_code'     => 'USD',
            'date_format'       => 'm/d/Y',
            'time_format'       => 'g:i A',
            'week_start'        => 'monday',
            'invoice_prefix'    => 'INV-',
            'fiscal_year_start' => '01',
            'ai_enabled'        => '1',
            'ai_default_model'  => 'llama3',
            'ai_enabled_models' => 'llama3',
            'ai_temperature'    => '0.7',
            'ai_context_window' => '4096',
        ];

        foreach ($defaults as $key => $value) {
            SystemSetting::firstOrCreate(['key' => $key], ['key' => $key, 'value' => $value]);
        }

        $this->command->line('  ✓ System settings seeded (' . count($defaults) . ' keys)');
    }

    // ── Admin User ─────────────────────────────────────────────────────────────

    private function seedAdminUser(): void
    {
        $adminRole = Role::where('name', 'Admin')->first();

        if (! $adminRole) {
            $this->command->warn('  — Could not find Admin role, skipping admin user seed.');
            return;
        }

        // Only create if NO users exist at all
        if (User::count() > 0) {
            $this->command->line('  — Users already exist, skipping admin user seed.');
            return;
        }

        User::create([
            'first_name' => 'Admin',
            'last_name'  => 'User',
            'email'      => 'admin@kore-erp.local',
            'password'   => Hash::make('ChangeMe123!'),
            'role_id'    => $adminRole->id,
            'is_active'  => true,
        ]);

        $this->command->line('  ✓ Admin user created: admin@kore-erp.local / ChangeMe123!');
        $this->command->warn('  ⚠ CHANGE THE ADMIN PASSWORD IMMEDIATELY after first login!');
    }
}
