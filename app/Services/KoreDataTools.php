<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectCommunication;
use App\Models\Proposal;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\Timesheet;
use App\Models\TimesheetEntry;
use App\Models\User;
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
        return \Carbon\Carbon::parse($date)->format($this->dateFormat);
    }
}
