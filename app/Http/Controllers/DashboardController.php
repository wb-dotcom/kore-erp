<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Proposal;
use App\Models\Timesheet;
use App\Models\TimeOffRequest;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // ── Employee Dashboard ────────────────────────────────────────────
    public function employee()
    {
        $user = auth()->user();

        // My active task assignments
        $myTasks = TaskAssignment::with(['task.milestone.deliverable.project'])
            ->where('user_id', $user->id)
            ->whereHas('task', fn($q) => $q->where('status', 'active'))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Current timesheet period
        $currentTimesheet = Timesheet::with('period')
            ->where('user_id', $user->id)
            ->whereHas('period', function ($q) {
                $q->where('start_date', '<=', now())
                  ->where('end_date', '>=', now());
            })
            ->first();

        // Pending time-off requests
        $pendingTimeOff = TimeOffRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();

        // Hours logged this week
        $weekStart = now()->startOfWeek();
        $hoursThisWeek = DB::table('timesheet_entries')
            ->join('timesheets', 'timesheets.id', '=', 'timesheet_entries.timesheet_id')
            ->where('timesheets.user_id', $user->id)
            ->where('timesheet_entries.entry_date', '>=', $weekStart)
            ->sum('timesheet_entries.hours') ?? 0;

        return view('dashboard.employee', compact(
            'myTasks',
            'currentTimesheet',
            'pendingTimeOff',
            'hoursThisWeek'
        ))->with('title', 'My Dashboard');
    }

    // ── Business Dashboard ────────────────────────────────────────────
    public function business()
    {
        // Active projects count
        $activeProjects = Project::whereHas('status', fn($q) => $q->where('name', 'Active'))->count();

        // Proposals by status
        $proposalStats = Proposal::select('proposal_statuses.name', DB::raw('COUNT(*) as total'))
            ->join('proposal_statuses', 'proposals.status_id', '=', 'proposal_statuses.id')
            ->groupBy('proposal_statuses.name')
            ->pluck('total', 'name');

        // Recent proposals
        $recentProposals = Proposal::with(['company', 'status', 'accountManager'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        // Recent projects
        $recentProjects = Project::with(['company', 'status', 'projectManager'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        // Project status breakdown
        $projectStats = Project::select('project_statuses.name', DB::raw('COUNT(*) as total'))
            ->join('project_statuses', 'projects.status_id', '=', 'project_statuses.id')
            ->groupBy('project_statuses.name')
            ->pluck('total', 'name');

        return view('dashboard.business', compact(
            'activeProjects',
            'proposalStats',
            'recentProposals',
            'recentProjects',
            'projectStats'
        ))->with('title', 'Business Dashboard');
    }

    // ── Financial Dashboard ───────────────────────────────────────────
    public function financial()
    {
        // Invoice totals
        $invoiceTotals = DB::table('invoices')
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as amount'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $totalBilled    = $invoiceTotals->get('sent')?->amount ?? 0;
        $totalPaid      = $invoiceTotals->get('paid')?->amount ?? 0;
        $totalOverdue   = $invoiceTotals->get('overdue')?->amount ?? 0;
        $totalDraft     = $invoiceTotals->get('draft')?->amount ?? 0;

        // Recent invoices
        $recentInvoices = DB::table('invoices')
            ->join('companies', 'invoices.company_id', '=', 'companies.id')
            ->select('invoices.*', 'companies.name as company_name')
            ->orderByDesc('invoices.created_at')
            ->limit(10)
            ->get();

        // Monthly revenue (last 6 months)
        $monthlyRevenue = DB::table('invoices')
            ->where('status', 'paid')
            ->where('paid_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw("TO_CHAR(paid_at, 'YYYY-MM') as month"),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        return view('dashboard.financial', compact(
            'totalBilled',
            'totalPaid',
            'totalOverdue',
            'totalDraft',
            'recentInvoices',
            'monthlyRevenue'
        ))->with('title', 'Financial Dashboard');
    }

    // ── KPI Dashboard ─────────────────────────────────────────────────
    public function kpi()
    {
        // Timesheet submission rate (current period)
        $totalUsers      = DB::table('users')->where('is_active', 1)->count();
        $submittedCount  = DB::table('timesheets')
            ->whereIn('status', ['submitted', 'approved'])
            ->count();

        // Active tasks overview
        $taskStats = Task::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        // Budget vs actuals per project (top 10 active)
        $budgetData = DB::table('projects')
            ->join('project_statuses', 'projects.status_id', '=', 'project_statuses.id')
            ->leftJoin('task_assignments', 'task_assignments.task_id', DB::raw(
                '(SELECT id FROM tasks WHERE milestone_id IN (SELECT id FROM milestones WHERE deliverable_id IN (SELECT id FROM deliverables WHERE project_id = projects.id)) LIMIT 1)'
            ))
            ->where('project_statuses.name', 'Active')
            ->select(
                'projects.id',
                'projects.title',
                'projects.total_budget',
                DB::raw('SUM(task_assignments.actual_hours) as actual_hours')
            )
            ->groupBy('projects.id', 'projects.title', 'projects.total_budget')
            ->limit(10)
            ->get();

        // Proposal win rate
        $totalProposals  = Proposal::count();
        $wonProposals    = Proposal::whereHas('status', fn($q) => $q->where('name', 'Approved'))->count();
        $winRate         = $totalProposals > 0 ? round(($wonProposals / $totalProposals) * 100, 1) : 0;

        return view('dashboard.kpi', compact(
            'totalUsers',
            'taskStats',
            'budgetData',
            'totalProposals',
            'wonProposals',
            'winRate'
        ))->with('title', 'KPI Dashboard');
    }

    // ── AJAX: Chart Data ──────────────────────────────────────────────
    public function chartData(Request $request)
    {
        $type = $request->input('type', 'project_status');

        $data = match ($type) {
            'project_status' => DB::table('projects')
                ->join('project_statuses', 'projects.status_id', '=', 'project_statuses.id')
                ->select('project_statuses.name as label', DB::raw('COUNT(*) as value'))
                ->groupBy('project_statuses.name')
                ->get(),

            'proposal_status' => DB::table('proposals')
                ->join('proposal_statuses', 'proposals.status_id', '=', 'proposal_statuses.id')
                ->select('proposal_statuses.name as label', DB::raw('COUNT(*) as value'))
                ->groupBy('proposal_statuses.name')
                ->get(),

            default => collect(),
        };

        return response()->json($data);
    }
}
