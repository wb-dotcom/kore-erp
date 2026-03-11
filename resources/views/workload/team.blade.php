@extends('layouts.app')

@section('title', 'Team Workload')

@section('content')

{{-- ── Header ── --}}
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">Team Workload</h4>
        <div style="font-size:0.75rem; color:#6b7280;">
            Week of {{ $weekStart->format('M j') }}–{{ $weekEnd->format('M j, Y') }}
        </div>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <a href="{{ route('workload.index', ['week' => $weekStart->format('Y-m-d')]) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-person me-1"></i>My Pipeline
        </a>
        <a href="{{ route('workload.team', ['week' => $prevWeek]) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-chevron-left"></i>
        </a>
        <a href="{{ route('workload.team') }}" class="btn btn-sm btn-outline-primary">Today</a>
        <a href="{{ route('workload.team', ['week' => $nextWeek]) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-chevron-right"></i>
        </a>
    </div>
</div>

<div class="row g-4">

    {{-- ── Left: Team Resource Grid ── --}}
    <div class="col-lg-8">
        @foreach($users as $user)
        @php
            $userItems   = $user->workScheduleItems;
            $totalHrs    = $userItems->sum('scheduled_hours');
            $capacity    = 40;
            $pct         = min(100, $totalHrs / $capacity * 100);
            $barColor    = $pct >= 90 ? '#ef4444' : ($pct >= 70 ? '#f59e0b' : '#22c55e');
        @endphp
        <div class="kore-card mb-3" id="user-card-{{ $user->id }}">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div style="width:34px; height:34px; border-radius:50%; background:#4c8bf5; color:#fff;
                            display:flex; align-items:center; justify-content:center;
                            font-size:0.75rem; font-weight:700; flex-shrink:0;">
                    {{ $user->initials }}
                </div>
                <div style="flex:1; min-width:0;">
                    <div style="font-weight:600; font-size:0.85rem;">{{ $user->full_name }}</div>
                    <div style="font-size:0.7rem; color:#6b7280;">{{ $user->department ?? $user->role?->name }}</div>
                </div>
                <div class="text-end flex-shrink-0">
                    <div style="font-size:0.9rem; font-weight:700; color:{{ $barColor }};">{{ number_format($totalHrs, 1) }}h</div>
                    <div style="font-size:0.65rem; color:#9ca3af;">/ {{ $capacity }}h capacity</div>
                </div>
                <button class="btn btn-sm btn-outline-primary flex-shrink-0"
                        style="font-size:0.65rem;"
                        onclick="openAssignModal({{ $user->id }}, '{{ addslashes($user->full_name) }}')">
                    <i class="bi bi-plus me-1"></i>Assign
                </button>
            </div>

            {{-- Capacity bar --}}
            <div class="mb-2" style="height:6px; background:#e5e7eb; border-radius:3px; overflow:hidden;">
                <div style="height:100%; width:{{ $pct }}%; background:{{ $barColor }}; border-radius:3px; transition:.3s;"></div>
            </div>

            {{-- Scheduled items --}}
            @if($userItems->isEmpty())
            <div style="font-size:0.72rem; color:#9ca3af; padding:.25rem 0;">No tasks scheduled this week.</div>
            @else
            <div>
                @foreach($userItems as $item)
                @php
                    $task = $item->taskAssignment?->task;
                    $project = $task?->milestone?->deliverable?->project;
                @endphp
                <div class="d-flex align-items-center gap-2 py-1 border-bottom" style="font-size:0.75rem;">
                    <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size:0.65rem; min-width:36px;">
                        {{ $item->scheduled_hours }}h
                    </span>
                    <span style="flex:1; min-width:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        {{ $task?->name ?? '—' }}
                    </span>
                    <span style="color:#9ca3af; font-size:0.68rem; flex-shrink:0;">
                        {{ $project?->title ?? '' }}
                    </span>
                    <button class="btn btn-sm" style="padding:0 4px; font-size:0.7rem; color:#9ca3af; line-height:1;"
                            onclick="removeTeamItem({{ $item->id }}, this)">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- ── Right: Unassigned Tasks ── --}}
    <div class="col-lg-4">
        <div class="kore-card">
            <h6 class="mb-3" style="font-size:0.8rem; font-weight:600;">
                <i class="bi bi-exclamation-circle me-1 text-warning"></i>Unassigned Tasks
            </h6>

            {{-- Project filter --}}
            <div class="mb-3">
                <select id="project-filter" class="form-select form-select-sm" onchange="filterUnassigned(this.value)">
                    <option value="">All Projects</option>
                    @foreach($projects as $p)
                    <option value="{{ $p->id }}">{{ $p->title }}</option>
                    @endforeach
                </select>
            </div>

            <div id="unassigned-list">
                @forelse($unassignedTasks as $task)
                @php $deliverable = $task->milestone?->deliverable; $project = $deliverable?->project; @endphp
                <div class="border rounded p-2 mb-2 unassigned-task" data-project-id="{{ $project?->id }}" style="background:#fafafa;">
                    <div style="font-weight:600; font-size:0.78rem;">{{ $task->name }}</div>
                    <div style="font-size:0.68rem; color:#6b7280; margin-bottom:.5rem;">
                        {{ $project?->title ?? '—' }}
                        @if($deliverable) · {{ $deliverable->name }} @endif
                        @if($task->end_date) · Due {{ $task->end_date->format('M j') }} @endif
                    </div>
                    <button class="btn btn-sm btn-primary w-100" style="font-size:0.65rem;"
                            onclick="openAssignModalForTask({{ $task->id }}, '{{ addslashes($task->name) }}')">
                        <i class="bi bi-person-plus me-1"></i>Assign to Resource
                    </button>
                </div>
                @empty
                <div class="text-center py-3" style="color:#9ca3af; font-size:0.8rem;">
                    <i class="bi bi-check2-all" style="font-size:1.5rem; display:block; color:#22c55e;"></i>
                    All tasks assigned!
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- ── Assign Modal ── --}}
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0" style="font-size:0.85rem;">Assign Work to Resource</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3" id="assign-resource-row">
                    <label class="form-label" style="font-size:0.75rem; font-weight:600;">Resource</label>
                    <select id="assign-user-id" class="form-select form-select-sm">
                        @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3" id="assign-task-row">
                    <label class="form-label" style="font-size:0.75rem; font-weight:600;">Task (if assigning from unassigned list)</label>
                    <div id="assign-task-name" style="font-size:0.8rem; font-weight:600; color:#374151;"></div>
                    <input type="hidden" id="assign-task-id">
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label" style="font-size:0.75rem; font-weight:600;">Week</label>
                        <input type="date" id="assign-week-start" class="form-control form-control-sm"
                               value="{{ $weekStart->format('Y-m-d') }}">
                    </div>
                    <div class="col-6">
                        <label class="form-label" style="font-size:0.75rem; font-weight:600;">Planned Hours</label>
                        <input type="number" id="assign-hours" class="form-control form-control-sm"
                               step="0.25" min="0.25" max="80" value="4">
                    </div>
                </div>
                <div class="mt-2">
                    <label class="form-label" style="font-size:0.75rem; font-weight:600;">Budget Hours (assignment total)</label>
                    <input type="number" id="assign-budget-hours" class="form-control form-control-sm"
                           step="0.25" min="0" value="">
                    <div style="font-size:0.65rem; color:#9ca3af; margin-top:2px;">Leave blank to keep existing assignment.</div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="confirmAssign()">
                    <i class="bi bi-person-check me-1"></i>Assign & Schedule
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let assignMode = null; // 'user' or 'task'
let assignUserId = null;
let assignTaskId = null;

function openAssignModal(userId, userName) {
    assignMode   = 'user';
    assignUserId = userId;
    // Pre-select user in dropdown
    document.getElementById('assign-user-id').value = userId;
    document.getElementById('assign-task-name').textContent = '';
    document.getElementById('assign-task-id').value = '';
    document.getElementById('assign-task-row').querySelector('label').textContent = 'Task — select a task from the project';
    new bootstrap.Modal(document.getElementById('assignModal')).show();
}

function openAssignModalForTask(taskId, taskName) {
    assignMode   = 'task';
    assignTaskId = taskId;
    document.getElementById('assign-task-name').textContent = taskName;
    document.getElementById('assign-task-id').value = taskId;
    new bootstrap.Modal(document.getElementById('assignModal')).show();
}

function confirmAssign() {
    const userId      = document.getElementById('assign-user-id').value;
    const taskId      = document.getElementById('assign-task-id').value || assignTaskId;
    const weekStart   = document.getElementById('assign-week-start').value;
    const hours       = document.getElementById('assign-hours').value;
    const budgetHours = document.getElementById('assign-budget-hours').value;

    if (! taskId) { alert('Please select a task.'); return; }
    if (! userId) { alert('Please select a resource.'); return; }

    fetch('/workload/assign', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({
            task_id: parseInt(taskId),
            user_id: parseInt(userId),
            week_start: weekStart,
            scheduled_hours: parseFloat(hours),
            budget_hours: budgetHours ? parseFloat(budgetHours) : undefined,
        }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('assignModal')).hide();
            location.reload();
        }
    });
}

function removeTeamItem(itemId, btn) {
    if (! confirm('Remove this task from the schedule?')) return;
    fetch(`/workload/items/${itemId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF },
    })
    .then(() => location.reload());
}

function filterUnassigned(projectId) {
    document.querySelectorAll('.unassigned-task').forEach(el => {
        el.style.display = (! projectId || el.dataset.projectId == projectId) ? '' : 'none';
    });
}
</script>
@endpush
