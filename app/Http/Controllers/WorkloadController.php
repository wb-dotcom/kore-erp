<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use App\Models\WorkScheduleItem;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Resource Pipeline / Workload Scheduler
 *
 * Personal view  → GET /workload
 * Manager view   → GET /workload/team
 * Schedule a task → POST /workload/schedule
 * Update item    → PUT /workload/items/{item}
 * Remove item    → DELETE /workload/items/{item}
 * Reorder items  → POST /workload/reorder
 */
class WorkloadController extends Controller
{
    // ── Personal Workload ──────────────────────────────────────────────────────

    /**
     * My pipeline: weekly view with drag-to-reorder and quick timesheet fill.
     */
    public function index(Request $request)
    {
        $userId = auth()->id();

        // Determine the week being viewed (defaults to current week)
        $weekStart = $request->input('week')
            ? Carbon::parse($request->input('week'))->startOfWeek()
            : Carbon::now()->startOfWeek();

        $weekEnd = $weekStart->copy()->endOfWeek();

        // Scheduled items for this week
        $scheduleItems = WorkScheduleItem::where('user_id', $userId)
            ->where('week_start', $weekStart->format('Y-m-d'))
            ->with([
                'taskAssignment.task.milestone.deliverable.project.company',
                'taskAssignment.task.milestone.deliverable.project.status',
            ])
            ->orderBy('priority_order')
            ->get();

        // Unscheduled task assignments (still open, not yet scheduled for this week)
        $scheduledAssignmentIds = $scheduleItems->pluck('task_assignment_id');

        $unscheduledAssignments = TaskAssignment::where('user_id', $userId)
            ->whereNotIn('id', $scheduledAssignmentIds)
            ->whereHas('task', fn($q) => $q->whereIn('status', ['pending', 'in_progress']))
            ->with([
                'task.milestone.deliverable.project.company',
                'task.milestone.deliverable.project.status',
                'scheduleItems' => fn($q) => $q->where('week_start', $weekStart->format('Y-m-d')),
            ])
            ->get()
            ->sortBy(fn($a) => $a->task?->end_date)
            ->values();

        // Summary stats
        $totalScheduledHours = $scheduleItems->sum('scheduled_hours');

        // Navigation weeks
        $prevWeek = $weekStart->copy()->subWeek()->format('Y-m-d');
        $nextWeek = $weekStart->copy()->addWeek()->format('Y-m-d');

        return view('workload.index', compact(
            'scheduleItems', 'unscheduledAssignments',
            'weekStart', 'weekEnd', 'totalScheduledHours',
            'prevWeek', 'nextWeek'
        ));
    }

    // ── Manager Team View ──────────────────────────────────────────────────────

    /**
     * Team workload: all active users' pipelines for a given week.
     * Managers can drag-assign unscheduled tasks to resources.
     */
    public function team(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $weekStart = $request->input('week')
            ? Carbon::parse($request->input('week'))->startOfWeek()
            : Carbon::now()->startOfWeek();

        $weekEnd = $weekStart->copy()->endOfWeek();

        $users = User::where('is_active', 1)
            ->with([
                'workScheduleItems' => fn($q) => $q
                    ->where('week_start', $weekStart->format('Y-m-d'))
                    ->with('taskAssignment.task.milestone.deliverable.project')
                    ->orderBy('priority_order'),
            ])
            ->orderBy('first_name')
            ->get();

        // Unassigned tasks across all active projects (for manager assignment)
        $unassignedTasks = Task::whereIn('status', ['pending', 'in_progress'])
            ->doesntHave('assignments')
            ->with('milestone.deliverable.project.company')
            ->limit(50)
            ->get();

        $projects = Project::whereHas('status', fn($q) => $q->whereIn('name', ['Active', 'On Hold']))
            ->orderBy('title')
            ->get();

        $prevWeek = $weekStart->copy()->subWeek()->format('Y-m-d');
        $nextWeek = $weekStart->copy()->addWeek()->format('Y-m-d');

        return view('workload.team', compact(
            'users', 'weekStart', 'weekEnd',
            'unassignedTasks', 'projects',
            'prevWeek', 'nextWeek'
        ));
    }

    // ── AJAX: Schedule / Update / Remove ─────────────────────────────────────

    /**
     * POST /workload/schedule
     * Schedule (or update) a task assignment for a given week.
     * Body: { task_assignment_id, week_start, scheduled_hours, priority_order? }
     */
    public function schedule(Request $request)
    {
        $data = $request->validate([
            'task_assignment_id' => ['required', 'exists:task_assignments,id'],
            'week_start'         => ['required', 'date'],
            'scheduled_hours'    => ['required', 'numeric', 'min:0.25', 'max:80'],
            'priority_order'     => ['nullable', 'integer', 'min:1'],
        ]);

        $assignment = TaskAssignment::findOrFail($data['task_assignment_id']);
        $userId = auth()->id();

        // Managers can schedule for any user; regular users only for themselves
        if ($assignment->user_id !== $userId && ! auth()->user()->isManager()) {
            abort(403);
        }

        $weekStart = Carbon::parse($data['week_start'])->startOfWeek()->format('Y-m-d');

        // Auto-assign priority to end of list if not provided
        if (empty($data['priority_order'])) {
            $max = WorkScheduleItem::where('user_id', $assignment->user_id)
                ->where('week_start', $weekStart)
                ->max('priority_order') ?? 0;
            $data['priority_order'] = $max + 10;
        }

        $item = WorkScheduleItem::updateOrCreate(
            ['task_assignment_id' => $assignment->id, 'week_start' => $weekStart],
            [
                'user_id'         => $assignment->user_id,
                'scheduled_hours' => $data['scheduled_hours'],
                'priority_order'  => $data['priority_order'],
                'status'          => 'planned',
            ]
        );

        return response()->json(['success' => true, 'item' => $item->load('taskAssignment.task')]);
    }

    /**
     * PUT /workload/items/{item}
     */
    public function update(Request $request, WorkScheduleItem $item)
    {
        if ($item->user_id !== auth()->id() && ! auth()->user()->isManager()) {
            abort(403);
        }

        $data = $request->validate([
            'scheduled_hours' => ['nullable', 'numeric', 'min:0', 'max:80'],
            'priority_order'  => ['nullable', 'integer', 'min:1'],
            'status'          => ['nullable', 'in:planned,done,carried_over'],
            'notes'           => ['nullable', 'string'],
        ]);

        $item->update(array_filter($data, fn($v) => $v !== null));

        return response()->json(['success' => true]);
    }

    /**
     * DELETE /workload/items/{item}
     */
    public function destroy(WorkScheduleItem $item)
    {
        if ($item->user_id !== auth()->id() && ! auth()->user()->isManager()) {
            abort(403);
        }

        $item->delete();

        return response()->json(['success' => true]);
    }

    /**
     * POST /workload/reorder
     * Body: { items: [{ id, priority_order }, ...] }
     */
    public function reorder(Request $request)
    {
        $data = $request->validate([
            'items'                => ['required', 'array'],
            'items.*.id'           => ['required', 'exists:work_schedule_items,id'],
            'items.*.priority_order' => ['required', 'integer', 'min:1'],
        ]);

        $userId = auth()->id();

        foreach ($data['items'] as $row) {
            $item = WorkScheduleItem::find($row['id']);
            if ($item && ($item->user_id === $userId || auth()->user()->isManager())) {
                $item->update(['priority_order' => $row['priority_order']]);
            }
        }

        return response()->json(['success' => true]);
    }

    // ── AJAX: Assign task to resource (Manager) ───────────────────────────────

    /**
     * POST /workload/assign
     * Manager assigns a task to a user and schedules it.
     * Body: { task_id, user_id, role?, budget_hours?, week_start, scheduled_hours }
     */
    public function assign(Request $request)
    {
        if (! auth()->user()->isManager()) {
            abort(403);
        }

        $data = $request->validate([
            'task_id'         => ['required', 'exists:tasks,id'],
            'user_id'         => ['required', 'exists:users,id'],
            'role'            => ['nullable', 'string', 'max:100'],
            'budget_hours'    => ['nullable', 'numeric', 'min:0'],
            'week_start'      => ['required', 'date'],
            'scheduled_hours' => ['required', 'numeric', 'min:0.25', 'max:80'],
        ]);

        // Create or retrieve assignment
        $assignment = TaskAssignment::firstOrCreate(
            ['task_id' => $data['task_id'], 'user_id' => $data['user_id']],
            [
                'role'         => $data['role'] ?? null,
                'budget_hours' => $data['budget_hours'] ?? 0,
            ]
        );

        $weekStart = Carbon::parse($data['week_start'])->startOfWeek()->format('Y-m-d');

        $max = WorkScheduleItem::where('user_id', $data['user_id'])
            ->where('week_start', $weekStart)
            ->max('priority_order') ?? 0;

        $item = WorkScheduleItem::updateOrCreate(
            ['task_assignment_id' => $assignment->id, 'week_start' => $weekStart],
            [
                'user_id'         => $data['user_id'],
                'scheduled_hours' => $data['scheduled_hours'],
                'priority_order'  => $max + 10,
                'status'          => 'planned',
            ]
        );

        return response()->json([
            'success'    => true,
            'assignment' => $assignment,
            'item'       => $item,
        ]);
    }

    // ── AJAX: Weekly data for timesheet quick-fill ────────────────────────────

    /**
     * GET /workload/week-data?week=YYYY-MM-DD
     * Returns scheduled tasks for the current user's given week.
     * Used by timesheet to offer quick-fill from pipeline.
     */
    public function weekData(Request $request)
    {
        $userId = auth()->id();
        $weekStart = $request->input('week')
            ? Carbon::parse($request->input('week'))->startOfWeek()->format('Y-m-d')
            : Carbon::now()->startOfWeek()->format('Y-m-d');

        $items = WorkScheduleItem::where('user_id', $userId)
            ->where('week_start', $weekStart)
            ->with([
                'taskAssignment.task.milestone.deliverable.project',
            ])
            ->orderBy('priority_order')
            ->get()
            ->map(fn($item) => [
                'schedule_item_id'  => $item->id,
                'assignment_id'     => $item->task_assignment_id,
                'task_id'           => $item->taskAssignment?->task_id,
                'task_name'         => $item->taskAssignment?->task?->name,
                'milestone'         => $item->taskAssignment?->task?->milestone?->name,
                'deliverable'       => $item->taskAssignment?->task?->milestone?->deliverable?->name,
                'project_id'        => $item->taskAssignment?->task?->milestone?->deliverable?->project_id,
                'project_title'     => $item->taskAssignment?->task?->milestone?->deliverable?->project?->title,
                'deliverable_id'    => $item->taskAssignment?->task?->milestone?->deliverable_id,
                'milestone_id'      => $item->taskAssignment?->task?->milestone_id,
                'scheduled_hours'   => $item->scheduled_hours,
                'status'            => $item->status,
                'priority_order'    => $item->priority_order,
            ]);

        return response()->json(['items' => $items]);
    }
}
