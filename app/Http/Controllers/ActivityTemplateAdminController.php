<?php

namespace App\Http\Controllers;

use App\Models\ActivityTemplate;
use App\Models\ActivityTemplateActivity;
use App\Models\ActivityTemplateDeliverable;
use App\Models\ActivityTemplateTask;
use App\Models\WorkType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActivityTemplateAdminController extends Controller
{
    public function index()
    {
        $templates = ActivityTemplate::with('workType')
            ->withCount('deliverables')
            ->orderBy('name')
            ->get();

        return view('admin.activity-templates.index', compact('templates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'work_type_id'  => ['nullable', 'exists:work_types,id'],
            'billing_type'  => ['nullable', 'in:fixed,time_and_material,hybrid,retainer,per_deliverable'],
            'billing_cycle' => ['nullable', 'in:biweekly,monthly,quarterly,on_completion,custom'],
        ]);

        $data['created_by']          = auth()->id();
        $data['total_budgeted_hours'] = 0;

        ActivityTemplate::create($data);

        return back()->with('success', "Template \"{$data['name']}\" created.");
    }

    public function show(ActivityTemplate $activityTemplate)
    {
        $activityTemplate->load([
            'deliverables.activities.tasks',
            'deliverables.directTasks',
            'workType',
        ]);
        $workTypes = WorkType::orderBy('name')->get();

        return view('admin.activity-templates.show', compact('activityTemplate', 'workTypes'));
    }

    public function update(Request $request, ActivityTemplate $activityTemplate): RedirectResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'work_type_id'  => ['nullable', 'exists:work_types,id'],
            'billing_type'  => ['nullable', 'in:fixed,time_and_material,hybrid,retainer,per_deliverable'],
            'billing_cycle' => ['nullable', 'in:biweekly,monthly,quarterly,on_completion,custom'],
        ]);

        $activityTemplate->update($data);

        return back()->with('success', 'Template updated.');
    }

    public function destroy(ActivityTemplate $activityTemplate): RedirectResponse
    {
        $name = $activityTemplate->name;
        $activityTemplate->delete();

        return redirect()->route('admin.templates')->with('success', "Template \"{$name}\" deleted.");
    }

    // ── Deliverables ──────────────────────────────────────────────────────────

    public function storeDeliverable(Request $request, ActivityTemplate $activityTemplate): RedirectResponse
    {
        $data = $request->validate([
            'name'                      => ['required', 'string', 'max:255'],
            'description'               => ['nullable', 'string'],
            'max_hours'                 => ['nullable', 'numeric', 'min:0'],
            'depends_on_deliverable_id' => ['nullable', 'exists:activity_template_deliverables,id'],
        ]);

        $data['sort_order'] = $activityTemplate->deliverables()->max('sort_order') + 1;
        $activityTemplate->deliverables()->create($data);

        return back()->with('success', 'Deliverable added.');
    }

    public function updateDeliverable(Request $request, ActivityTemplateDeliverable $deliverable): RedirectResponse
    {
        $data = $request->validate([
            'name'                      => ['required', 'string', 'max:255'],
            'description'               => ['nullable', 'string'],
            'max_hours'                 => ['nullable', 'numeric', 'min:0'],
            'depends_on_deliverable_id' => ['nullable', 'exists:activity_template_deliverables,id'],
        ]);

        $deliverable->update($data);

        return back()->with('success', 'Deliverable updated.');
    }

    public function destroyDeliverable(ActivityTemplateDeliverable $deliverable): RedirectResponse
    {
        $deliverable->delete();

        return back()->with('success', 'Deliverable removed.');
    }

    // ── Activities (Milestones) ───────────────────────────────────────────────

    public function storeActivity(Request $request, ActivityTemplateDeliverable $deliverable): RedirectResponse
    {
        $data = $request->validate([
            'name'                   => ['required', 'string', 'max:255'],
            'description'            => ['nullable', 'string'],
            'relative_start_day'     => ['nullable', 'integer', 'min:0'],
            'relative_end_day'       => ['nullable', 'integer', 'min:0'],
            'assigned_role'          => ['nullable', 'string', 'max:100'],
            'budgeted_hours'         => ['nullable', 'numeric', 'min:0'],
            'depends_on_activity_id' => ['nullable', 'exists:activity_template_activities,id'],
        ]);

        $data['sort_order']     = $deliverable->activities()->max('sort_order') + 1;
        $data['budgeted_hours'] = $data['budgeted_hours'] ?? 0;

        $deliverable->activities()->create($data);
        $deliverable->template->recalculateHours();

        return back()->with('success', 'Milestone added.');
    }

    public function updateActivity(Request $request, ActivityTemplateActivity $activity): RedirectResponse
    {
        $data = $request->validate([
            'name'                   => ['required', 'string', 'max:255'],
            'description'            => ['nullable', 'string'],
            'relative_start_day'     => ['nullable', 'integer', 'min:0'],
            'relative_end_day'       => ['nullable', 'integer', 'min:0'],
            'assigned_role'          => ['nullable', 'string', 'max:100'],
            'budgeted_hours'         => ['nullable', 'numeric', 'min:0'],
            'depends_on_activity_id' => ['nullable', 'exists:activity_template_activities,id'],
        ]);

        $activity->update($data);
        $activity->deliverable->template->recalculateHours();

        return back()->with('success', 'Milestone updated.');
    }

    public function destroyActivity(ActivityTemplateActivity $activity): RedirectResponse
    {
        $template = $activity->deliverable->template;
        $activity->delete();
        $template->recalculateHours();

        return back()->with('success', 'Milestone removed.');
    }

    // ── Tasks under a milestone ───────────────────────────────────────────────

    public function storeTask(Request $request, ActivityTemplateActivity $activity): RedirectResponse
    {
        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'description'       => ['nullable', 'string'],
            'relative_due_day'  => ['nullable', 'integer', 'min:0'],
            'assigned_role'     => ['nullable', 'string', 'max:100'],
            'estimated_hours'   => ['nullable', 'numeric', 'min:0'],
            'depends_on_task_id' => ['nullable', 'exists:activity_template_tasks,id'],
        ]);

        $data['sort_order']      = $activity->tasks()->max('sort_order') + 1;
        $data['estimated_hours'] = $data['estimated_hours'] ?? 0;

        $activity->tasks()->create($data);
        $activity->deliverable->template->recalculateHours();

        return back()->with('success', 'Task added.');
    }

    public function updateTask(Request $request, ActivityTemplateTask $task): RedirectResponse
    {
        $data = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'description'        => ['nullable', 'string'],
            'relative_due_day'   => ['nullable', 'integer', 'min:0'],
            'assigned_role'      => ['nullable', 'string', 'max:100'],
            'estimated_hours'    => ['nullable', 'numeric', 'min:0'],
            'depends_on_task_id' => ['nullable', 'exists:activity_template_tasks,id'],
        ]);

        $task->update($data);

        // Recalculate from whichever parent owns this task
        if ($task->activity_template_activity_id) {
            $task->activity->deliverable->template->recalculateHours();
        } elseif ($task->activity_template_deliverable_id) {
            $task->deliverable->template->recalculateHours();
        }

        return back()->with('success', 'Task updated.');
    }

    public function destroyTask(ActivityTemplateTask $task): RedirectResponse
    {
        if ($task->activity_template_activity_id) {
            $template = $task->activity->deliverable->template;
        } else {
            $template = $task->deliverable->template;
        }

        $task->delete();
        $template->recalculateHours();

        return back()->with('success', 'Task removed.');
    }

    // ── Direct tasks under a deliverable (no milestone) ──────────────────────

    /** POST /admin/templates/deliverables/{deliverable}/tasks */
    public function storeDeliverableTask(Request $request, ActivityTemplateDeliverable $deliverable): RedirectResponse
    {
        $data = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'description'        => ['nullable', 'string'],
            'relative_due_day'   => ['nullable', 'integer', 'min:0'],
            'assigned_role'      => ['nullable', 'string', 'max:100'],
            'estimated_hours'    => ['nullable', 'numeric', 'min:0'],
            'depends_on_task_id' => ['nullable', 'exists:activity_template_tasks,id'],
        ]);

        $data['sort_order']                       = $deliverable->directTasks()->max('sort_order') + 1;
        $data['estimated_hours']                  = $data['estimated_hours'] ?? 0;
        $data['activity_template_deliverable_id'] = $deliverable->id;
        // activity_template_activity_id stays null — task is directly under deliverable

        ActivityTemplateTask::create($data);
        $deliverable->template->recalculateHours();

        return back()->with('success', 'Task added directly to deliverable.');
    }
}
