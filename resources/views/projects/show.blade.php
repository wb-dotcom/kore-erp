@extends('layouts.app')

@section('content')

@php
    $ref = $project->year.'-'.str_pad($project->project_number, 3, '0', STR_PAD_LEFT);
@endphp

{{-- Header --}}
<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h4 class="mb-0 fw-700" style="font-size:1rem;">{{ $project->title }}</h4>
            <div style="font-size:0.75rem; color:#6b7280;">
                Project {{ $ref }}
                @if($project->company)
                    · <a href="{{ route('companies.show', $project->company) }}"
                        class="text-decoration-none" style="color:#4c8bf5;">{{ $project->company->name }}</a>
                @endif
            </div>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('projects.deliverables', $project) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-list-task me-1"></i> Deliverables
        </a>
        <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

<div class="row g-4">

    {{-- Left: Details --}}
    <div class="col-lg-8">

        {{-- Summary Card --}}
        <div class="kore-card mb-4">
            <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">
                Project Info
            </div>
            <div class="row g-3">
                <div class="col-sm-4">
                    <div style="font-size:0.72rem; color:#9ca3af; font-weight:600; text-transform:uppercase;">Status</div>
                    @php
                        $cls = match($project->status?->name) {
                            'Active'    => 'active',
                            'Complete'  => 'approved',
                            'On Hold'   => 'draft',
                            'Cancelled' => 'rejected',
                            default     => 'draft',
                        };
                    @endphp
                    <span class="badge badge-{{ $cls }} mt-1">{{ $project->status?->name ?? '—' }}</span>
                </div>
                <div class="col-sm-4">
                    <div style="font-size:0.72rem; color:#9ca3af; font-weight:600; text-transform:uppercase;">Project Type</div>
                    <div style="font-size:0.85rem; margin-top:2px;">{{ $project->projectType?->name ?? '—' }}</div>
                </div>
                <div class="col-sm-4">
                    <div style="font-size:0.72rem; color:#9ca3af; font-weight:600; text-transform:uppercase;">Budget</div>
                    <div style="font-size:0.85rem; margin-top:2px; font-weight:600;">
                        {{ $project->total_budget ? '$'.number_format($project->total_budget, 2) : '—' }}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div style="font-size:0.72rem; color:#9ca3af; font-weight:600; text-transform:uppercase;">Project Manager</div>
                    <div style="font-size:0.85rem; margin-top:2px;">{{ $project->projectManager?->full_name ?? '—' }}</div>
                </div>
                <div class="col-sm-4">
                    <div style="font-size:0.72rem; color:#9ca3af; font-weight:600; text-transform:uppercase;">Start Date</div>
                    <div style="font-size:0.85rem; margin-top:2px;">{{ $project->start_date?->format('M d, Y') ?? '—' }}</div>
                </div>
                <div class="col-sm-4">
                    <div style="font-size:0.72rem; color:#9ca3af; font-weight:600; text-transform:uppercase;">End Date</div>
                    <div style="font-size:0.85rem; margin-top:2px;">{{ $project->end_date?->format('M d, Y') ?? '—' }}</div>
                </div>
                @if($project->proposal)
                <div class="col-12">
                    <div style="font-size:0.72rem; color:#9ca3af; font-weight:600; text-transform:uppercase;">Linked Proposal</div>
                    <div style="font-size:0.85rem; margin-top:2px;">
                        <a href="{{ route('proposals.show', $project->proposal) }}" class="text-decoration-none" style="color:#4c8bf5;">
                            {{ $project->proposal->year }}-{{ $project->proposal->proposal_number }}
                            — {{ $project->proposal->title }}
                        </a>
                    </div>
                </div>
                @endif
                @if($project->notes)
                <div class="col-12">
                    <div style="font-size:0.72rem; color:#9ca3af; font-weight:600; text-transform:uppercase;">Notes</div>
                    <div style="font-size:0.85rem; margin-top:4px; white-space:pre-wrap;">{{ $project->notes }}</div>
                </div>
                @endif
            </div>
        </div>

        {{-- Deliverables Summary --}}
        <div class="kore-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="fw-600" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">
                    Deliverables & Tasks
                </div>
                <a href="{{ route('projects.deliverables', $project) }}" class="btn btn-sm btn-outline-primary" style="font-size:0.75rem;">
                    <i class="bi bi-pencil-square me-1"></i> Manage
                </a>
            </div>

            @forelse($project->deliverables as $deliverable)
            <div class="mb-3">
                <div class="fw-600 mb-1" style="font-size:0.85rem;">
                    <i class="bi bi-folder2 me-1" style="color:#4c8bf5;"></i>{{ $deliverable->name }}
                </div>
                @foreach($deliverable->milestones as $milestone)
                <div class="ms-3 mb-2">
                    <div style="font-size:0.8rem; color:#374151; font-weight:500;">
                        <i class="bi bi-flag me-1" style="color:#f59e0b;"></i>{{ $milestone->name }}
                    </div>
                    @foreach($milestone->tasks as $task)
                    <div class="ms-3 d-flex align-items-center gap-2 mt-1">
                        @php
                            $tIcon = match($task->status) {
                                'complete'    => 'bi-check-circle-fill text-success',
                                'in_progress' => 'bi-play-circle-fill text-primary',
                                default       => 'bi-circle text-secondary',
                            };
                        @endphp
                        <i class="bi {{ $tIcon }}" style="font-size:0.75rem;"></i>
                        <span style="font-size:0.78rem; color:#6b7280;">{{ $task->name }}</span>
                    </div>
                    @endforeach
                </div>
                @endforeach
            </div>
            @empty
            <div class="text-center py-3" style="font-size:0.8rem; color:#9ca3af;">
                No deliverables yet.
                <a href="{{ route('projects.deliverables', $project) }}" class="text-primary">Add one</a>
            </div>
            @endforelse
        </div>

    </div>

    {{-- Right: Side panel --}}
    <div class="col-lg-4">
        <div class="kore-card mb-4">
            <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">
                Quick Stats
            </div>
            @php
                $totalTasks    = $project->deliverables->flatMap(fn($d) => $d->milestones)->flatMap(fn($m) => $m->tasks);
                $completeTasks = $totalTasks->where('status', 'complete')->count();
                $pct           = $totalTasks->count() ? round($completeTasks / $totalTasks->count() * 100) : 0;
            @endphp
            <div class="d-flex justify-content-between mb-1" style="font-size:0.78rem;">
                <span>Task Completion</span>
                <span class="fw-600">{{ $pct }}%</span>
            </div>
            <div class="progress mb-3" style="height:6px;">
                <div class="progress-bar bg-primary" style="width:{{ $pct }}%"></div>
            </div>
            <div class="row g-2 text-center">
                <div class="col-4">
                    <div style="font-size:1.2rem; font-weight:700; color:#374151;">{{ $project->deliverables->count() }}</div>
                    <div style="font-size:0.7rem; color:#9ca3af;">Deliverables</div>
                </div>
                <div class="col-4">
                    <div style="font-size:1.2rem; font-weight:700; color:#374151;">{{ $totalTasks->count() }}</div>
                    <div style="font-size:0.7rem; color:#9ca3af;">Tasks</div>
                </div>
                <div class="col-4">
                    <div style="font-size:1.2rem; font-weight:700; color:#22c55e;">{{ $completeTasks }}</div>
                    <div style="font-size:0.7rem; color:#9ca3af;">Done</div>
                </div>
            </div>
        </div>

        <div class="kore-card">
            <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">
                Actions
            </div>
            <div class="d-grid gap-2">
                <a href="{{ route('projects.deliverables', $project) }}" class="btn btn-sm btn-outline-secondary text-start">
                    <i class="bi bi-list-task me-2"></i> Manage Deliverables
                </a>
                <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-outline-secondary text-start">
                    <i class="bi bi-pencil me-2"></i> Edit Project
                </a>
                <form action="{{ route('projects.destroy', $project) }}" method="POST"
                    onsubmit="return confirm('Delete this project? This cannot be undone.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger text-start w-100">
                        <i class="bi bi-trash me-2"></i> Delete Project
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

@endsection
