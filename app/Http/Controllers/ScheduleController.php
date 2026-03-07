<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function projectSchedule()
    {
        $projects = Project::with(['deliverables.milestones.tasks', 'projectManager', 'status', 'company'])
            ->whereHas('status', fn($q) => $q->whereIn('name', ['Active', 'On Hold']))
            ->orderByDesc('start_date')
            ->get();

        return view('schedule.project', compact('projects'));
    }

    public function employeeSchedule()
    {
        $users = User::where('is_active', 1)
            ->with(['taskAssignments.task.milestone.deliverable.project'])
            ->orderBy('first_name')
            ->get();

        return view('schedule.employee', compact('users'));
    }

    public function resourcesDashboard()
    {
        $users = User::where('is_active', 1)
            ->withCount(['taskAssignments as open_tasks' => fn($q) =>
                $q->whereHas('task', fn($t) => $t->whereIn('status', ['pending', 'in_progress']))
            ])
            ->orderByDesc('open_tasks')
            ->get();

        return view('schedule.resources', compact('users'));
    }

    /**
     * Return Gantt-compatible JSON for FullCalendar / custom Gantt rendering.
     */
    public function ganttData(Request $request)
    {
        $projectId = $request->input('project_id');

        $query = Project::with(['deliverables.milestones.tasks', 'company'])
            ->whereNotNull('start_date');

        if ($projectId) {
            $query->where('id', $projectId);
        }

        $projects = $query->get();

        $bars = [];

        foreach ($projects as $project) {
            // Project bar
            $bars[] = [
                'id'        => "project-{$project->id}",
                'label'     => $project->title,
                'type'      => 'project',
                'start'     => $project->start_date?->format('Y-m-d'),
                'end'       => $project->end_date?->format('Y-m-d') ?? $project->start_date?->addMonths(3)->format('Y-m-d'),
                'color'     => '#4c8bf5',
                'progress'  => 0,
            ];

            foreach ($project->deliverables as $deliverable) {
                $bars[] = [
                    'id'       => "deliverable-{$deliverable->id}",
                    'parentId' => "project-{$project->id}",
                    'label'    => $deliverable->name,
                    'type'     => 'deliverable',
                    'color'    => '#6b7280',
                ];

                foreach ($deliverable->milestones as $milestone) {
                    foreach ($milestone->tasks as $task) {
                        if (! $task->start_date) continue;
                        $bars[] = [
                            'id'       => "task-{$task->id}",
                            'parentId' => "deliverable-{$deliverable->id}",
                            'label'    => $task->name,
                            'type'     => 'task',
                            'start'    => $task->start_date->format('Y-m-d'),
                            'end'      => $task->end_date?->format('Y-m-d') ?? $task->start_date->addWeek()->format('Y-m-d'),
                            'status'   => $task->status,
                            'color'    => match($task->status) {
                                'complete'    => '#22c55e',
                                'in_progress' => '#f59e0b',
                                default       => '#d1d5db',
                            },
                        ];
                    }
                }
            }
        }

        return response()->json($bars);
    }
}
