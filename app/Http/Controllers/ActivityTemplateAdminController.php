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
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'work_type_id' => ['nullable', 'exists:work_types,id'],
        ]);

        $data['created_by']           = auth()->id();
        $data['total_budgeted_hours']  = 0;

        ActivityTemplate::create($data);

        return back()->with('success', "Template \"{$data['name']}\" created.");
    }

    public function show(ActivityTemplate $activityTemplate)
    {
        $activityTemplate->load('deliverables.activities.tasks', 'workType');
        $workTypes = WorkType::orderBy('name')->get();

        return view('admin.activity-templates.show', compact('activityTemplate', 'workTypes'));
    }

    public function update(Request $request, ActivityTemplate $activityTemplate): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'work_type_id' => ['nullable', 'exists:work_types,id'],
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
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $data['sort_order'] = $activityTemplate->deliverables()->max('sort_order') + 1;
        $activityTemplate->deliverables()->create($data);

        return back()->with('success', 'Deliverable added.');
    }

    public function updateDeliverable(Request $request, ActivityTemplateDeliverable $deliverable): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
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
            'name'                => ['required', 'string', 'max:255'],
            'description'         => ['nullable', 'string'],
            'relative_start_day'  => ['nullable', 'integer', 'min:0'],
            'relative_end_day'    => ['nullable', 'integer', 'min:0'],
            'assigned_role'       => ['nullable', 'string', 'max:100'],
            'budgeted_hours'      => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['sort_order']      = $deliverable->activities()->max('sort_order') + 1;
        $data['budgeted_hours']  = $data['budgeted_hours'] ?? 0;

        $deliverable->activities()->create($data);
        $deliverable->template->recalculateHours();

        return back()->with('success', 'Activity added.');
    }

    public function updateActivity(Request $request, ActivityTemplateActivity $activity): RedirectResponse
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'description'         => ['nullable', 'string'],
            'relative_start_day'  => ['nullable', 'integer', 'min:0'],
            'relative_end_day'    => ['nullable', 'integer', 'min:0'],
            'assigned_role'       => ['nullable', 'string', 'max:100'],
            'budgeted_hours'      => ['nullable', 'numeric', 'min:0'],
        ]);

        $activity->update($data);
        $activity->deliverable->template->recalculateHours();

        return back()->with('success', 'Activity updated.');
    }

    public function destroyActivity(ActivityTemplateActivity $activity): RedirectResponse
    {
        $template = $activity->deliverable->template;
        $activity->delete();
        $template->recalculateHours();

        return back()->with('success', 'Activity removed.');
    }

    // ── Tasks ─────────────────────────────────────────────────────────────────

    public function storeTask(Request $request, ActivityTemplateActivity $activity): RedirectResponse
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'relative_due_day' => ['nullable', 'integer', 'min:0'],
            'assigned_role'    => ['nullable', 'string', 'max:100'],
            'estimated_hours'  => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['sort_order']     = $activity->tasks()->max('sort_order') + 1;
        $data['estimated_hours'] = $data['estimated_hours'] ?? 0;

        $activity->tasks()->create($data);
        $activity->deliverable->template->recalculateHours();

        return back()->with('success', 'Task added.');
    }

    public function updateTask(Request $request, ActivityTemplateTask $task): RedirectResponse
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'relative_due_day' => ['nullable', 'integer', 'min:0'],
            'assigned_role'    => ['nullable', 'string', 'max:100'],
            'estimated_hours'  => ['nullable', 'numeric', 'min:0'],
        ]);

        $task->update($data);
        $task->activity->deliverable->template->recalculateHours();

        return back()->with('success', 'Task updated.');
    }

    public function destroyTask(ActivityTemplateTask $task): RedirectResponse
    {
        $template = $task->activity->deliverable->template;
        $task->delete();
        $template->recalculateHours();

        return back()->with('success', 'Task removed.');
    }
}
