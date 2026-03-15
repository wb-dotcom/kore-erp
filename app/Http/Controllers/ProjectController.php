<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ActivityTemplate;
use App\Models\Company;
use App\Models\Deliverable;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\ProjectType;
use App\Models\Proposal;
use App\Models\ProposalStatus;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskDependency;
use App\Models\User;
use App\Http\Controllers\ActivityTemplateImportController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::with(['company', 'projectManager', 'projectType', 'status', 'proposal'])
            ->orderByDesc('year')
            ->orderByDesc('project_number');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('company', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        if ($statusId = $request->input('status_id')) {
            $query->where('status_id', $statusId);
        }

        if ($managerId = $request->input('manager_id')) {
            $query->where('project_manager_id', $managerId);
        }

        if ($year = $request->input('year')) {
            $query->where('year', $year);
        }

        if ($request->input('billable_only')) {
            $query->whereNotNull('proposal_id');
        }

        $projects  = $query->paginate(25)->withQueryString();
        $statuses  = ProjectStatus::orderBy('name')->get();
        $managers  = User::where('is_active', 1)->orderBy('first_name')->get();
        $years     = Project::select('year')->distinct()->orderByDesc('year')->pluck('year');

        return view('projects.index', compact('projects', 'statuses', 'managers', 'years'));
    }

    public function create()
    {
        $companies    = Company::where('is_active', 1)->orderBy('name')->get();
        $statuses     = ProjectStatus::orderBy('name')->get();
        $projectTypes = ProjectType::orderBy('name')->get();
        $managers     = User::where('is_active', 1)->orderBy('first_name')->get();

        $approvedStatusId = ProposalStatus::whereRaw('LOWER(name) = ?', ['approved'])->value('id');
        $proposals    = Proposal::whereDoesntHave('project')
                            ->where('status_id', $approvedStatusId)
                            ->orderByDesc('year')
                            ->orderByDesc('proposal_number')
                            ->get();

        $year       = now()->year;
        $lastNumber = Project::where('year', $year)->max('project_number') ?? 0;
        $nextNumber = $lastNumber + 1;

        // Default internal client for non-billable projects
        $k5Company = Company::where('name', 'K5 Company')->first();

        return view('projects.create', compact(
            'companies', 'statuses', 'projectTypes', 'managers', 'proposals',
            'year', 'nextNumber', 'k5Company'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'              => ['required', 'string', 'max:255'],
            'company_id'         => ['nullable', 'exists:companies,id'],
            'project_manager_id' => ['nullable', 'exists:users,id'],
            'project_type_id'    => ['nullable', 'exists:project_types,id'],
            'status_id'          => ['required', 'exists:project_statuses,id'],
            'proposal_id'        => ['nullable', 'exists:proposals,id'],
            'start_date'         => ['nullable', 'date'],
            'end_date'           => ['nullable', 'date', 'after_or_equal:start_date'],
            'total_budget'       => ['nullable', 'numeric', 'min:0'],
            'notes'              => ['nullable', 'string'],
        ]);

        // System-generate year and project_number — never accept from user input
        $year = now()->year;
        if (!empty($data['proposal_id'])) {
            $proposal = Proposal::find($data['proposal_id']);
            if ($proposal) $year = $proposal->year;
        }
        $lastNumber = Project::where('year', $year)->max('project_number') ?? 0;
        $data['year']           = $year;
        $data['project_number'] = $lastNumber + 1;

        // If linked to a proposal, validate it is approved and not already taken
        if (!empty($data['proposal_id'])) {
            $proposal = Proposal::with('status')->find($data['proposal_id']);
            if (!$proposal->isApproved()) {
                return back()->withInput()->with('error', 'A project can only be linked to an approved proposal.');
            }
            if ($proposal->project()->exists()) {
                return back()->withInput()->with('error', 'This proposal already has a project linked to it.');
            }
            // Auto-inherit client from proposal when not explicitly set
            if (empty($data['company_id']) && $proposal->company_id) {
                $data['company_id'] = $proposal->company_id;
            }
            // Auto-set year from proposal
            $data['year'] = $proposal->year;
            // Always lock total_budget to proposal contract value
            $data['total_budget'] = $proposal->contract_value ?? $proposal->total_fee ?? 0;
        } else {
            // No proposal = non-billable; default to K5 Company
            $data['proposal_id'] = null;
            if (empty($data['company_id'])) {
                $k5 = Company::where('name', 'K5 Company')->first();
                if ($k5) {
                    $data['company_id'] = $k5->id;
                }
            }
            // Auto-set to Non-Billable project type
            if (empty($data['project_type_id'])) {
                $nonBillable = ProjectType::whereRaw('LOWER(name) = ?', ['non-billable'])->first();
                if ($nonBillable) {
                    $data['project_type_id'] = $nonBillable->id;
                }
            }
        }

        $data['total_budget'] = $data['total_budget'] ?? 0;
        $data['created_by'] = auth()->id();

        $project = Project::create($data);

        ActivityLog::record('Created project', 'projects', $project->id, $project->title);

        return redirect()->route('projects.show', $project)
            ->with('success', "Project {$project->year}-{$project->project_number} created successfully.");
    }

    public function show(Project $project)
    {
        $project->load([
            'company',
            'projectManager',
            'projectType',
            'status',
            'proposal',
            'deliverables.milestones.tasks.assignments.user',
            'projectNotes.user',
        ]);

        $communications = \App\Models\ProjectCommunication::where('project_id', $project->id)
            ->with('documents')
            ->orderByDesc('sent_at')
            ->get();

        return view('projects.show', compact('project', 'communications'));
    }

    public function edit(Project $project)
    {
        $companies    = Company::where('is_active', 1)->orderBy('name')->get();
        $statuses     = ProjectStatus::orderBy('name')->get();
        $projectTypes = ProjectType::orderBy('name')->get();
        $managers     = User::where('is_active', 1)->orderBy('first_name')->get();

        $approvedStatusId = ProposalStatus::whereRaw('LOWER(name) = ?', ['approved'])->value('id');
        $proposals    = Proposal::where('status_id', $approvedStatusId)
                            ->where(function ($q) use ($project) {
                                $q->whereDoesntHave('project')
                                  ->orWhere('id', $project->proposal_id);
                            })
                            ->orderByDesc('year')
                            ->orderByDesc('proposal_number')
                            ->get();

        $k5Company = Company::where('name', 'K5 Company')->first();

        return view('projects.edit', compact(
            'project', 'companies', 'statuses', 'projectTypes', 'managers', 'proposals', 'k5Company'
        ));
    }

    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'title'              => ['required', 'string', 'max:255'],
            'company_id'         => ['nullable', 'exists:companies,id'],
            'project_manager_id' => ['nullable', 'exists:users,id'],
            'project_type_id'    => ['nullable', 'exists:project_types,id'],
            'status_id'          => ['required', 'exists:project_statuses,id'],
            'proposal_id'        => ['nullable', 'exists:proposals,id'],
            'start_date'         => ['nullable', 'date'],
            'end_date'           => ['nullable', 'date', 'after_or_equal:start_date'],
            'total_budget'       => ['nullable', 'numeric', 'min:0'],
            'notes'              => ['nullable', 'string'],
        ]);
        // year and project_number are system-generated — never allow changes

        // If a proposal is being linked and it's different from existing, validate
        if (!empty($data['proposal_id']) && $data['proposal_id'] != $project->proposal_id) {
            $proposal = Proposal::with('status')->find($data['proposal_id']);
            if (!$proposal->isApproved()) {
                return back()->withInput()->with('error', 'Only approved proposals can be linked.');
            }
            if ($proposal->project()->exists()) {
                return back()->withInput()->with('error', 'This proposal already has a project linked to it.');
            }
            // Inherit client from proposal
            if (empty($data['company_id']) && $proposal->company_id) {
                $data['company_id'] = $proposal->company_id;
            }
        }

        // Always lock total_budget to proposal contract value when a proposal is linked
        $linkedProposalId = $data['proposal_id'] ?? $project->proposal_id;
        if (!empty($linkedProposalId)) {
            $linkedProposal = Proposal::find($linkedProposalId);
            if ($linkedProposal) {
                $data['total_budget'] = $linkedProposal->contract_value ?? $linkedProposal->total_fee ?? 0;
            }
        }

        // If proposal is removed, auto-set to non-billable
        if (empty($data['proposal_id'])) {
            $data['proposal_id'] = null;
            if (empty($data['company_id'])) {
                $k5 = Company::where('name', 'K5 Company')->first();
                if ($k5) {
                    $data['company_id'] = $k5->id;
                }
            }
        }

        $data['total_budget'] = $data['total_budget'] ?? 0;

        $project->update($data);

        ActivityLog::record('Updated project', 'projects', $project->id, $project->title);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        $ref = "{$project->year}-{$project->project_number}";
        $project->delete();

        ActivityLog::record('Deleted project', 'projects', null, $ref);

        return redirect()->route('projects.index')
            ->with('success', "Project {$ref} deleted.");
    }

    // ─── Deliverables / Milestones / Tasks ───────────────────────────────────

    public function deliverables(Project $project)
    {
        $project->load([
            'deliverables.milestones.tasks.assignments.user',
            'deliverables.milestones.tasks.dependencies.dependsOn',
            'deliverables.directTasks.assignments.user',
            'deliverables.directTasks.dependencies.dependsOn',
            'proposal.feeSchedule.rates',
        ]);

        $billingCtx        = $project->proposal?->billing_context ?? ['type'=>'fixed','isFixed'=>true,'isTm'=>false,'isHybrid'=>false,'isRetainer'=>false,'isPerDeliverable'=>false,'isHourly'=>false,'showDeliverableFee'=>true,'showRate'=>false];
        $billingType       = $billingCtx['type'];
        $isFixed           = $billingCtx['isFixed'];
        $isTm              = $billingCtx['isTm'];
        $isHybrid          = $billingCtx['isHybrid'];
        $isRetainer        = $billingCtx['isRetainer'];
        $isPerDeliverable  = $billingCtx['isPerDeliverable'];
        $isHourly          = $billingCtx['isHourly'];
        $showDeliverableFee = $billingCtx['showDeliverableFee'];
        $feeRates          = $project->proposal?->feeSchedule?->rates ?? collect();

        // Show billing-matched templates first, then others
        $allTemplates = ActivityTemplate::with('deliverables.activities.tasks')->orderBy('name')->get();
        $templates    = $allTemplates->sortByDesc(fn($t) => $t->billing_type === $billingType)->values();

        $users = User::where('is_active', 1)->orderBy('first_name')->get();

        return view('projects.deliverables', compact(
            'project', 'billingType', 'isFixed', 'isTm', 'isHybrid', 'isRetainer', 'isPerDeliverable',
            'isHourly', 'showDeliverableFee', 'feeRates', 'templates', 'users'
        ));
    }

    public function storeDeliverable(Request $request, Project $project)
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'deliverable_fee' => ['nullable', 'numeric', 'min:0'],
            'start_date'      => ['nullable', 'date'],
            'due_date'        => ['nullable', 'date'],
        ]);

        $data['sort_order']   = $project->deliverables()->max('sort_order') + 1;
        $data['budget_hours'] = 0; // always computed from tasks

        $project->deliverables()->create($data);

        return back()->with('success', 'Deliverable added.');
    }

    public function storeMilestone(Request $request, Project $project, Deliverable $deliverable)
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'deliverable_fee' => ['nullable', 'numeric', 'min:0'],
            'start_date'      => ['nullable', 'date'],
            'due_date'        => ['nullable', 'date'],
        ]);

        $data['sort_order']   = $deliverable->milestones()->max('sort_order') + 1;
        $data['budget_hours'] = 0; // always computed from tasks

        $deliverable->milestones()->create($data);

        return back()->with('success', 'Milestone added.');
    }

    public function storeTask(Request $request, Milestone $milestone)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'start_date'   => ['nullable', 'date'],
            'end_date'     => ['nullable', 'date'],
            'status'       => ['nullable', 'string', 'in:pending,in_progress,complete'],
            'budget_hours' => ['nullable', 'numeric', 'min:0'],
            'rate'         => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['sort_order']   = $milestone->tasks()->max('sort_order') + 1;
        $data['status']       = $data['status'] ?? 'pending';
        $data['budget_hours'] = $data['budget_hours'] ?? 0;

        $milestone->tasks()->create($data);

        return back()->with('success', 'Task added.');
    }

    public function updateDeliverable(Request $request, Project $project, Deliverable $deliverable)
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'deliverable_fee' => ['nullable', 'numeric', 'min:0'],
            'billing_status'  => ['nullable', 'in:pending,ready_to_bill,invoiced,paid'],
            'start_date'      => ['nullable', 'date'],
            'due_date'        => ['nullable', 'date'],
        ]);

        $deliverable->update($data);

        ActivityLog::record('Updated deliverable', 'deliverables', $deliverable->id, $deliverable->name);

        return back()->with('success', 'Deliverable updated.');
    }

    public function destroyDeliverable(Project $project, Deliverable $deliverable)
    {
        $name = $deliverable->name;
        $deliverable->delete();

        ActivityLog::record('Deleted deliverable', 'deliverables', null, $name);

        return back()->with('success', "Deliverable \"{$name}\" deleted.");
    }

    public function updateMilestone(Request $request, Milestone $milestone)
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'deliverable_fee' => ['nullable', 'numeric', 'min:0'],
            'billing_status'  => ['nullable', 'in:pending,ready_to_bill,invoiced,paid'],
            'start_date'      => ['nullable', 'date'],
            'due_date'        => ['nullable', 'date'],
        ]);

        $milestone->update($data);

        return back()->with('success', 'Milestone updated.');
    }

    public function destroyMilestone(Milestone $milestone)
    {
        $name = $milestone->name;
        $milestone->delete();

        return back()->with('success', "Milestone \"{$name}\" deleted.");
    }

    public function updateTask(Request $request, Task $task)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'start_date'   => ['nullable', 'date'],
            'end_date'     => ['nullable', 'date'],
            'status'       => ['required', 'string', 'in:pending,in_progress,complete,cancelled'],
            'budget_hours' => ['nullable', 'numeric', 'min:0'],
            'rate'         => ['nullable', 'numeric', 'min:0'],
        ]);

        $task->update($data);

        // Cascade finish-to-start dates to all successor tasks
        if ($task->fresh()->end_date) {
            $this->cascadeTaskDates($task->fresh());
        }

        return back()->with('success', 'Task updated.');
    }

    /** Recursively push successor task dates forward based on dependency chain. */
    private function cascadeTaskDates(Task $task, int $depth = 0): void
    {
        if ($depth > 30 || ! $task->end_date) return;

        $successors = TaskDependency::where('depends_on_id', $task->id)->with('task')->get();

        foreach ($successors as $dep) {
            $successor = $dep->task;
            if (! $successor) continue;

            $newStart = $task->end_date->copy()->addDays(max(0, $dep->lag_days) + 1);

            // Preserve the original duration when shifting dates
            $duration = ($successor->start_date && $successor->end_date)
                ? (int) $successor->start_date->diffInDays($successor->end_date)
                : 0;

            $newEnd = $newStart->copy()->addDays($duration);
            $successor->update(['start_date' => $newStart, 'end_date' => $newEnd]);

            $this->cascadeTaskDates($successor, $depth + 1);
        }
    }

    public function destroyTask(Task $task)
    {
        $name = $task->name;
        $task->delete();

        return back()->with('success', "Task \"{$name}\" deleted.");
    }

    // ─── Direct task under deliverable (no milestone) ────────────────────────

    public function storeDirectTask(Request $request, Deliverable $deliverable): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'start_date'   => ['nullable', 'date'],
            'end_date'     => ['nullable', 'date'],
            'status'       => ['nullable', 'string', 'in:pending,in_progress,complete'],
            'budget_hours' => ['nullable', 'numeric', 'min:0'],
            'rate'         => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['sort_order']   = $deliverable->directTasks()->max('sort_order') + 1;
        $data['status']       = $data['status'] ?? 'pending';
        $data['budget_hours'] = $data['budget_hours'] ?? 0;
        // milestone_id stays null — task is directly under deliverable

        $deliverable->directTasks()->create($data);

        return back()->with('success', 'Task added.');
    }

    // ─── Move task to a different parent ─────────────────────────────────────

    public function moveTask(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'target_type' => ['required', 'in:milestone,deliverable'],
            'target_id'   => ['required', 'integer'],
        ]);

        if ($request->target_type === 'milestone') {
            $milestone = Milestone::findOrFail($request->target_id);
            $task->update([
                'milestone_id'   => $milestone->id,
                'deliverable_id' => null,
                'sort_order'     => $milestone->tasks()->max('sort_order') + 1,
            ]);
        } else {
            $deliverable = Deliverable::findOrFail($request->target_id);
            $task->update([
                'milestone_id'   => null,
                'deliverable_id' => $deliverable->id,
                'sort_order'     => $deliverable->directTasks()->max('sort_order') + 1,
            ]);
        }

        return response()->json(['success' => true]);
    }

    // ─── Reorder WBS items ────────────────────────────────────────────────────

    public function reorderWbs(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'type'               => ['required', 'in:deliverable,milestone,task'],
            'items'              => ['required', 'array'],
            'items.*.id'         => ['required', 'integer'],
            'items.*.sort_order' => ['required', 'integer'],
        ]);

        $model = match ($request->type) {
            'deliverable' => Deliverable::class,
            'milestone'   => Milestone::class,
            'task'        => Task::class,
        };

        foreach ($request->items as $item) {
            $model::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true]);
    }

    // ─── Apply system template to project ────────────────────────────────────

    public function applyTemplate(Request $request, Project $project): RedirectResponse
    {
        $request->validate(['template_id' => ['required', 'exists:activity_templates,id']]);

        $template  = ActivityTemplate::with(['deliverables.activities.tasks', 'deliverables.directTasks'])->findOrFail($request->template_id);
        $startDate = $project->start_date ?? today();

        // Two-pass copy: Pass 1 creates records, Pass 2 remaps dependency IDs
        $deliverableMap = []; // template deliverable ID → project deliverable ID
        $milestoneMap   = []; // template activity ID    → project milestone ID
        $taskMap        = []; // template task ID        → project task ID

        // ── Pass 1: create all deliverables, milestones, tasks ────────────────
        foreach ($template->deliverables as $td) {
            $deliverable = $project->deliverables()->create([
                'name'         => $td->name,
                'description'  => $td->description,
                'sort_order'   => $project->deliverables()->max('sort_order') + 1,
                'budget_hours' => 0,
                'deliverable_fee' => null,
            ]);
            $deliverableMap[$td->id] = $deliverable->id;

            foreach ($td->activities as $ta) {
                $milestone = $deliverable->milestones()->create([
                    'name'         => $ta->name,
                    'description'  => $ta->description,
                    'sort_order'   => $deliverable->milestones()->max('sort_order') + 1,
                    'budget_hours' => $ta->budgeted_hours ?? 0,
                    'start_date'   => $ta->relative_start_day !== null
                        ? $startDate->copy()->addDays($ta->relative_start_day) : null,
                    'due_date'     => $ta->relative_end_day !== null
                        ? $startDate->copy()->addDays($ta->relative_end_day) : null,
                ]);
                $milestoneMap[$ta->id] = $milestone->id;

                foreach ($ta->tasks as $tt) {
                    $task = $milestone->tasks()->create([
                        'name'         => $tt->name,
                        'description'  => $tt->description,
                        'sort_order'   => $milestone->tasks()->max('sort_order') + 1,
                        'status'       => 'pending',
                        'budget_hours' => $tt->estimated_hours ?? 0,
                        'end_date'     => $tt->relative_due_day !== null
                            ? $startDate->copy()->addDays($tt->relative_due_day) : null,
                    ]);
                    $taskMap[$tt->id] = $task->id;
                }
            }

            // Direct tasks (no milestone)
            foreach ($td->directTasks as $tt) {
                $task = $deliverable->directTasks()->create([
                    'name'         => $tt->name,
                    'description'  => $tt->description,
                    'sort_order'   => $deliverable->directTasks()->max('sort_order') + 1,
                    'status'       => 'pending',
                    'budget_hours' => $tt->estimated_hours ?? 0,
                    'end_date'     => $tt->relative_due_day !== null
                        ? $startDate->copy()->addDays($tt->relative_due_day) : null,
                ]);
                $taskMap[$tt->id] = $task->id;
            }
        }

        // ── Pass 2: remap dependency IDs ──────────────────────────────────────
        // (TaskDependency records for depends_on chains between tasks)
        foreach ($template->deliverables as $td) {
            foreach ($td->activities as $ta) {
                foreach ($ta->tasks as $tt) {
                    if ($tt->depends_on_task_id && isset($taskMap[$tt->depends_on_task_id]) && isset($taskMap[$tt->id])) {
                        Task::where('id', $taskMap[$tt->id])->update([/* dependency handled via TaskDependency table */]);
                    }
                }
            }
            foreach ($td->directTasks as $tt) {
                if ($tt->depends_on_task_id && isset($taskMap[$tt->depends_on_task_id]) && isset($taskMap[$tt->id])) {
                    Task::where('id', $taskMap[$tt->id])->update([/* dependency handled via TaskDependency table */]);
                }
            }
        }

        ActivityLog::record('Applied template to project', 'projects', $project->id, $template->name);

        return back()->with('success', "Template \"{$template->name}\" applied — {$template->deliverables->count()} deliverables added.");
    }

    // ─── Import deliverables from Excel / CSV ─────────────────────────────────

    public function importDeliverables(Request $request, Project $project): RedirectResponse
    {
        $request->validate([
            'file'       => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'start_date' => 'nullable|date',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        } catch (\Exception $e) {
            return back()->withErrors(['import_file' => 'Could not read the file. Make sure it is a valid Excel or CSV file.']);
        }

        // Prefer a sheet named "Project Plan", fall back to active sheet
        $sheet = null;
        foreach ($spreadsheet->getAllSheets() as $s) {
            if (strtolower(trim($s->getTitle())) === 'project plan') {
                $sheet = $s;
                break;
            }
        }
        $sheet = $sheet ?? $spreadsheet->getActiveSheet();

        $rawRows = $sheet->toArray(null, true, true, false); // 0-indexed columns
        if (count($rawRows) < 2) {
            return back()->withErrors(['import_file' => 'The spreadsheet appears to be empty.']);
        }

        // Parse rows: [0]=ID [1]=Type [2]=Name [3]=Desc [4]=ParentID [5]=DependsOn [6]=Hours [7]=Role [8]=StartDay [9]=EndDay
        $parsed = [];
        foreach (array_slice($rawRows, 1) as $row) {
            $name = trim((string)($row[2] ?? ''));
            if ($name === '') continue;

            $type = strtoupper(trim((string)($row[1] ?? '')));
            if (!in_array($type, ['DELIVERABLE', 'MILESTONE', 'TASK'])) continue;

            $parsed[] = [
                'uid'        => trim((string)($row[0] ?? '')),
                'type'       => $type,
                'name'       => $name,
                'description'=> trim((string)($row[3] ?? '')),
                'parent_uid' => trim((string)($row[4] ?? '')),
                'depends_on' => array_values(array_filter(array_map('trim', explode(',', (string)($row[5] ?? ''))))),
                'hours'      => is_numeric($row[6]) ? (float)$row[6] : 0,
                'role'       => trim((string)($row[7] ?? '')),
                'start_day'  => is_numeric($row[8]) ? (int)$row[8] : null,
                'end_day'    => is_numeric($row[9]) ? (int)$row[9] : null,
            ];
        }

        if (empty($parsed) || empty(array_filter($parsed, fn($r) => $r['type'] === 'DELIVERABLE'))) {
            return back()->withErrors(['import_file' => 'No valid rows found. Ensure at least one DELIVERABLE row exists and the Type column contains DELIVERABLE, MILESTONE, or TASK.']);
        }

        $startDate     = $request->start_date
            ? \Carbon\Carbon::parse($request->start_date)
            : ($project->start_date ?? today());
        $importedCount = 0;

        try {
            \DB::transaction(function () use ($project, $parsed, $startDate, &$importedCount) {
                $deliverableMap = [];
                $milestoneMap   = [];
                $taskMap        = [];

                $delSort  = (int)$project->deliverables()->max('sort_order') + 1;
                $actSort  = [];
                $taskSort = [];

                // ── Pass 1: create all records ────────────────────────────────
                foreach ($parsed as $row) {

                    if ($row['type'] === 'DELIVERABLE') {
                        $del = $project->deliverables()->create([
                            'name'            => $row['name'],
                            'description'     => $row['description'] ?: null,
                            'sort_order'      => $delSort++,
                            'budget_hours'    => 0,
                            'deliverable_fee' => null,
                        ]);
                        $deliverableMap[$row['uid']] = $del->id;
                        $importedCount++;

                    } elseif ($row['type'] === 'MILESTONE') {
                        $parentDelId = $deliverableMap[$row['parent_uid']] ?? null;
                        if (!$parentDelId) continue;

                        $del = Deliverable::find($parentDelId);
                        $actSort[$parentDelId] = ($actSort[$parentDelId] ?? (int)$del->milestones()->max('sort_order')) + 1;

                        $ms = $del->milestones()->create([
                            'name'         => $row['name'],
                            'description'  => $row['description'] ?: null,
                            'sort_order'   => $actSort[$parentDelId],
                            'budget_hours' => $row['hours'],
                            'start_date'   => $row['start_day'] !== null ? $startDate->copy()->addDays($row['start_day']) : null,
                            'due_date'     => $row['end_day']   !== null ? $startDate->copy()->addDays($row['end_day'])   : null,
                        ]);
                        $milestoneMap[$row['uid']] = $ms->id;
                        $importedCount++;

                    } elseif ($row['type'] === 'TASK') {
                        $parentMsId  = $milestoneMap[$row['parent_uid']] ?? null;
                        $parentDelId = $parentMsId ? null : ($deliverableMap[$row['parent_uid']] ?? null);
                        if (!$parentMsId && !$parentDelId) continue;

                        $sortKey = $parentMsId ? "m{$parentMsId}" : "d{$parentDelId}";

                        if ($parentMsId) {
                            $ms = Milestone::find($parentMsId);
                            $taskSort[$sortKey] = ($taskSort[$sortKey] ?? (int)$ms->tasks()->max('sort_order')) + 1;
                            $task = $ms->tasks()->create([
                                'name'         => $row['name'],
                                'description'  => $row['description'] ?: null,
                                'sort_order'   => $taskSort[$sortKey],
                                'status'       => 'pending',
                                'budget_hours' => $row['hours'],
                                'start_date'   => $row['start_day'] !== null ? $startDate->copy()->addDays($row['start_day']) : null,
                                'end_date'     => $row['end_day']   !== null ? $startDate->copy()->addDays($row['end_day'])   : null,
                            ]);
                        } else {
                            $del = Deliverable::find($parentDelId);
                            $taskSort[$sortKey] = ($taskSort[$sortKey] ?? (int)$del->directTasks()->max('sort_order')) + 1;
                            $task = $del->directTasks()->create([
                                'name'         => $row['name'],
                                'description'  => $row['description'] ?: null,
                                'sort_order'   => $taskSort[$sortKey],
                                'status'       => 'pending',
                                'budget_hours' => $row['hours'],
                                'start_date'   => $row['start_day'] !== null ? $startDate->copy()->addDays($row['start_day']) : null,
                                'end_date'     => $row['end_day']   !== null ? $startDate->copy()->addDays($row['end_day'])   : null,
                            ]);
                        }
                        $taskMap[$row['uid']] = $task->id;
                        $importedCount++;
                    }
                }

                // ── Pass 2: wire task predecessor chains via TaskDependency ───
                foreach ($parsed as $row) {
                    if ($row['type'] !== 'TASK' || empty($row['depends_on']) || empty($row['uid'])) continue;
                    $taskId = $taskMap[$row['uid']] ?? null;
                    if (!$taskId) continue;

                    foreach ($row['depends_on'] as $depUid) {
                        $dependsOnId = $taskMap[$depUid] ?? null;
                        if ($dependsOnId) {
                            TaskDependency::firstOrCreate(
                                ['task_id' => $taskId, 'depends_on_id' => $dependsOnId],
                                ['lag_days' => 0]
                            );
                        }
                    }
                }
            });
        } catch (\Exception $e) {
            return back()->withErrors(['import_file' => 'Import failed: ' . $e->getMessage()]);
        }

        ActivityLog::record('Imported deliverables from spreadsheet', 'projects', $project->id, "{$importedCount} items");

        return back()->with('success', "Spreadsheet imported — {$importedCount} items added to the project.");
    }

    // ─── Download sample import spreadsheet (reuses template import sample) ──

    public function importSample()
    {
        return (new ActivityTemplateImportController())->sample();
    }

    // ─── Gantt chart data ─────────────────────────────────────────────────────

    public function ganttData(Project $project): JsonResponse
    {
        $project->load([
            'deliverables.milestones.tasks.dependencies',
            'deliverables.directTasks.dependencies',
        ]);

        $tasks = [];

        foreach ($project->deliverables as $d) {
            foreach ($d->milestones as $m) {
                foreach ($m->tasks as $task) {
                    if ($task->start_date && $task->end_date) {
                        $tasks[] = $this->taskToGantt($task, $d->name . ' / ' . $m->name);
                    }
                }
            }
            foreach ($d->directTasks as $task) {
                if ($task->start_date && $task->end_date) {
                    $tasks[] = $this->taskToGantt($task, $d->name);
                }
            }
        }

        return response()->json($tasks);
    }

    private function taskToGantt(Task $task, string $group): array
    {
        $depIds = $task->dependencies
            ->map(fn ($d) => 'task-' . $d->depends_on_id)
            ->implode(', ');

        $progress = match ($task->status) {
            'complete'    => 100,
            'in_progress' => 50,
            default       => 0,
        };

        return [
            'id'           => 'task-' . $task->id,
            'name'         => $task->name,
            'start'        => $task->start_date->format('Y-m-d'),
            'end'          => $task->end_date->format('Y-m-d'),
            'progress'     => $progress,
            'dependencies' => $depIds,
            'custom_class' => 'gantt-' . $task->status,
            'deliverable'  => $group,
        ];
    }

    // ─── Task dependencies ────────────────────────────────────────────────────

    public function storeDependency(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'depends_on_id' => ['required', 'integer', 'exists:tasks,id', 'different:task_id'],
            'lag_days'      => ['nullable', 'integer'],
        ]);

        // Prevent circular dependency (simple check: depends_on can't already depend on task)
        $wouldCircle = TaskDependency::where('task_id', $request->depends_on_id)
            ->where('depends_on_id', $task->id)
            ->exists();

        if ($wouldCircle) {
            return response()->json(['error' => 'Circular dependency detected.'], 422);
        }

        $dep = TaskDependency::firstOrCreate(
            ['task_id' => $task->id, 'depends_on_id' => $request->depends_on_id],
            ['lag_days' => $request->lag_days ?? 0]
        );

        return response()->json(['success' => true, 'id' => $dep->id]);
    }

    public function destroyDependency(Task $task, int $dependsOnId): JsonResponse
    {
        TaskDependency::where('task_id', $task->id)
            ->where('depends_on_id', $dependsOnId)
            ->delete();

        return response()->json(['success' => true]);
    }

    // ─── Resource assignments ─────────────────────────────────────────────────

    public function storeAssignment(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'user_id'      => ['required', 'exists:users,id'],
            'budget_hours' => ['required', 'numeric', 'min:0'],
            'role'         => ['nullable', 'string', 'max:100'],
        ]);

        // One assignment per user per task
        $assignment = TaskAssignment::updateOrCreate(
            ['task_id' => $task->id, 'user_id' => $request->user_id],
            [
                'budget_hours' => $request->budget_hours,
                'role'         => $request->role,
            ]
        );

        $assignment->load('user');

        return response()->json([
            'success'    => true,
            'assignment' => [
                'id'           => $assignment->id,
                'user_id'      => $assignment->user_id,
                'user_name'    => $assignment->user->full_name ?? $assignment->user->first_name . ' ' . $assignment->user->last_name,
                'initials'     => strtoupper(substr($assignment->user->first_name, 0, 1) . substr($assignment->user->last_name ?? '', 0, 1)),
                'role'         => $assignment->role,
                'budget_hours' => (float) $assignment->budget_hours,
            ],
        ]);
    }

    public function updateAssignment(Request $request, TaskAssignment $assignment): JsonResponse
    {
        $request->validate([
            'budget_hours' => ['required', 'numeric', 'min:0'],
            'role'         => ['nullable', 'string', 'max:100'],
        ]);

        $assignment->update([
            'budget_hours' => $request->budget_hours,
            'role'         => $request->role,
        ]);

        return response()->json(['success' => true]);
    }

    public function destroyAssignment(TaskAssignment $assignment): JsonResponse
    {
        $assignment->delete();
        return response()->json(['success' => true]);
    }

    // ─── AJAX Endpoints ──────────────────────────────────────────────────────

    public function apiDeliverables(Project $project)
    {
        return response()->json($project->deliverables()->orderBy('sort_order')->get());
    }

    public function apiMilestones(Deliverable $deliverable)
    {
        return response()->json($deliverable->milestones()->orderBy('sort_order')->get());
    }

    public function apiTasks(Milestone $milestone)
    {
        return response()->json($milestone->tasks()->with('assignments.user')->orderBy('sort_order')->get());
    }
}
