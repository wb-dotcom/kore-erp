<?php

namespace App\Http\Controllers;

use App\Models\ActivityTemplate;
use App\Models\Proposal;
use App\Models\ProposalActivity;
use App\Models\ProposalDeliverable;
use App\Models\ProposalTask;
use App\Services\ProposalSimilarityService;
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

    // ── Prior Proposal Import ─────────────────────────────────────────────────

    /**
     * GET /proposals/{proposal}/deliverables/prior/{source}
     * Returns the deliverable tree of a source proposal, annotated with
     * duplicate flags against the target proposal.
     */
    public function priorProposalTree(
        Proposal $proposal,
        Proposal $source,
        ProposalSimilarityService $similarity
    ): JsonResponse {
        $source->load(['deliverables.activities.tasks', 'company', 'status']);

        $dupeCheck = $similarity->checkImportDuplicates($proposal, $source);

        $deliverables = $source->deliverables->map(function (ProposalDeliverable $d) use ($dupeCheck) {
            $isDuplicate = in_array(strtolower(trim($d->name)), array_map('strtolower', $dupeCheck['duplicates']));
            return [
                'id'          => $d->id,
                'name'        => $d->name,
                'description' => $d->description,
                'is_duplicate' => $isDuplicate,
                'activities'  => $d->activities->map(fn ($a) => [
                    'id'                 => $a->id,
                    'name'               => $a->name,
                    'description'        => $a->description,
                    'assigned_role'      => $a->assigned_role,
                    'budgeted_hours'     => $a->budgeted_hours,
                    'relative_start_day' => $a->relative_start_day,
                    'relative_end_day'   => $a->relative_end_day,
                    'tasks'              => $a->tasks->map(fn ($t) => [
                        'id'               => $t->id,
                        'name'             => $t->name,
                        'description'      => $t->description,
                        'assigned_role'    => $t->assigned_role,
                        'estimated_hours'  => $t->estimated_hours,
                        'relative_due_day' => $t->relative_due_day,
                    ])->values()->all(),
                ])->values()->all(),
            ];
        })->values()->all();

        return response()->json([
            'source' => [
                'id'      => $source->id,
                'ref'     => $source->ref,
                'title'   => $source->title,
                'company' => $source->company?->name,
                'status'  => $source->status?->name,
            ],
            'deliverables'     => $deliverables,
            'duplicate_names'  => $dupeCheck['duplicates'],
            'new_names'        => $dupeCheck['new'],
        ]);
    }

    /**
     * POST /proposals/{proposal}/deliverables/copy-from-proposal
     * Imports selected deliverables (by ID) from a source proposal.
     * Skips any that are flagged as duplicates unless force=true.
     */
    public function copyFromProposal(
        Request $request,
        Proposal $proposal,
        ProposalSimilarityService $similarity
    ): JsonResponse {
        $data = $request->validate([
            'source_proposal_id'   => ['required', 'exists:proposals,id'],
            'deliverable_ids'      => ['required', 'array', 'min:1'],
            'deliverable_ids.*'    => ['integer'],
            'skip_duplicates'      => ['boolean'],
        ]);

        $source        = Proposal::with('deliverables.activities.tasks')->find($data['source_proposal_id']);
        $skipDupes     = $data['skip_duplicates'] ?? true;
        $dupeCheck     = $similarity->checkImportDuplicates($proposal, $source);
        $duplicateNames = array_map('strtolower', $dupeCheck['duplicates']);

        $imported  = 0;
        $skipped   = 0;
        $sortOffset = $proposal->deliverables()->max('sort_order') + 1;

        foreach ($source->deliverables->whereIn('id', $data['deliverable_ids']) as $srcDeliv) {
            $nameLower = strtolower(trim($srcDeliv->name));

            if ($skipDupes && in_array($nameLower, $duplicateNames)) {
                $skipped++;
                continue;
            }

            $deliverable = ProposalDeliverable::create([
                'proposal_id' => $proposal->id,
                'name'        => $srcDeliv->name,
                'description' => $srcDeliv->description,
                'sort_order'  => $sortOffset++,
            ]);

            foreach ($srcDeliv->activities as $srcAct) {
                $activity = ProposalActivity::create([
                    'proposal_deliverable_id' => $deliverable->id,
                    'name'                    => $srcAct->name,
                    'description'             => $srcAct->description,
                    'relative_start_day'      => $srcAct->relative_start_day,
                    'relative_end_day'        => $srcAct->relative_end_day,
                    'assigned_role'           => $srcAct->assigned_role,
                    'budgeted_hours'          => $srcAct->budgeted_hours,
                    'sort_order'              => $srcAct->sort_order,
                ]);

                foreach ($srcAct->tasks as $srcTask) {
                    ProposalTask::create([
                        'proposal_activity_id' => $activity->id,
                        'name'                 => $srcTask->name,
                        'description'          => $srcTask->description,
                        'relative_due_day'     => $srcTask->relative_due_day,
                        'assigned_role'        => $srcTask->assigned_role,
                        'estimated_hours'      => $srcTask->estimated_hours,
                        'sort_order'           => $srcTask->sort_order,
                    ]);
                }
            }

            $imported++;
        }

        return response()->json([
            'message'      => "{$imported} deliverable(s) imported" . ($skipped > 0 ? ", {$skipped} skipped (duplicate)." : "."),
            'imported'     => $imported,
            'skipped'      => $skipped,
            'deliverables' => $proposal->deliverables()->with('activities.tasks')->get(),
        ]);
    }

    // ── Template Import ───────────────────────────────────────────────────────

    /** POST /proposals/{proposal}/deliverables/copy-template */
    public function copyFromTemplate(Request $request, Proposal $proposal): JsonResponse
    {
        $data = $request->validate([
            'template_id' => ['required', 'exists:activity_templates,id'],
        ]);

        $template = ActivityTemplate::with([
            'deliverables.activities.tasks',
            'deliverables.directTasks',
        ])->find($data['template_id']);

        $sortOffset = $proposal->deliverables()->max('sort_order') + 1;

        // Maps template IDs → newly created proposal IDs for dependency remapping
        $deliverableMap = []; // tpl deliverable id → proposal deliverable id
        $activityMap    = []; // tpl activity id    → proposal activity id
        $taskMap        = []; // tpl task id        → proposal task id

        // ── Pass 1: create all deliverables, activities, tasks ───────────────
        foreach ($template->deliverables as $tDeliv) {
            $deliverable = ProposalDeliverable::create([
                'proposal_id' => $proposal->id,
                'name'        => $tDeliv->name,
                'description' => $tDeliv->description,
                'sort_order'  => $sortOffset++,
                'max_hours'   => $tDeliv->max_hours,
                // depends_on_deliverable_id remapped in pass 2
            ]);
            $deliverableMap[$tDeliv->id] = $deliverable->id;

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
                    // depends_on_activity_id remapped in pass 2
                ]);
                $activityMap[$tAct->id] = $activity->id;

                foreach ($tAct->tasks as $tTask) {
                    $task = ProposalTask::create([
                        'proposal_activity_id'   => $activity->id,
                        'proposal_deliverable_id' => null,
                        'name'                   => $tTask->name,
                        'description'            => $tTask->description,
                        'relative_due_day'       => $tTask->relative_due_day,
                        'assigned_role'          => $tTask->assigned_role,
                        'estimated_hours'        => $tTask->estimated_hours,
                        'sort_order'             => $tTask->sort_order,
                        // depends_on_task_id remapped in pass 2
                    ]);
                    $taskMap[$tTask->id] = $task->id;
                }
            }

            // Direct tasks (no milestone)
            foreach ($tDeliv->directTasks as $tTask) {
                $task = ProposalTask::create([
                    'proposal_activity_id'    => null,
                    'proposal_deliverable_id' => $deliverable->id,
                    'name'                   => $tTask->name,
                    'description'            => $tTask->description,
                    'relative_due_day'       => $tTask->relative_due_day,
                    'assigned_role'          => $tTask->assigned_role,
                    'estimated_hours'        => $tTask->estimated_hours,
                    'sort_order'             => $tTask->sort_order,
                ]);
                $taskMap[$tTask->id] = $task->id;
            }
        }

        // ── Pass 2: remap dependency IDs ─────────────────────────────────────
        foreach ($template->deliverables as $tDeliv) {
            if ($tDeliv->depends_on_deliverable_id && isset($deliverableMap[$tDeliv->depends_on_deliverable_id])) {
                ProposalDeliverable::where('id', $deliverableMap[$tDeliv->id])
                    ->update(['depends_on_deliverable_id' => $deliverableMap[$tDeliv->depends_on_deliverable_id]]);
            }

            foreach ($tDeliv->activities as $tAct) {
                if ($tAct->depends_on_activity_id && isset($activityMap[$tAct->depends_on_activity_id])) {
                    ProposalActivity::where('id', $activityMap[$tAct->id])
                        ->update(['depends_on_activity_id' => $activityMap[$tAct->depends_on_activity_id]]);
                }

                foreach ($tAct->tasks as $tTask) {
                    if ($tTask->depends_on_task_id && isset($taskMap[$tTask->depends_on_task_id])) {
                        ProposalTask::where('id', $taskMap[$tTask->id])
                            ->update(['depends_on_task_id' => $taskMap[$tTask->depends_on_task_id]]);
                    }
                }
            }

            foreach ($tDeliv->directTasks as $tTask) {
                if ($tTask->depends_on_task_id && isset($taskMap[$tTask->depends_on_task_id])) {
                    ProposalTask::where('id', $taskMap[$tTask->id])
                        ->update(['depends_on_task_id' => $taskMap[$tTask->depends_on_task_id]]);
                }
            }
        }

        return response()->json([
            'message'      => "Template '{$template->name}' copied successfully.",
            'deliverables' => $proposal->deliverables()->with('activities.tasks')->get(),
        ]);
    }
}
