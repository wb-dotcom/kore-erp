@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">My Tasks</h4>
        <div style="font-size:0.75rem; color:#6b7280;">{{ auth()->user()->full_name }}</div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

{{-- Stats Strip --}}
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="kore-card text-center py-3">
            <div style="font-size:1.6rem; font-weight:700; color:#f59e0b;">{{ $statusCounts['in_progress'] }}</div>
            <div style="font-size:0.72rem; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em;">In Progress</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="kore-card text-center py-3">
            <div style="font-size:1.6rem; font-weight:700; color:#6b7280;">{{ $statusCounts['pending'] }}</div>
            <div style="font-size:0.72rem; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em;">Pending</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="kore-card text-center py-3">
            <div style="font-size:1.6rem; font-weight:700; color:#22c55e;">{{ $statusCounts['complete'] }}</div>
            <div style="font-size:0.72rem; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em;">Complete</div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="kore-card mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-sm-3">
            <label class="form-label" style="font-size:0.75rem; font-weight:600;">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="pending"     {{ request('status') === 'pending'     ? 'selected' : '' }}>Pending</option>
                <option value="complete"    {{ request('status') === 'complete'    ? 'selected' : '' }}>Complete</option>
            </select>
        </div>
        <div class="col-sm-4">
            <label class="form-label" style="font-size:0.75rem; font-weight:600;">Project</label>
            <select name="project_id" class="form-select form-select-sm">
                <option value="">All Projects</option>
                @foreach($projects as $p)
                <option value="{{ $p->id }}" {{ request('project_id') == $p->id ? 'selected' : '' }}>
                    {{ $p->title }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-auto">
            <button type="submit" class="btn btn-sm btn-primary">Filter</button>
            <a href="{{ route('tasks.mine') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
        </div>
    </form>
</div>

{{-- Task List --}}
@forelse($assignments as $assignment)
@php
    $task       = $assignment->task;
    $milestone  = $task->milestone;
    $deliverable= $milestone->deliverable;
    $project    = $deliverable->project;
    $isComplete = $task->status === 'complete';
@endphp

<div class="kore-card mb-2 task-card {{ $isComplete ? 'opacity-75' : '' }}"
    data-assignment="{{ $assignment->id }}">
    <div class="d-flex align-items-start gap-3">

        {{-- Status icon / toggle --}}
        <div class="pt-1" style="flex-shrink:0;">
            @php
                $icon = match($task->status) {
                    'complete'    => 'bi-check-circle-fill text-success',
                    'in_progress' => 'bi-play-circle-fill text-primary',
                    default       => 'bi-circle text-secondary',
                };
            @endphp
            <i class="bi {{ $icon }} status-icon" style="font-size:1.1rem; cursor:pointer;"
                data-assignment="{{ $assignment->id }}"
                data-status="{{ $task->status }}"
                title="Click to cycle status"
                onclick="cycleStatus(this)"></i>
        </div>

        {{-- Task details --}}
        <div style="flex:1; min-width:0;">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="fw-600" style="font-size:0.88rem; {{ $isComplete ? 'text-decoration:line-through; color:#9ca3af;' : '' }}">
                    {{ $task->name }}
                </span>
                @php
                    $badgeCls = match($task->status) {
                        'in_progress' => 'active',
                        'complete'    => 'approved',
                        default       => 'draft',
                    };
                @endphp
                <span class="badge badge-{{ $badgeCls }} task-status-badge">
                    {{ str_replace('_', ' ', ucfirst($task->status)) }}
                </span>
                @if($task->end_date)
                @php $overdue = !$isComplete && $task->end_date->isPast(); @endphp
                <span style="font-size:0.72rem; color:{{ $overdue ? '#ef4444' : '#9ca3af' }};">
                    @if($overdue)<i class="bi bi-exclamation-circle me-1"></i>@endif
                    Due {{ $task->end_date->format('M d, Y') }}
                </span>
                @endif
            </div>
            <div class="mt-1" style="font-size:0.75rem; color:#9ca3af;">
                <a href="{{ route('projects.show', $project) }}" class="text-decoration-none" style="color:#4c8bf5;">
                    {{ $project->title }}
                </a>
                <span class="mx-1">·</span>{{ $deliverable->name }}
                <span class="mx-1">·</span>{{ $milestone->name }}
            </div>
            @if($task->description)
            <div class="mt-1" style="font-size:0.78rem; color:#6b7280;">{{ Str::limit($task->description, 120) }}</div>
            @endif
            @if($assignment->notes)
            <div class="mt-1" style="font-size:0.75rem; color:#9ca3af; font-style:italic;">
                Note: {{ $assignment->notes }}
            </div>
            @endif
        </div>

        {{-- Quick status dropdown --}}
        <div style="flex-shrink:0;">
            <form action="{{ route('tasks.update', $assignment) }}" method="POST" class="d-flex align-items-center gap-1">
                @csrf @method('PUT')
                <select name="task_status" class="form-select form-select-sm py-0"
                    style="font-size:0.75rem; width:auto; height:28px; border-color:#e5e7eb;"
                    onchange="this.form.submit()">
                    <option value="pending"     {{ $task->status === 'pending'     ? 'selected' : '' }}>Pending</option>
                    <option value="in_progress" {{ $task->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="complete"    {{ $task->status === 'complete'    ? 'selected' : '' }}>Complete</option>
                </select>
            </form>
        </div>

    </div>
</div>

@empty
<div class="kore-card text-center py-5" style="color:#9ca3af;">
    <i class="bi bi-check2-all fs-2 d-block mb-2"></i>
    @if(request()->hasAny(['status', 'project_id']))
        No tasks match the current filter.
        <a href="{{ route('tasks.mine') }}" class="d-block mt-2 text-primary" style="font-size:0.8rem;">Clear filters</a>
    @else
        You have no tasks assigned yet.
    @endif
</div>
@endforelse

@endsection

@push('scripts')
<script>
const STATUS_CYCLE = ['pending', 'in_progress', 'complete'];
const STATUS_ICONS = {
    'pending':     'bi-circle text-secondary',
    'in_progress': 'bi-play-circle-fill text-primary',
    'complete':    'bi-check-circle-fill text-success',
};
const STATUS_BADGE = {
    'pending':     'draft',
    'in_progress': 'active',
    'complete':    'approved',
};

function cycleStatus(icon) {
    const assignmentId = icon.dataset.assignment;
    const current      = icon.dataset.status;
    const nextIdx      = (STATUS_CYCLE.indexOf(current) + 1) % STATUS_CYCLE.length;
    const nextStatus   = STATUS_CYCLE[nextIdx];

    fetch(`/my-tasks/${assignmentId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ task_status: nextStatus })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Update icon
            icon.className = `bi ${STATUS_ICONS[nextStatus]} status-icon`;
            icon.dataset.status = nextStatus;

            // Update badge
            const card  = icon.closest('.task-card');
            const badge = card.querySelector('.task-status-badge');
            if (badge) {
                badge.className = `badge badge-${STATUS_BADGE[nextStatus]} task-status-badge`;
                badge.textContent = nextStatus.replace('_', ' ').replace(/^\w/, c => c.toUpperCase());
            }

            // Strike task name if complete
            const nameEl = card.querySelector('.fw-600');
            if (nextStatus === 'complete') {
                nameEl.style.textDecoration = 'line-through';
                nameEl.style.color = '#9ca3af';
                card.classList.add('opacity-75');
            } else {
                nameEl.style.textDecoration = '';
                nameEl.style.color = '';
                card.classList.remove('opacity-75');
            }
        }
    });
}
</script>
@endpush
