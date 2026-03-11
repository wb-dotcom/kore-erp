<?php

namespace App\Http\Controllers;

use App\Models\ActivityTemplate;
use App\Models\Proposal;
use App\Models\ProposalActivity;
use App\Models\ProposalDeliverable;
use App\Models\ProposalTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProposalDeliverableController extends Controller
{
    /** GET /proposals/{proposal}/deliverables — full tree as JSON */
    public function index(Proposal $proposal): JsonResponse
    {
        $deliverables = $proposal->deliverables()
            ->with('activities.tasks')
            ->get();

        $totalHours      = $deliverables->sum(fn ($d) => $d->activities->sum('budgeted_hours'));
        $totalActivities = $deliverables->sum(fn ($d) => $d->activities->count());
        $totalTasks      = $deliverables->sum(fn ($d) => $d->activities->sum(fn ($a) => $a->tasks->count()));

        return response()->json([
            'deliverables'    => $deliverables,
            'total_hours'     => $totalHours,
            'total_activities' => $totalActivities,
            'total_tasks'     => $totalTasks,
        ]);
    }

    // ── Deliverables ──────────────────────────────────────────────────────────

    public function storeDeliverable(Request $request, Proposal $proposal): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $data['proposal_id'] = $proposal->id;
        $data['sort_order']  = $proposal->deliverables()->max('sort_order') + 1;

        $deliverable = ProposalDeliverable::create($data);

        return response()->json(['deliverable' => $deliverable->load('activities.tasks')], 201);
    }

    public function updateDeliverable(Request $request, Proposal $proposal, ProposalDeliverable $deliverable): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $deliverable->update($data);

        return response()->json(['deliverable' => $deliverable->fresh('activities.tasks')]);
    }

    public function destroyDeliverable(Proposal $proposal, ProposalDeliverable $deliverable): JsonResponse
    {
        $deliverable->delete();
        return response()->json(['message' => 'Deliverable deleted.']);
    }

    // ── Activities ────────────────────────────────────────────────────────────

    public function storeActivity(Request $request, Proposal $proposal, ProposalDeliverable $deliverable): JsonResponse
    {
        $data = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'description'        => ['nullable', 'string'],
            'relative_start_day' => ['nullable', 'integer', 'min:0'],
            'relative_end_day'   => ['nullable', 'integer', 'min:0'],
            'assigned_role'      => ['nullable', 'string', 'max:100'],
            'budgeted_hours'     => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['proposal_deliverable_id'] = $deliverable->id;
        $data['sort_order']              = $deliverable->activities()->max('sort_order') + 1;

        $activity = ProposalActivity::create($data);

        return response()->json(['activity' => $activity->load('tasks')], 201);
    }

    public function updateActivity(Request $request, Proposal $proposal, ProposalActivity $activity): JsonResponse
    {
        $data = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'description'        => ['nullable', 'string'],
            'relative_start_day' => ['nullable', 'integer', 'min:0'],
            'relative_end_day'   => ['nullable', 'integer', 'min:0'],
            'assigned_role'      => ['nullable', 'string', 'max:100'],
            'budgeted_hours'     => ['nullable', 'numeric', 'min:0'],
        ]);

        $activity->update($data);

        return response()->json(['activity' => $activity->fresh('tasks')]);
    }

    public function destroyActivity(Proposal $proposal, ProposalActivity $activity): JsonResponse
    {
        $activity->delete();
        return response()->json(['message' => 'Activity deleted.']);
    }

    // ── Tasks ─────────────────────────────────────────────────────────────────

    public function storeTask(Request $request, Proposal $proposal, ProposalActivity $activity): JsonResponse
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'relative_due_day' => ['nullable', 'integer', 'min:0'],
            'assigned_role'    => ['nullable', 'string', 'max:100'],
            'estimated_hours'  => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['proposal_activity_id'] = $activity->id;
        $data['sort_order']           = $activity->tasks()->max('sort_order') + 1;

        $task = ProposalTask::create($data);

        return response()->json(['task' => $task], 201);
    }

    public function updateTask(Request $request, Proposal $proposal, ProposalTask $task): JsonResponse
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'relative_due_day' => ['nullable', 'integer', 'min:0'],
            'assigned_role'    => ['nullable', 'string', 'max:100'],
            'estimated_hours'  => ['nullable', 'numeric', 'min:0'],
        ]);

        $task->update($data);

        return response()->json(['task' => $task->fresh()]);
    }

    public function destroyTask(Proposal $proposal, ProposalTask $task): JsonResponse
    {
        $task->delete();
        return response()->json(['message' => 'Task deleted.']);
    }

    // ── Template Import ───────────────────────────────────────────────────────

    /** POST /proposals/{proposal}/deliverables/copy-template */
    public function copyFromTemplate(Request $request, Proposal $proposal): JsonResponse
    {
        $data = $request->validate([
            'template_id' => ['required', 'exists:activity_templates,id'],
        ]);

        $template = ActivityTemplate::with('deliverables.activities.tasks')->find($data['template_id']);

        $sortOffset = $proposal->deliverables()->max('sort_order') + 1;

        foreach ($template->deliverables as $tDeliv) {
            $deliverable = ProposalDeliverable::create([
                'proposal_id' => $proposal->id,
                'name'        => $tDeliv->name,
                'description' => $tDeliv->description,
                'sort_order'  => $sortOffset++,
            ]);

            foreach ($tDeliv->activities as $tAct) {
                $activity = ProposalActivity::create([
                    'proposal_deliverable_id' => $deliverable->id,
                    'name'                    => $tAct->name,
                    'description'             => $tAct->description,
                    'relative_start_day'      => $tAct->relative_start_day,
                    'relative_end_day'        => $tAct->relative_end_day,
                    'assigned_role'           => $tAct->assigned_role,
                    'budgeted_hours'          => $tAct->budgeted_hours,
                    'sort_order'              => $tAct->sort_order,
                ]);

                foreach ($tAct->tasks as $tTask) {
                    ProposalTask::create([
                        'proposal_activity_id' => $activity->id,
                        'name'                 => $tTask->name,
                        'description'          => $tTask->description,
                        'relative_due_day'     => $tTask->relative_due_day,
                        'assigned_role'        => $tTask->assigned_role,
                        'estimated_hours'      => $tTask->estimated_hours,
                        'sort_order'           => $tTask->sort_order,
                    ]);
                }
            }
        }

        return response()->json([
            'message'      => "Template '{$template->name}' copied successfully.",
            'deliverables' => $proposal->deliverables()->with('activities.tasks')->get(),
        ]);
    }
}
