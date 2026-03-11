<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\CalendarEvent;
use App\Models\Company;
use App\Models\Contact;
use App\Models\ExpenseRequest;
use App\Models\Holiday;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\Project;
use App\Models\ProjectCommunication;
use App\Models\ProjectDocument;
use App\Models\Proposal;
use App\Models\PtoPolicy;
use App\Models\ScheduleOfFee;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\Timesheet;
use App\Models\TimesheetEntry;
use App\Models\TimeOffRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Fetches structured ERP data and formats it as LLM-readable text blocks.
 *
 * Each method returns a compact, information-dense string block that fits
 * within the AI's context window. Numbers are specific; dates are formatted.
 * Risk flags use emoji markers so the LLM can easily identify them in context.
 *
 * Output conventions:
 *   ✅  completed / on track
 *   🔄  in progress
 *   ⚠️  at risk (>80% hours for <80% completion, or overdue)
 *   🚨  critical (over budget, past deadline)
 *   ⏳  not started
 */
class KoreDataTools
{
    private string $currency;
    private string $dateFormat;

    public function __construct()
    {
        $this->currency   = config('kore.currency_symbol', '$');
        $this->dateFormat = config('kore.date_format', 'M d, Y');
    }

    // ── Project Data ───────────────────────────────────────────────────────────

    /**
     * Full project summary with all phases, budget burn, and team.
     */
    public function getProjectSummary(int $projectId): string
    {
        $project = Project::with([
            'company', 'projectManager', 'projectType', 'status', 'phases',
        ])->find($projectId);

        if (! $project) {
            return "Project ID {$projectId} not found.";
        }

        $totalBudgetedHours = $project->phases->sum('estimated_hours');
        $totalBurnedHours   = $this->getProjectBurnedHours($projectId);
        $totalBilled        = Invoice::where('project_id', $projectId)->sum('total');
        $hoursPercent       = $totalBudgetedHours > 0
            ? round(($totalBurnedHours / $totalBudgetedHours) * 100, 1)
            : 0;

        $lines = [
            "PROJECT: {$project->project_number} — \"{$project->title}\"",
            "Status: {$project->status?->name} | Type: {$project->projectType?->name}",
            "Client: {$project->company?->name} | PM: {$project->projectManager?->full_name}",
            "Contract Budget: {$this->money($project->total_budget)} | Billed to Date: {$this->money($totalBilled)}",
            "Start: {$this->date($project->start_date)} | Est. End: {$this->date($project->end_date)}",
            "",
            "PHASES:",
        ];

        foreach ($project->phases->sortBy('phase_order') as $phase) {
            $burned    = $this->getPhaseBurnedHours($phase->id);
            $risk      = $this->phaseRiskFlag($phase, $burned);
            $burnPct   = $phase->estimated_hours > 0
                ? round(($burned / $phase->estimated_hours) * 100, 1) : 0;

            $lines[] = "  {$risk} {$phase->code} {$phase->name}: "
                . "{$phase->percent_complete}% complete | "
                . "{$this->money($phase->fixed_fee)} | "
                . "{$burned}/{$phase->estimated_hours} hrs ({$burnPct}% of budget)"
                . ($phase->planned_end_date ? " | Due: {$this->date($phase->planned_end_date)}" : "");
        }

        $lines[] = "";
        $lines[] = "LABOR SUMMARY: {$totalBurnedHours}/{$totalBudgetedHours} hrs total "
            . "({$hoursPercent}% of budget hours consumed)";

        if ($hoursPercent > 80) {
            $avgCompletion = $project->phases->avg('percent_complete') ?? 0;
            if ($avgCompletion < 70) {
                $lines[] = "⚠️  BUDGET RISK: {$hoursPercent}% of hours consumed with only "
                    . round($avgCompletion, 1) . "% average phase completion.";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Recent inbound emails for a project.
     */
    public function getProjectCommunications(int $projectId, int $limit = 5): string
    {
        $comms = ProjectCommunication::where('project_id', $projectId)
            ->orderByDesc('sent_at')
            ->limit($limit)
            ->get();

        if ($comms->isEmpty()) {
            return "No inbound email communications on record for this project.";
        }

        $lines = ["RECENT COMMUNICATIONS ({$comms->count()} shown):"];

        foreach ($comms as $c) {
            $date    = $c->sent_at ? $c->sent_at->format($this->dateFormat) : 'unknown date';
            $preview = mb_substr(strip_tags($c->body_text ?? $c->subject ?? ''), 0, 120);
            $attCount = $c->documents()->count();
            $att      = $attCount > 0 ? " [{$attCount} attachment(s)]" : '';
            $lines[]  = "  • {$date} from {$c->from_email}: \"{$c->subject}\"{$att}";
            if ($preview) {
                $lines[] = "    {$preview}…";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Timesheet burn by role for a project.
     */
    public function getProjectTimesheetBreakdown(int $projectId): string
    {
        $entries = DB::table('timesheet_entries as te')
            ->join('timesheets as t', 't.id', '=', 'te.timesheet_id')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->join('project_phases as pp', 'pp.id', '=', 'te.project_phase_id')
            ->select(
                'u.role',
                DB::raw('SUM(te.hours) as total_hours'),
                DB::raw('COUNT(DISTINCT t.user_id) as staff_count')
            )
            ->where('te.project_id', $projectId)
            ->groupBy('u.role')
            ->orderByDesc('total_hours')
            ->get();

        if ($entries->isEmpty()) {
            return "No timesheet entries recorded for this project.";
        }

        $totalHours = $entries->sum('total_hours');
        $lines      = ["LABOR BY ROLE (total {$totalHours} hrs):"];

        foreach ($entries as $row) {
            $pct     = $totalHours > 0 ? round(($row->total_hours / $totalHours) * 100) : 0;
            $lines[] = "  {$row->role}: {$row->total_hours} hrs ({$pct}%) — {$row->staff_count} staff";
        }

        return implode("\n", $lines);
    }

    /**
     * Invoice and payment status for a project.
     */
    public function getProjectInvoices(int $projectId): string
    {
        $invoices = Invoice::where('project_id', $projectId)
            ->orderByDesc('invoice_date')
            ->get();

        if ($invoices->isEmpty()) {
            return "No invoices issued for this project.";
        }

        $totalBilled  = $invoices->sum('total');
        $totalPaid    = $invoices->where('status', 'paid')->sum('total');
        $totalOverdue = $invoices->where('status', 'overdue')->sum('total');

        $lines = [
            "INVOICES: {$invoices->count()} total | {$this->money($totalBilled)} billed | "
            . "{$this->money($totalPaid)} paid | {$this->money($totalOverdue)} overdue",
        ];

        foreach ($invoices as $inv) {
            $flag    = $inv->status === 'overdue' ? '🚨 ' : ($inv->status === 'paid' ? '✅ ' : '🔄 ');
            $due     = $inv->due_date ? " due {$this->date($inv->due_date)}" : '';
            $lines[] = "  {$flag}{$inv->invoice_number}: {$this->money($inv->total)} — "
                . strtoupper($inv->status) . $due;
        }

        return implode("\n", $lines);
    }

    // ── Proposal Data ──────────────────────────────────────────────────────────

    /**
     * Proposal with fee worksheet summary.
     */
    public function getProposalSummary(int $proposalId): string
    {
        $proposal = Proposal::with([
            'company', 'status', 'workType', 'accountManager', 'lineItems',
        ])->find($proposalId);

        if (! $proposal) {
            return "Proposal ID {$proposalId} not found.";
        }

        $lines = [
            "PROPOSAL: {$proposal->ref} — \"{$proposal->title}\"",
            "Status: {$proposal->status?->name} | Client: {$proposal->company?->name}",
            "Work Type: {$proposal->workType?->name} | Account Manager: {$proposal->accountManager?->full_name}",
            "Billing: " . ucwords(str_replace('_', ' ', $proposal->billing_type))
                . " | Cycle: " . ucwords(str_replace('_', ' ', $proposal->billing_cycle))
                . " | Terms: Net {$proposal->payment_terms_days}",
            "Total Fee: {$this->money($proposal->total_fee)}",
        ];

        if ($proposal->lineItems->isNotEmpty()) {
            $byPhase = $proposal->lineItems->groupBy('phase_code');
            $lines[] = "";
            $lines[] = "FEE WORKSHEET:";

            foreach ($byPhase as $code => $items) {
                $phaseTotal = $items->sum('amount');
                $phaseHours = $items->sum('hours');
                $lines[]    = "  {$code}: {$this->money($phaseTotal)} | {$phaseHours} hrs";

                foreach ($items as $item) {
                    $lines[] = "    • {$item->deliverable} [{$item->role_name}]: "
                        . "{$item->hours} hrs × {$this->money($item->rate)}/hr = {$this->money($item->effective_amount)}";
                }
            }
        }

        if ($proposal->scope_of_work) {
            $lines[] = "";
            $lines[] = "SCOPE: " . mb_substr(strip_tags($proposal->scope_of_work), 0, 300) . '…';
        }

        return implode("\n", $lines);
    }

    // ── Proposal Pipeline ──────────────────────────────────────────────────────

    /**
     * Full proposal pipeline across all statuses — used when user asks about proposals.
     */
    public function getProposalsPipelineSummary(): string
    {
        $proposals = Proposal::with(['company', 'status', 'accountManager'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        if ($proposals->isEmpty()) {
            return "PROPOSALS: There are no proposals in the system. The database is empty.";
        }

        $byStatus = $proposals->groupBy(fn($p) => $p->status?->name ?? 'Unknown');
        $totalFee = $proposals->sum('total_fee');

        $lines = [
            "PROPOSALS PIPELINE ({$proposals->count()} total | {$this->money($totalFee)} total fee value):",
        ];

        foreach ($byStatus as $status => $group) {
            $statusTotal = $group->sum('total_fee');
            $lines[]     = "\n  {$status} ({$group->count()}) — {$this->money($statusTotal)}:";
            foreach ($group as $p) {
                $lines[] = "    • {$p->ref}: \"{$p->title}\" | Client: {$p->company?->name} | Fee: {$this->money($p->total_fee)}";
            }
        }

        return implode("\n", $lines);
    }

    // ── Portfolio Overview ─────────────────────────────────────────────────────

    /**
     * High-level firm portfolio summary for global context.
     * Designed to fit in ~300 tokens.
     */
    public function getPortfolioOverview(User $user): string
    {
        $activeProjects = Project::whereHas('status', fn($q) => $q->where('name', 'In Progress'))
            ->with(['status', 'projectManager'])
            ->orderByDesc('start_date')
            ->get();

        $totalProposals   = Proposal::count();
        $openProposals    = Proposal::whereHas('status', fn($q) => $q->whereIn('name', ['Draft', 'Submitted', 'Under Review']))->count();
        $approvedProposals = Proposal::whereHas('status', fn($q) => $q->where('name', 'Approved'))->count();
        $pendingInvoices  = Invoice::whereIn('status', ['sent', 'overdue'])->sum('total');
        $overdueInvoices  = Invoice::where('status', 'overdue')->count();

        $lines = [
            "PORTFOLIO OVERVIEW ({$this->date(now())})",
            "Active Projects: {$activeProjects->count()} | Total Proposals: {$totalProposals} (Open: {$openProposals}, Approved: {$approvedProposals})",
            "Outstanding Invoices: {$this->money($pendingInvoices)} ({$overdueInvoices} overdue)",
            "",
            "ACTIVE PROJECTS:",
        ];

        foreach ($activeProjects->take(8) as $p) {
            $burned   = $this->getProjectBurnedHours($p->id);
            $budgeted = $p->phases()->sum('estimated_hours');
            $burnPct  = $budgeted > 0 ? round(($burned / $budgeted) * 100) : 0;
            $flag     = $burnPct > 80 ? '⚠️ ' : '';
            $lines[]  = "  {$flag}{$p->project_number} \"{$p->title}\" | PM: "
                . ($p->projectManager?->full_name ?? 'Unassigned')
                . " | Budget: {$this->money($p->total_budget)} | Hours: {$burned}/{$budgeted} ({$burnPct}%)";
        }

        if ($activeProjects->count() > 8) {
            $lines[] = "  … and " . ($activeProjects->count() - 8) . " more active projects.";
        }

        return implode("\n", $lines);
    }

    /**
     * Tasks assigned to the current user or all overdue tasks.
     */
    public function getUserTasks(User $user, bool $overdueOnly = false): string
    {
        $query = TaskAssignment::with(['task.milestone.deliverable.project'])
            ->where('user_id', $user->id)
            ->whereHas('task', fn($q) => $q->whereIn('status', ['active', 'in_progress']));

        if ($overdueOnly) {
            $query->whereHas('task', fn($q) => $q->where('due_date', '<', now()));
        }

        $assignments = $query->orderBy(
            Task::select('due_date')->whereColumn('tasks.id', 'task_assignments.task_id')->limit(1)
        )->limit(15)->get();

        if ($assignments->isEmpty()) {
            return $overdueOnly
                ? "No overdue tasks for {$user->full_name}."
                : "No active tasks assigned to {$user->full_name}.";
        }

        $lines = [($overdueOnly ? "OVERDUE TASKS" : "MY TASKS") . " ({$user->full_name}):"];

        foreach ($assignments as $a) {
            $task    = $a->task;
            $project = $task->milestone?->deliverable?->project;
            $due     = $task->due_date ? $this->date($task->due_date) : 'no due date';
            $flag    = $task->due_date && $task->due_date < now() ? '🚨 ' : '';
            $lines[] = "  {$flag}[{$project?->project_number}] {$task->name} — due {$due}";
        }

        return implode("\n", $lines);
    }

    /**
     * Recent timesheet summary for a user.
     */
    public function getUserTimesheetSummary(User $user): string
    {
        $thisWeekStart = now()->startOfWeek();

        $weekHours = DB::table('timesheet_entries as te')
            ->join('timesheets as t', 't.id', '=', 'te.timesheet_id')
            ->where('t.user_id', $user->id)
            ->where('te.entry_date', '>=', $thisWeekStart)
            ->sum('te.hours');

        $byProject = DB::table('timesheet_entries as te')
            ->join('timesheets as t', 't.id', '=', 'te.timesheet_id')
            ->join('projects as p', 'p.id', '=', 'te.project_id')
            ->where('t.user_id', $user->id)
            ->where('te.entry_date', '>=', now()->subDays(30))
            ->select('p.project_number', 'p.title', DB::raw('SUM(te.hours) as total_hours'))
            ->groupBy('p.id', 'p.project_number', 'p.title')
            ->orderByDesc('total_hours')
            ->limit(5)
            ->get();

        $lines = [
            "TIMESHEET ({$user->full_name}): {$weekHours} hrs this week",
            "Last 30 days by project:",
        ];

        foreach ($byProject as $row) {
            $lines[] = "  {$row->project_number} \"{$row->title}\": {$row->total_hours} hrs";
        }

        return implode("\n", $lines);
    }

    // ── CRM Data ───────────────────────────────────────────────────────────────

    /**
     * All companies with sector, region, contact count, project count, and status.
     */
    public function getCompaniesSummary(): string
    {
        $companies = Company::with(['sector', 'region'])
            ->withCount(['contacts', 'projects'])
            ->orderBy('name')
            ->get();

        if ($companies->isEmpty()) {
            return "COMPANIES: No companies are in the system.";
        }

        $active   = $companies->where('is_active', true)->count();
        $inactive = $companies->where('is_active', false)->count();

        $lines = [
            "COMPANIES ({$companies->count()} total | {$active} active | {$inactive} inactive):",
        ];

        foreach ($companies as $co) {
            $status   = $co->is_active ? 'Active' : 'Inactive';
            $phone    = $co->phone ? " | Phone: {$co->phone}" : '';
            $website  = $co->website ? " | Web: {$co->website}" : '';
            $lines[]  = "  • {$co->name} [{$status}]"
                . " | Sector: " . ($co->sector?->name ?? '—')
                . " | Region: " . ($co->region?->name ?? '—')
                . " | Contacts: {$co->contacts_count} | Projects: {$co->projects_count}"
                . $phone . $website;
        }

        return implode("\n", $lines);
    }

    /**
     * All contacts for a specific company, or all contacts if no company given.
     */
    public function getCompanyContacts(?int $companyId = null): string
    {
        $query = Contact::with(['company', 'contactType'])
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($companyId) {
            $query->where('company_id', $companyId);
        } else {
            $query->limit(50);
        }

        $contacts = $query->get();

        if ($contacts->isEmpty()) {
            return $companyId
                ? "No contacts found for this company."
                : "CONTACTS: No contacts are in the system.";
        }

        $label = $companyId
            ? "CONTACTS for " . ($contacts->first()->company?->name ?? "Company #{$companyId}")
            : "ALL CONTACTS ({$contacts->count()} shown)";

        $lines = ["{$label}:"];

        foreach ($contacts as $c) {
            $status  = $c->is_active ? 'Active' : 'Inactive';
            $phone   = $c->business_phone ?? $c->mobile_phone ?? null;
            $phoneStr = $phone ? " | Phone: {$phone}" : '';
            $company  = $companyId ? '' : " | Company: " . ($c->company?->name ?? '—');
            $lines[]  = "  • {$c->full_name} [{$status}]"
                . " | Title: " . ($c->title ?? '—')
                . " | Type: " . ($c->contactType?->name ?? '—')
                . " | Email: " . ($c->email ?? '—')
                . $phoneStr
                . $company;
        }

        return implode("\n", $lines);
    }

    // ── Approvals ──────────────────────────────────────────────────────────────

    /**
     * All pending approvals, grouped by type — for the current approver or firm-wide.
     */
    public function getPendingApprovals(?User $user = null): string
    {
        $query = Approval::with('approver')->where('status', 'pending');
        if ($user) {
            $query->where('approver_id', $user->id);
        }
        $pending = $query->orderBy('created_at')->get();

        if ($pending->isEmpty()) {
            $scope = $user ? "for {$user->full_name}" : 'firm-wide';
            return "APPROVALS: No pending approvals {$scope}.";
        }

        $byType = $pending->groupBy('approval_type');
        $lines  = ["PENDING APPROVALS ({$pending->count()} total):"];

        foreach ($byType as $type => $group) {
            $label = ucwords(str_replace('_', ' ', $type));
            $lines[] = "\n  {$label} ({$group->count()}):";
            foreach ($group->take(10) as $a) {
                $approver = $a->approver?->full_name ?? 'Unassigned';
                $age      = Carbon::parse($a->created_at)->diffForHumans();
                $lines[]  = "    ⏳ ID #{$a->reference_id} — approver: {$approver} — submitted {$age}";
            }
            if ($group->count() > 10) {
                $lines[] = "    … and " . ($group->count() - 10) . " more.";
            }
        }

        return implode("\n", $lines);
    }

    // ── Time Off / PTO ─────────────────────────────────────────────────────────

    /**
     * PTO balance and upcoming approved/pending requests for a user.
     */
    public function getUserPtoSummary(User $user): string
    {
        $policy = PtoPolicy::where('user_id', $user->id)
            ->orderByDesc('effective_date')
            ->first();

        $usedHours = TimeOffRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereYear('start_date', now()->year)
            ->sum('hours');

        $pendingHours = TimeOffRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->sum('hours');

        $lines = ["PTO SUMMARY — {$user->full_name} (" . now()->year . "):"];

        if ($policy) {
            $total     = (float) $policy->annual_pto_hours + (float) $policy->carry_over_hours;
            $remaining = $total - $usedHours;
            $lines[]   = "  Annual Allotment: {$policy->annual_pto_hours} hrs"
                . ($policy->carry_over_hours > 0 ? " + {$policy->carry_over_hours} carry-over" : '');
            $lines[]   = "  Used: {$usedHours} hrs | Remaining: {$remaining} hrs"
                . ($pendingHours > 0 ? " | Pending approval: {$pendingHours} hrs" : '');
        } else {
            $lines[] = "  No PTO policy on file. Used this year: {$usedHours} hrs.";
        }

        // Upcoming approved requests
        $upcoming = TimeOffRequest::where('user_id', $user->id)
            ->whereIn('status', ['approved', 'pending'])
            ->where('end_date', '>=', now()->toDateString())
            ->orderBy('start_date')
            ->limit(5)
            ->get();

        if ($upcoming->isNotEmpty()) {
            $lines[] = "  Upcoming Requests:";
            foreach ($upcoming as $req) {
                $flag    = $req->status === 'approved' ? '✅' : '⏳';
                $type    = ucfirst($req->request_type);
                $lines[] = "    {$flag} {$type}: {$this->date($req->start_date)} – {$this->date($req->end_date)} ({$req->hours} hrs) [{$req->status}]";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * All pending time-off requests across the firm.
     */
    public function getPendingTimeOffRequests(): string
    {
        $requests = TimeOffRequest::with('user')
            ->where('status', 'pending')
            ->orderBy('start_date')
            ->get();

        if ($requests->isEmpty()) {
            return "TIME OFF: No pending time-off requests.";
        }

        $lines = ["PENDING TIME-OFF REQUESTS ({$requests->count()}):"];
        foreach ($requests as $req) {
            $type    = ucfirst($req->request_type);
            $lines[] = "  ⏳ {$req->user?->full_name}: {$type} — {$this->date($req->start_date)} – {$this->date($req->end_date)} ({$req->hours} hrs)";
        }

        return implode("\n", $lines);
    }

    /**
     * Who is out (approved time-off) in the next 60 days.
     */
    public function getTeamTimeOffCalendar(): string
    {
        $approved = TimeOffRequest::with('user')
            ->where('status', 'approved')
            ->where('end_date', '>=', now()->toDateString())
            ->where('start_date', '<=', now()->addDays(60)->toDateString())
            ->orderBy('start_date')
            ->get();

        if ($approved->isEmpty()) {
            return "TIME OFF CALENDAR: No approved time-off in the next 60 days.";
        }

        $lines = ["TEAM TIME-OFF CALENDAR (next 60 days, approved only):"];
        foreach ($approved as $req) {
            $type    = ucfirst($req->request_type);
            $lines[] = "  ✅ {$req->user?->full_name}: {$type} — {$this->date($req->start_date)} – {$this->date($req->end_date)} ({$req->hours} hrs)";
        }

        return implode("\n", $lines);
    }

    // ── Expenses ───────────────────────────────────────────────────────────────

    /**
     * All pending expense requests across the firm.
     */
    public function getPendingExpenses(): string
    {
        $expenses = ExpenseRequest::with(['user', 'project'])
            ->where('status', 'pending')
            ->orderByDesc('expense_date')
            ->get();

        if ($expenses->isEmpty()) {
            return "EXPENSES: No pending expense requests.";
        }

        $total  = $expenses->sum('amount');
        $lines  = ["PENDING EXPENSES ({$expenses->count()} | {$this->money($total)} total):"];

        foreach ($expenses as $exp) {
            $project = $exp->project ? "[{$exp->project->project_number}]" : '[No project]';
            $lines[] = "  ⏳ {$exp->user?->full_name}: {$this->money($exp->amount)} — "
                . ucfirst($exp->category) . " on {$this->date($exp->expense_date)} {$project}"
                . ($exp->description ? " — \"{$exp->description}\"" : '');
        }

        return implode("\n", $lines);
    }

    /**
     * Expense summary for a specific project.
     */
    public function getProjectExpenses(int $projectId): string
    {
        $expenses = ExpenseRequest::with('user')
            ->where('project_id', $projectId)
            ->orderByDesc('expense_date')
            ->get();

        if ($expenses->isEmpty()) {
            return "No expenses recorded for this project.";
        }

        $byCategory = $expenses->groupBy('category');
        $total      = $expenses->sum('amount');
        $approved   = $expenses->where('status', 'approved')->sum('amount');
        $pending    = $expenses->where('status', 'pending')->sum('amount');

        $lines = ["PROJECT EXPENSES: {$this->money($total)} total | {$this->money($approved)} approved | {$this->money($pending)} pending"];

        foreach ($byCategory as $category => $group) {
            $catTotal = $group->sum('amount');
            $lines[]  = "  " . ucfirst($category) . ": {$this->money($catTotal)} ({$group->count()} items)";
        }

        return implode("\n", $lines);
    }

    // ── Programs ───────────────────────────────────────────────────────────────

    /**
     * All programs with project counts and budget rollup.
     */
    public function getActivePrograms(): string
    {
        $programs = Program::with(['company'])
            ->withCount('projects')
            ->orderBy('status')
            ->orderBy('name')
            ->get();

        if ($programs->isEmpty()) {
            return "PROGRAMS: No programs in the system.";
        }

        $lines = ["PROGRAMS ({$programs->count()} total):"];

        foreach ($programs as $prog) {
            $status      = ucfirst($prog->status);
            $statusFlag  = match ($prog->status) {
                'active'    => '🔄',
                'completed' => '✅',
                'on_hold'   => '⚠️ ',
                'cancelled' => '🚫',
                default     => '⏳',
            };
            $contracted = $prog->total_contracted_fee;
            $budgetPct  = $prog->global_budget > 0
                ? round(($contracted / $prog->global_budget) * 100, 1) : 0;

            $lines[] = "  {$statusFlag} [{$prog->code}] {$prog->name}"
                . " | Client: " . ($prog->company?->name ?? '—')
                . " | Status: {$status} | Projects: {$prog->projects_count}"
                . " | Budget: {$this->money($prog->global_budget)} | Contracted: {$this->money($contracted)} ({$budgetPct}%)";
        }

        return implode("\n", $lines);
    }

    /**
     * Full detail of a single program including all projects.
     */
    public function getProgramSummary(int $programId): string
    {
        $program = Program::with(['company', 'projects.status', 'projects.projectManager'])
            ->find($programId);

        if (! $program) {
            return "Program ID {$programId} not found.";
        }

        $contractedFee = $program->total_contracted_fee;
        $budgetPct     = $program->global_budget > 0
            ? round(($contractedFee / $program->global_budget) * 100, 1) : 0;

        $lines = [
            "PROGRAM: [{$program->code}] {$program->name}",
            "Client: " . ($program->company?->name ?? '—') . " | Status: " . ucfirst($program->status),
            "Global Budget: {$this->money($program->global_budget)} | Contracted: {$this->money($contractedFee)} ({$budgetPct}%)",
            "Dates: {$this->date($program->start_date)} → {$this->date($program->end_date)}",
            "",
            "PROJECTS ({$program->projects->count()}):",
        ];

        foreach ($program->projects as $p) {
            $lines[] = "  • [{$p->project_number}] \"{$p->title}\" — {$p->status?->name}"
                . " | PM: " . ($p->projectManager?->full_name ?? 'Unassigned')
                . " | Budget: {$this->money($p->total_budget)}";
        }

        if ($program->description) {
            $lines[] = "";
            $lines[] = "DESCRIPTION: " . mb_substr(strip_tags($program->description), 0, 200);
        }

        return implode("\n", $lines);
    }

    // ── Schedule of Fees ───────────────────────────────────────────────────────

    /**
     * Current billing/cost rates by role.
     */
    public function getScheduleOfFees(): string
    {
        $fees = ScheduleOfFee::with('projectType')
            ->where(function ($q) {
                $q->whereNull('effective_date')
                  ->orWhere('effective_date', '<=', now()->toDateString());
            })
            ->orderBy('role_name')
            ->orderByDesc('effective_date')
            ->get()
            ->unique('role_name'); // keep most recent per role

        if ($fees->isEmpty()) {
            return "SCHEDULE OF FEES: No rates on file.";
        }

        $lines = ["SCHEDULE OF FEES (current rates):"];
        foreach ($fees as $fee) {
            $type    = $fee->projectType?->name ? " [{$fee->projectType->name}]" : '';
            $eff     = $fee->effective_date ? " (eff. {$this->date($fee->effective_date)})" : '';
            $lines[] = "  {$fee->role_name}{$type}: \${$fee->hourly_rate}/hr{$eff}";
        }

        return implode("\n", $lines);
    }

    // ── Project Documents ──────────────────────────────────────────────────────

    /**
     * Document inventory for a project — count by type, total size, index status.
     */
    public function getProjectDocumentSummary(int $projectId): string
    {
        $docs = ProjectDocument::where('project_id', $projectId)
            ->orderByDesc('created_at')
            ->get();

        if ($docs->isEmpty()) {
            return "No documents on file for this project.";
        }

        $totalSize = $docs->sum('file_size_bytes');
        $indexed   = $docs->where('is_indexed', true)->count();
        $byType    = $docs->groupBy('document_type');

        $lines = [
            "DOCUMENTS ({$docs->count()} files | " . $this->fileSize($totalSize) . " | {$indexed} indexed for AI search):",
        ];

        foreach ($byType as $type => $group) {
            $lines[] = "  " . ucfirst($type) . ": {$group->count()} file(s)";
            foreach ($group->take(5) as $doc) {
                $size    = $doc->file_size_bytes ? " (" . $this->fileSize($doc->file_size_bytes) . ")" : '';
                $aiFlag  = $doc->is_indexed ? ' [AI-indexed]' : '';
                $lines[] = "    • {$doc->original_filename}{$size}{$aiFlag}";
            }
        }

        return implode("\n", $lines);
    }

    // ── Project Tasks (enhanced) ────────────────────────────────────────────────

    /**
     * Full task breakdown for a project — by status with assignees.
     */
    public function getProjectTaskSummary(int $projectId): string
    {
        $tasks = Task::whereHas('milestone.deliverable', fn($q) => $q->where('project_id', $projectId))
            ->with(['assignments.user', 'milestone.deliverable'])
            ->get();

        if ($tasks->isEmpty()) {
            return "No tasks defined for this project.";
        }

        $byStatus = $tasks->groupBy('status');
        $overdue  = $tasks->filter(fn($t) => $t->end_date && $t->end_date < now() && $t->status !== 'completed');

        $lines = ["TASKS ({$tasks->count()} total" . ($overdue->count() > 0 ? " | 🚨 {$overdue->count()} overdue" : '') . "):"];

        $statusOrder = ['in_progress', 'active', 'pending', 'blocked', 'completed'];
        foreach ($statusOrder as $s) {
            if (! isset($byStatus[$s])) continue;
            $group   = $byStatus[$s];
            $label   = ucwords(str_replace('_', ' ', $s));
            $lines[] = "\n  {$label} ({$group->count()}):";
            foreach ($group->take(8) as $task) {
                $assignees = $task->assignments->map(fn($a) => $a->user?->full_name)->filter()->implode(', ');
                $due       = $task->end_date ? " due {$this->date($task->end_date)}" : '';
                $flag      = $task->end_date && $task->end_date < now() && $s !== 'completed' ? '🚨 ' : '';
                $lines[]   = "    {$flag}{$task->name}{$due}" . ($assignees ? " — {$assignees}" : '');
            }
            if ($group->count() > 8) {
                $lines[] = "    … and " . ($group->count() - 8) . " more.";
            }
        }

        return implode("\n", $lines);
    }

    // ── Holidays ───────────────────────────────────────────────────────────────

    /**
     * Upcoming holidays (next 90 days by default).
     */
    public function getUpcomingHolidays(int $days = 90): string
    {
        $holidays = Holiday::where('holiday_date', '>=', now()->toDateString())
            ->where('holiday_date', '<=', now()->addDays($days)->toDateString())
            ->orderBy('holiday_date')
            ->get();

        if ($holidays->isEmpty()) {
            return "No holidays in the next {$days} days.";
        }

        $lines = ["UPCOMING HOLIDAYS (next {$days} days):"];
        foreach ($holidays as $h) {
            $daysAway = (int) now()->diffInDays($h->holiday_date, false);
            $in       = $daysAway === 0 ? 'Today' : "in {$daysAway} day(s)";
            $lines[]  = "  🗓️ {$h->name} — {$this->date($h->holiday_date)} ({$in})";
        }

        return implode("\n", $lines);
    }

    // ── Firm Utilization ───────────────────────────────────────────────────────

    /**
     * Firm-wide timesheet utilization summary for a rolling window.
     */
    public function getFirmUtilizationSummary(int $days = 30): string
    {
        $since = now()->subDays($days)->toDateString();

        $byUser = DB::table('timesheet_entries as te')
            ->join('timesheets as t', 't.id', '=', 'te.timesheet_id')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->select(
                'u.id',
                DB::raw("CONCAT(u.first_name, ' ', u.last_name) as name"),
                DB::raw('SUM(te.hours) as total_hours')
            )
            ->where('te.entry_date', '>=', $since)
            ->groupBy('u.id', 'u.first_name', 'u.last_name')
            ->orderByDesc('total_hours')
            ->get();

        if ($byUser->isEmpty()) {
            return "UTILIZATION: No timesheet entries in the last {$days} days.";
        }

        $totalHours = $byUser->sum('total_hours');
        $avgPerUser = round($totalHours / max($byUser->count(), 1), 1);
        $workdays   = max(1, now()->diffInWeekdays(now()->subDays($days)));
        $expectedPerUser = $workdays * 8;

        $lines = [
            "FIRM UTILIZATION (last {$days} days | {$byUser->count()} staff active):",
            "Total Logged Hours: {$totalHours} | Avg per person: {$avgPerUser} hrs",
        ];

        foreach ($byUser as $row) {
            $pct     = $expectedPerUser > 0 ? round(($row->total_hours / $expectedPerUser) * 100) : 0;
            $flag    = $pct >= 90 ? '✅' : ($pct >= 60 ? '🔄' : '⚠️ ');
            $lines[] = "  {$flag} {$row->name}: {$row->total_hours} hrs ({$pct}% utilization)";
        }

        return implode("\n", $lines);
    }

    /**
     * Timesheet submission status — who has/hasn't submitted this period.
     */
    public function getTimesheetSubmissionStatus(): string
    {
        $users = User::where('is_active', true)->orderBy('last_name')->get(['id', 'first_name', 'last_name']);

        $thisWeekStart = now()->startOfWeek()->toDateString();

        $submitted = DB::table('timesheets')
            ->where('period_start', '>=', $thisWeekStart)
            ->whereNotNull('submitted_at')
            ->pluck('user_id')
            ->flip();

        $notSubmitted = $users->filter(fn($u) => ! isset($submitted[$u->id]));
        $hasSubmitted = $users->filter(fn($u) => isset($submitted[$u->id]));

        $lines = ["TIMESHEET STATUS (week of " . now()->startOfWeek()->format('M d') . "):"];
        $lines[] = "  ✅ Submitted: {$hasSubmitted->count()} | ⚠️  Not yet: {$notSubmitted->count()}";

        if ($notSubmitted->isNotEmpty()) {
            $lines[] = "\n  Pending submission:";
            foreach ($notSubmitted->take(15) as $u) {
                $lines[] = "    ⚠️  {$u->first_name} {$u->last_name}";
            }
        }

        return implode("\n", $lines);
    }

    // ── Global Invoice Dashboard ────────────────────────────────────────────────

    /**
     * Firm-wide invoice summary across all statuses.
     */
    public function getGlobalInvoiceSummary(): string
    {
        $invoices = Invoice::with('project.company')
            ->orderByDesc('invoice_date')
            ->limit(50)
            ->get();

        if ($invoices->isEmpty()) {
            return "INVOICES: No invoices in the system.";
        }

        $byStatus = $invoices->groupBy('status');
        $total    = $invoices->sum('total');
        $paid     = $invoices->where('status', 'paid')->sum('total');
        $pending  = $invoices->whereIn('status', ['sent', 'draft'])->sum('total');
        $overdue  = $invoices->where('status', 'overdue')->sum('total');

        $lines = [
            "INVOICE DASHBOARD ({$invoices->count()} invoices shown):",
            "Total Billed: {$this->money($total)} | Collected: {$this->money($paid)} | Pending: {$this->money($pending)} | Overdue: {$this->money($overdue)}",
        ];

        foreach (['overdue', 'sent', 'draft', 'paid'] as $status) {
            if (! isset($byStatus[$status])) continue;
            $group  = $byStatus[$status];
            $flag   = $status === 'overdue' ? '🚨 ' : ($status === 'paid' ? '✅ ' : '🔄 ');
            $label  = ucfirst($status);
            $lines[] = "\n  {$flag}{$label} ({$group->count()} | {$this->money($group->sum('total'))})";
            foreach ($group->take(5) as $inv) {
                $client  = $inv->project?->company?->name ?? '—';
                $due     = $inv->due_date ? " due {$this->date($inv->due_date)}" : '';
                $lines[] = "    • {$inv->invoice_number}: {$this->money($inv->total)} — {$client}{$due}";
            }
        }

        return implode("\n", $lines);
    }

    // ── Private Helpers ────────────────────────────────────────────────────────

    private function getProjectBurnedHours(int $projectId): float
    {
        return (float) DB::table('timesheet_entries')
            ->where('project_id', $projectId)
            ->sum('hours');
    }

    private function getPhaseBurnedHours(int $phaseId): float
    {
        return (float) DB::table('timesheet_entries')
            ->where('project_phase_id', $phaseId)
            ->sum('hours');
    }

    private function phaseRiskFlag(mixed $phase, float $burned): string
    {
        if ($phase->status === 'completed') return '✅';

        $burnPct       = $phase->estimated_hours > 0
            ? ($burned / $phase->estimated_hours) * 100 : 0;
        $completePct   = (float) $phase->percent_complete;

        if ($burnPct > 90 && $completePct < 80) return '🚨';
        if ($burnPct > 75 && $completePct < 65) return '⚠️ ';
        if ($phase->status === 'in_progress')    return '🔄';
        return '⏳';
    }

    private function money(mixed $amount): string
    {
        return $this->currency . number_format((float) $amount, 0);
    }

    private function date(mixed $date): string
    {
        if (! $date) return 'N/A';
        return Carbon::parse($date)->format($this->dateFormat);
    }

    private function fileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i     = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }
}
