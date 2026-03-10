<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\TaskAssignment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Daily scheduled command that flags overdue tasks.
 *
 * Scheduled in routes/console.php:
 *   Schedule::command('kore:check-overdue-tasks')->daily();
 *
 * A task is overdue when:
 *   - due_date < today
 *   - status is NOT 'complete'
 *
 * Actions taken:
 *   1. Logs overdue count to the activity log for dashboard reporting
 *   2. Lists overdue tasks in CLI output (visible in Laravel Sail logs)
 *
 * Future: integrate with notification system to email PMs / assignees.
 */
class CheckOverdueTasks extends Command
{
    protected $signature   = 'kore:check-overdue-tasks';
    protected $description = 'Flag overdue tasks and log to activity log';

    public function handle(): int
    {
        $overdue = Task::where('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['complete', 'cancelled'])
            ->with(['milestone.deliverable.project.projectManager', 'assignments.user'])
            ->get();

        if ($overdue->isEmpty()) {
            $this->info('No overdue tasks today.');
            return 0;
        }

        $this->warn("Found {$overdue->count()} overdue task(s):");

        foreach ($overdue as $task) {
            $project = $task->milestone?->deliverable?->project;
            $daysOverdue = now()->diffInDays($task->due_date);

            $this->line("  [{$project?->project_number}] {$task->name} — {$daysOverdue}d overdue");

            // Log each overdue task to the activity log for the dashboard
            ActivityLog::record(
                "Overdue task: {$task->name} ({$daysOverdue} day(s) past due)",
                'tasks',
                $task->id,
                $project?->title ?? 'Unknown project'
            );
        }

        Log::warning("kore:check-overdue-tasks: {$overdue->count()} overdue tasks", [
            'task_ids' => $overdue->pluck('id')->toArray(),
        ]);

        $this->newLine();
        $this->info("Logged {$overdue->count()} overdue tasks to activity log.");

        return 0;
    }
}
