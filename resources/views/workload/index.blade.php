@extends('layouts.app')

@section('title', 'My Workload Pipeline')

@section('content')

{{-- ── Header ── --}}
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">My Workload Pipeline</h4>
        <div style="font-size:0.75rem; color:#6b7280;">
            {{ auth()->user()->full_name }} &mdash; Week of {{ $weekStart->format('M j') }}–{{ $weekEnd->format('M j, Y') }}
        </div>
    </div>
    <div class="d-flex gap-2 align-items-center">
        @if(auth()->user()->isManager())
        <a href="{{ route('workload.team', ['week' => $weekStart->format('Y-m-d')]) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-people me-1"></i>Team View
        </a>
        @endif
        <a href="{{ route('workload.index', ['week' => $prevWeek]) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-chevron-left"></i>
        </a>
        <a href="{{ route('workload.index') }}" class="btn btn-sm btn-outline-primary" title="Current week">Today</a>
        <a href="{{ route('workload.index', ['week' => $nextWeek]) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-chevron-right"></i>
        </a>
    </div>
</div>

{{-- ── Stats Strip ── --}}
<div class="row g-3 mb-4">
    <div class="col-sm-3">
        <div class="kore-card text-center py-3">
            <div style="font-size:1.5rem; font-weight:700; color:#4c8bf5;">{{ number_format($totalScheduledHours, 1) }}</div>
            <div style="font-size:0.72rem; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em;">Scheduled hrs</div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="kore-card text-center py-3">
            <div style="font-size:1.5rem; font-weight:700; color:#6b7280;">{{ $scheduleItems->count() }}</div>
            <div style="font-size:0.72rem; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em;">Tasks Scheduled</div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="kore-card text-center py-3">
            <div style="font-size:1.5rem; font-weight:700; color:#f59e0b;">{{ $unscheduledAssignments->count() }}</div>
            <div style="font-size:0.72rem; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em;">Unscheduled</div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="kore-card text-center py-3">
            <div style="font-size:1.5rem; font-weight:700; color:{{ $totalScheduledHours > 40 ? '#ef4444' : '#22c55e' }};">
                {{ number_format(40 - $totalScheduledHours, 1) }}
            </div>
            <div style="font-size:0.72rem; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em;">
                {{ $totalScheduledHours > 40 ? 'Over capacity' : 'Remaining hrs' }}
            </div>
        </div>
    </div>
</div>

<div class="row g-4">

    {{-- ── Left: Scheduled Pipeline ── --}}
    <div class="col-lg-7">
        <div class="kore-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0" style="font-size:0.8rem; font-weight:600;">
                    <i class="bi bi-list-ol me-1 text-primary"></i>This Week's Pipeline
                </h6>
                <span style="font-size:0.72rem; color:#9ca3af;">Drag to reprioritize</span>
            </div>

            @if($scheduleItems->isEmpty())
            <div class="text-center py-5" style="color:#9ca3af; font-size:0.8rem;">
                <i class="bi bi-calendar-week" style="font-size:2rem; display:block; margin-bottom:.5rem;"></i>
                No tasks scheduled for this week.<br>Add tasks from the unscheduled list on the right.
            </div>
            @else
            <div id="pipeline-list" style="min-height:60px;">
                @foreach($scheduleItems as $item)
                @php
                    $task        = $item->taskAssignment?->task;
                    $milestone   = $task?->milestone;
                    $deliverable = $milestone?->deliverable;
                    $project     = $deliverable?->project;
                    $statusColor = match($item->status) {
                        'done'         => '#22c55e',
                        'carried_over' => '#f59e0b',
                        default        => '#4c8bf5',
                    };
                @endphp
                <div class="pipeline-item border rounded mb-2 p-3 d-flex align-items-start gap-3"
                     data-item-id="{{ $item->id }}"
                     style="background:#fff; cursor:grab;">
                    <div class="drag-handle text-muted" style="font-size:1.1rem; cursor:grab; flex-shrink:0; padding-top:2px;">
                        <i class="bi bi-grip-vertical"></i>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div style="min-width:0;">
                                <div style="font-weight:600; font-size:0.82rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    {{ $task?->name ?? '—' }}
                                </div>
                                <div style="font-size:0.72rem; color:#6b7280; margin-top:1px;">
                                    {{ $project?->title ?? '—' }}
                                    @if($deliverable) · {{ $deliverable->name }} @endif
                                    @if($milestone) · {{ $milestone->name }} @endif
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                <span class="badge" style="background:{{ $statusColor }}20; color:{{ $statusColor }}; font-size:0.65rem;">
                                    {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                                </span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3 mt-2">
                            <div style="font-size:0.72rem; color:#6b7280;">
                                <i class="bi bi-clock me-1"></i>
                                <input type="number" step="0.25" min="0.25" max="80"
                                       value="{{ $item->scheduled_hours }}"
                                       class="hours-input border rounded px-1"
                                       style="width:55px; font-size:0.72rem;"
                                       data-item-id="{{ $item->id }}"
                                       onchange="updateItemHours(this)"> hrs planned
                            </div>
                            @if($task?->end_date)
                            <div style="font-size:0.72rem; color:{{ $task->end_date->isPast() && $task->status !== 'complete' ? '#ef4444' : '#6b7280' }};">
                                <i class="bi bi-calendar3 me-1"></i>Due {{ $task->end_date->format('M j') }}
                            </div>
                            @endif
                            <div class="ms-auto d-flex gap-1">
                                <select class="form-select form-select-sm status-select"
                                        style="font-size:0.7rem; padding:2px 4px; width:auto;"
                                        data-item-id="{{ $item->id }}"
                                        onchange="updateItemStatus(this)">
                                    <option value="planned" {{ $item->status === 'planned' ? 'selected' : '' }}>Planned</option>
                                    <option value="done" {{ $item->status === 'done' ? 'selected' : '' }}>Done</option>
                                    <option value="carried_over" {{ $item->status === 'carried_over' ? 'selected' : '' }}>Carry Over</option>
                                </select>
                                <button class="btn btn-sm btn-outline-danger" onclick="removeItem({{ $item->id }})" style="font-size:0.65rem; padding:2px 6px;">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- ── Right: Unscheduled Tasks ── --}}
    <div class="col-lg-5">
        <div class="kore-card">
            <h6 class="mb-3" style="font-size:0.8rem; font-weight:600;">
                <i class="bi bi-inbox me-1 text-warning"></i>Unscheduled Assignments
                <span class="badge bg-warning text-dark ms-1" style="font-size:0.65rem;">{{ $unscheduledAssignments->count() }}</span>
            </h6>

            @if($unscheduledAssignments->isEmpty())
            <div class="text-center py-4" style="color:#9ca3af; font-size:0.8rem;">
                <i class="bi bi-check2-all" style="font-size:1.8rem; display:block; margin-bottom:.5rem; color:#22c55e;"></i>
                All tasks scheduled!
            </div>
            @else
            @foreach($unscheduledAssignments as $assignment)
            @php
                $task        = $assignment->task;
                $milestone   = $task?->milestone;
                $deliverable = $milestone?->deliverable;
                $project     = $deliverable?->project;
                $isOverdue   = $task?->end_date?->isPast() && $task?->status !== 'complete';
            @endphp
            <div class="border rounded mb-2 p-2" style="background:#fafafa;">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:600; font-size:0.78rem;">{{ $task?->name ?? '—' }}</div>
                        <div style="font-size:0.68rem; color:#6b7280; margin-top:1px;">
                            {{ $project?->title ?? '—' }}
                            @if($deliverable) · {{ $deliverable->name }} @endif
                        </div>
                        <div class="d-flex gap-2 mt-1 align-items-center">
                            @if($task?->end_date)
                            <span style="font-size:0.65rem; color:{{ $isOverdue ? '#ef4444' : '#6b7280' }};">
                                <i class="bi bi-calendar3 me-1"></i>Due {{ $task->end_date->format('M j') }}
                                @if($isOverdue) <span class="text-danger">(overdue)</span> @endif
                            </span>
                            @endif
                            @if($assignment->budget_hours)
                            <span style="font-size:0.65rem; color:#6b7280;">
                                <i class="bi bi-hourglass me-1"></i>{{ $assignment->budget_hours }}h budget
                            </span>
                            @endif
                        </div>
                    </div>
                    <button class="btn btn-sm btn-primary flex-shrink-0"
                            style="font-size:0.65rem; padding:3px 8px;"
                            onclick="scheduleTask({{ $assignment->id }}, '{{ addslashes($task?->name) }}')">
                        <i class="bi bi-plus me-1"></i>Schedule
                    </button>
                </div>
            </div>
            @endforeach
            @endif
        </div>
    </div>
</div>

{{-- ── Schedule Task Modal ── --}}
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0" style="font-size:0.85rem;">Schedule Task</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" style="font-size:0.75rem; font-weight:600;">Task</label>
                    <div id="modal-task-name" style="font-size:0.8rem; color:#374151;"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="font-size:0.75rem; font-weight:600;">Week</label>
                    <input type="date" id="modal-week-start" class="form-control form-control-sm"
                           value="{{ $weekStart->format('Y-m-d') }}">
                </div>
                <div class="mb-0">
                    <label class="form-label" style="font-size:0.75rem; font-weight:600;">Planned Hours</label>
                    <input type="number" id="modal-hours" class="form-control form-control-sm"
                           step="0.25" min="0.25" max="80" value="4">
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="confirmSchedule()">
                    <i class="bi bi-plus me-1"></i>Add to Pipeline
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let modalAssignmentId = null;

// ── SortableJS drag-to-reorder ─────────────────────────────────────────────
const pipelineList = document.getElementById('pipeline-list');
if (pipelineList) {
    new Sortable(pipelineList, {
        animation: 150,
        handle: '.drag-handle',
        onEnd: function() {
            const items = [...pipelineList.querySelectorAll('.pipeline-item')].map((el, i) => ({
                id: parseInt(el.dataset.itemId),
                priority_order: (i + 1) * 10,
            }));
            fetch('/workload/reorder', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ items }),
            });
        }
    });
}

// ── Schedule modal ─────────────────────────────────────────────────────────
function scheduleTask(assignmentId, taskName) {
    modalAssignmentId = assignmentId;
    document.getElementById('modal-task-name').textContent = taskName;
    new bootstrap.Modal(document.getElementById('scheduleModal')).show();
}

function confirmSchedule() {
    const weekStart = document.getElementById('modal-week-start').value;
    const hours     = document.getElementById('modal-hours').value;

    fetch('/workload/schedule', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({
            task_assignment_id: modalAssignmentId,
            week_start: weekStart,
            scheduled_hours: parseFloat(hours),
        }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('scheduleModal')).hide();
            location.reload();
        }
    });
}

// ── Update hours inline ────────────────────────────────────────────────────
function updateItemHours(input) {
    const itemId = input.dataset.itemId;
    fetch(`/workload/items/${itemId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ scheduled_hours: parseFloat(input.value) }),
    });
}

// ── Update status inline ───────────────────────────────────────────────────
function updateItemStatus(select) {
    const itemId = select.dataset.itemId;
    fetch(`/workload/items/${itemId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ status: select.value }),
    })
    .then(() => location.reload());
}

// ── Remove from pipeline ───────────────────────────────────────────────────
function removeItem(itemId) {
    if (! confirm('Remove this task from the pipeline?')) return;
    fetch(`/workload/items/${itemId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF },
    })
    .then(() => location.reload());
}
</script>
@endpush
