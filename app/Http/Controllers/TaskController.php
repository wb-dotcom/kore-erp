<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\TaskAssignment;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * "My Tasks" — all task assignments for the current user.
     */
    public function myTasks(Request $request)
    {
        $userId = auth()->id();

        $query = TaskAssignment::where('user_id', $userId)
            ->with([
                'task.milestone.deliverable.project.company',
                'task.milestone.deliverable.project.status',
            ]);

        // Filter by task status
        if ($status = $request->input('status')) {
            $query->whereHas('task', fn($q) => $q->where('status', $status));
        }

        // Filter by project
        if ($projectId = $request->input('project_id')) {
            $query->whereHas('task.milestone.deliverable.project', fn($q) => $q->where('id', $projectId));
        }

        $assignments = $query->get()->sortBy(function ($a) {
            // Sort: in_progress first, then pending, then complete
            return match($a->task->status) {
                'in_progress' => 0,
                'pending'     => 1,
                'complete'    => 2,
                default       => 3,
            };
        })->values();

        // Projects this user has tasks on (for filter dropdown)
        $myProjectIds = $assignments->map(fn($a) => $a->task?->milestone?->deliverable?->project_id)
                            ->unique()->filter()->all();
        $projects = Project::whereIn('id', $myProjectIds)->orderBy('title')->get();

        $statusCounts = [
            'in_progress' => $assignments->filter(fn($a) => $a->task->status === 'in_progress')->count(),
            'pending'     => $assignments->filter(fn($a) => $a->task->status === 'pending')->count(),
            'complete'    => $assignments->filter(fn($a) => $a->task->status === 'complete')->count(),
        ];

        return view('tasks.index', compact('assignments', 'projects', 'statusCounts'));
    }

    /**
     * Update a task assignment's status (and optionally the underlying task).
     */
    public function updateAssignment(Request $request, TaskAssignment $assignment)
    {
        // Only the assigned user can update
        if ($assignment->user_id !== auth()->id()) {
            abort(403);
        }

        $data = $request->validate([
            'task_status' => ['required', 'in:pending,in_progress,complete'],
            'notes'       => ['nullable', 'string'],
        ]);

        // Update the assignment notes
        $assignment->update(['notes' => $data['notes'] ?? $assignment->notes]);

        // Update the underlying task status
        $assignment->task->update(['status' => $data['task_status']]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'status' => $data['task_status']]);
        }

        return back()->with('success', 'Task status updated.');
    }
}
