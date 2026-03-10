<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Deliverable;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\ProjectType;
use App\Models\Proposal;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::with(['company', 'projectManager', 'projectType', 'status'])
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
        $proposals    = Proposal::whereDoesntHave('project')
                            ->orderByDesc('year')
                            ->orderByDesc('proposal_number')
                            ->get();

        $year       = now()->year;
        $lastNumber = Project::where('year', $year)->max('project_number') ?? 0;
        $nextNumber = $lastNumber + 1;

        return view('projects.create', compact(
            'companies', 'statuses', 'projectTypes', 'managers', 'proposals', 'year', 'nextNumber'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'year'               => ['required', 'integer', 'min:2000'],
            'project_number'     => ['required', 'integer', 'min:1'],
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
        $proposals    = Proposal::whereDoesntHave('project')
                            ->orWhere('id', $project->proposal_id)
                            ->orderByDesc('year')
                            ->orderByDesc('proposal_number')
                            ->get();

        return view('projects.edit', compact(
            'project', 'companies', 'statuses', 'projectTypes', 'managers', 'proposals'
        ));
    }

    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'year'               => ['required', 'integer', 'min:2000'],
            'project_number'     => ['required', 'integer', 'min:1'],
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
        $project->load('deliverables.milestones.tasks');
        return view('projects.deliverables', compact('project'));
    }

    public function storeDeliverable(Request $request, Project $project)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $data['sort_order'] = $project->deliverables()->max('sort_order') + 1;

        $project->deliverables()->create($data);

        return back()->with('success', 'Deliverable added.');
    }

    public function storeMilestone(Request $request, Project $project, Deliverable $deliverable)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $data['sort_order'] = $deliverable->milestones()->max('sort_order') + 1;

        $deliverable->milestones()->create($data);

        return back()->with('success', 'Milestone added.');
    }

    public function storeTask(Request $request, Milestone $milestone)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date'],
            'status'      => ['nullable', 'string', 'in:pending,in_progress,complete'],
        ]);

        $data['sort_order'] = $milestone->tasks()->max('sort_order') + 1;
        $data['status']     = $data['status'] ?? 'pending';

        $milestone->tasks()->create($data);

        return back()->with('success', 'Task added.');
    }

    public function updateTask(Request $request, Task $task)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date'],
            'status'      => ['required', 'string', 'in:pending,in_progress,complete'],
        ]);

        $task->update($data);

        return back()->with('success', 'Task updated.');
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
