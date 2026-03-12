@extends('layouts.app')

@section('content')

@php
    $ref = $project->year . '-' . str_pad($project->project_number, 3, '0', STR_PAD_LEFT);
    $csrfToken = csrf_token();
@endphp

{{-- ── Header ──────────────────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center gap-3 mb-3">
    <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div class="flex-grow-1">
        <h4 class="mb-0 fw-700" style="font-size:1rem;">Work Breakdown — {{ $project->title }}</h4>
        <div style="font-size:0.75rem; color:#6b7280;">
            Project {{ $ref }}
            @if($project->proposal)
            &bull;
            <span class="badge {{ $isFixed ? 'bg-success' : 'bg-primary' }}" style="font-size:0.65rem;">
                {{ ucwords(str_replace('_', ' ', $billingType)) }}
            </span>
            @if($project->proposal->feeSchedule)
            &bull; <i class="bi bi-cash-stack me-1"></i>{{ $project->proposal->feeSchedule->name }}
            @endif
            @endif
        </div>
    </div>
    {{-- View toggle --}}
    <div class="btn-group btn-group-sm" id="viewToggle">
        <button class="btn btn-primary active" id="btnWbs" onclick="switchView('wbs')">
            <i class="bi bi-diagram-3 me-1"></i> WBS
        </button>
        <button class="btn btn-outline-primary" id="btnGantt" onclick="switchView('gantt')">
            <i class="bi bi-bar-chart-steps me-1"></i> Gantt
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger py-2 mb-3" style="font-size:0.8rem;">{{ session('error') }}</div>
@endif

<div class="row g-4">

    {{-- ── Left: WBS / Gantt ─────────────────────────────────────────────── --}}
    <div class="col-lg-8">

        {{-- WBS Panel --}}
        <div id="wbsPanel">
            <div id="deliverablesContainer">
            @forelse($project->deliverables as $deliverable)
            @php
                $dHours     = $deliverable->computed_hours;
                $dStart     = $deliverable->computed_start;
                $dEnd       = $deliverable->computed_end;
                $mCount     = $deliverable->milestones->count();
                $dtCount    = $deliverable->directTasks->count();
            @endphp
            <div class="kore-card mb-3 deliverable-card" id="deliverable-{{ $deliverable->id }}" data-id="{{ $deliverable->id }}">

                {{-- Deliverable Header --}}
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="flex-grow-1">
                        <div class="fw-600 d-flex align-items-center gap-2" style="font-size:0.9rem;">
                            <i class="bi bi-folder2-open" style="color:#4c8bf5; cursor:grab;" title="Drag to reorder"></i>
                            {{ $deliverable->name }}
                            {{-- Hours rollup badge --}}
                            @if($isHourly && $dHours > 0)
                            <span class="badge bg-primary-subtle text-primary" style="font-size:0.65rem; font-weight:500;">
                                {{ number_format($dHours, 1) }}h
                            </span>
                            @endif
                            @if($isFixed && $deliverable->deliverable_fee > 0)
                            <span class="badge bg-success-subtle text-success" style="font-size:0.65rem;">${{ number_format($deliverable->deliverable_fee, 0) }}</span>
                            @endif
                        </div>
                        @if($deliverable->description)
                        <div style="font-size:0.76rem; color:#6b7280; margin-top:2px;">{{ $deliverable->description }}</div>
                        @endif
                        @if($dStart || $dEnd)
                        <div style="font-size:0.72rem; color:#9ca3af; margin-top:2px;">
                            <i class="bi bi-calendar3 me-1"></i>
                            {{ $dStart?->format('M j') ?? '?' }} → {{ $dEnd?->format('M j, Y') ?? '?' }}
                        </div>
                        @endif
                    </div>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-secondary"
                            onclick="openEditDeliverable({{ $deliverable->id }}, @json($deliverable->name), @json($deliverable->description ?? ''), {{ $deliverable->budget_hours ?? 0 }}, {{ $deliverable->rate ?? 0 }}, {{ $deliverable->deliverable_fee ?? 0 }}, '{{ $deliverable->start_date?->format('Y-m-d') ?? '' }}', '{{ $deliverable->due_date?->format('Y-m-d') ?? '' }}')"
                            title="Edit deliverable">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form action="{{ route('projects.deliverables.destroy', [$project, $deliverable]) }}" method="POST"
                            onsubmit="return confirm('Delete this deliverable and all its content?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- ── Milestones ────────────────────────────────────────── --}}
                <div class="milestones-container" data-deliverable-id="{{ $deliverable->id }}">
                @foreach($deliverable->milestones as $milestone)
                @php
                    $mHours = $milestone->computed_hours;
                    $mStart = $milestone->computed_start;
                    $mEnd   = $milestone->computed_end;
                @endphp
                <div class="ms-1 mb-2 milestone-block" id="milestone-{{ $milestone->id }}" data-id="{{ $milestone->id }}"
                    style="border-left:2px solid #e5e7eb; padding-left:12px;">

                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="d-flex align-items-center gap-2" style="font-size:0.82rem; color:#374151;">
                            <i class="bi bi-flag" style="color:#f59e0b; cursor:grab;" title="Drag to reorder"></i>
                            <span class="fw-500">{{ $milestone->name }}</span>
                            @if($isHourly && $mHours > 0)
                            <span class="text-muted" style="font-size:0.7rem;">{{ number_format($mHours, 1) }}h</span>
                            @endif
                            @if($isFixed && $milestone->deliverable_fee > 0)
                            <span class="badge bg-success-subtle text-success" style="font-size:0.62rem;">${{ number_format($milestone->deliverable_fee, 0) }}</span>
                            @endif
                            @if($mStart || $mEnd)
                            <span style="font-size:0.7rem; color:#9ca3af;">{{ $mStart?->format('M j') ?? '?' }} → {{ $mEnd?->format('M j') ?? '?' }}</span>
                            @endif
                        </div>
                        <div class="d-flex gap-1">
                            <button class="btn btn-link btn-sm py-0 px-1 text-secondary"
                                onclick="openEditMilestone({{ $milestone->id }}, @json($milestone->name), @json($milestone->description ?? ''), {{ $milestone->budget_hours ?? 0 }}, {{ $milestone->rate ?? 0 }}, {{ $milestone->deliverable_fee ?? 0 }}, '{{ $milestone->start_date?->format('Y-m-d') ?? '' }}', '{{ $milestone->due_date?->format('Y-m-d') ?? '' }}', '{{ $milestone->billing_status ?? 'pending' }}')"
                                style="font-size:0.75rem;" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="{{ route('projects.milestones.destroy', $milestone) }}" method="POST"
                                onsubmit="return confirm('Delete milestone and all its tasks?')" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-link btn-sm py-0 px-1 text-danger" style="font-size:0.75rem;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Tasks in milestone --}}
                    <div class="tasks-container ms-1" data-parent-type="milestone" data-parent-id="{{ $milestone->id }}">
                    @foreach($milestone->tasks as $task)
                        @include('projects._task_row', ['task' => $task, 'isHourly' => $isHourly])
                    @endforeach
                    </div>

                    {{-- Add task to milestone --}}
                    <details class="ms-1 mt-1">
                        <summary style="font-size:0.73rem; color:#4c8bf5; cursor:pointer; list-style:none;">
                            <i class="bi bi-plus-sm"></i> Add task
                        </summary>
                        <form action="{{ route('projects.tasks.store', $milestone) }}" method="POST" class="mt-2">
                            @csrf
                            @include('projects._task_quick_form', ['isHourly' => $isHourly])
                        </form>
                    </details>
                </div>
                @endforeach
                </div>

                {{-- ── Direct tasks (no milestone) ──────────────────────── --}}
                @if($dtCount > 0 || $isTm || $mCount === 0)
                <div class="ms-1 mb-2" @if($mCount > 0) style="border-left:2px dashed #e5e7eb; padding-left:12px;" @endif>
                    @if($mCount > 0)
                    <div style="font-size:0.72rem; color:#9ca3af; margin-bottom:4px;">
                        <i class="bi bi-list-task me-1"></i> Direct tasks
                    </div>
                    @endif
                    <div class="tasks-container" data-parent-type="deliverable" data-parent-id="{{ $deliverable->id }}">
                    @foreach($deliverable->directTasks as $task)
                        @include('projects._task_row', ['task' => $task, 'isHourly' => $isHourly])
                    @endforeach
                    </div>

                    {{-- Add direct task --}}
                    <details class="ms-1 mt-1">
                        <summary style="font-size:0.73rem; color:#6b7280; cursor:pointer; list-style:none;">
                            <i class="bi bi-plus-sm"></i> Add direct task
                        </summary>
                        <form action="{{ route('projects.deliverables.direct-tasks.store', $deliverable) }}" method="POST" class="mt-2">
                            @csrf
                            @include('projects._task_quick_form', ['isHourly' => $isHourly])
                        </form>
                    </details>
                </div>
                @endif

                {{-- Add milestone --}}
                <details class="ms-1 mt-1">
                    <summary style="font-size:0.73rem; color:#9ca3af; cursor:pointer; list-style:none;">
                        <i class="bi bi-plus-sm"></i> Add milestone
                    </summary>
                    <form action="{{ route('projects.milestones.store', [$project, $deliverable]) }}" method="POST" class="mt-2">
                        @csrf
                        <div class="row g-2">
                            <div class="col-sm-4">
                                <input type="text" name="name" class="form-control form-control-sm" placeholder="Milestone name" required>
                            </div>
                            @if($isFixed)
                            <div class="col-sm-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="deliverable_fee" class="form-control" step="0.01" min="0" placeholder="Fixed fee">
                                </div>
                            </div>
                            @else
                            <div class="col-sm-2">
                                <input type="number" name="budget_hours" class="form-control form-control-sm" step="0.25" min="0" placeholder="Hrs">
                            </div>
                            @endif
                            <div class="col-sm-2">
                                <input type="date" name="start_date" class="form-control form-control-sm" title="Start date">
                            </div>
                            <div class="col-sm-2">
                                <input type="date" name="due_date" class="form-control form-control-sm" title="Due date">
                            </div>
                            <div class="col-sm-2">
                                <button type="submit" class="btn btn-sm btn-outline-primary w-100">Add</button>
                            </div>
                        </div>
                    </form>
                </details>

            </div>
            @empty
            <div class="kore-card text-center py-5" style="color:#9ca3af;">
                <i class="bi bi-folder2-open fs-2 d-block mb-2"></i>
                <div>No deliverables yet.</div>
                <div style="font-size:0.78rem; margin-top:6px;">Add a deliverable using the form on the right, or apply a system template below.</div>
            </div>
            @endforelse
            </div>
        </div>

        {{-- Gantt Panel --}}
        <div id="ganttPanel" style="display:none;">
            <div class="kore-card" style="padding:16px;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="fw-600" style="font-size:0.85rem;">Gantt Chart</div>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary" onclick="ganttChangeView('Day')">Day</button>
                        <button class="btn btn-outline-secondary active" onclick="ganttChangeView('Week')">Week</button>
                        <button class="btn btn-outline-secondary" onclick="ganttChangeView('Month')">Month</button>
                    </div>
                </div>
                <div id="ganttNoTasks" class="text-center py-4 text-muted" style="display:none; font-size:0.82rem;">
                    <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>
                    No tasks with start and end dates found.<br>
                    Add dates to tasks in the WBS view to see them here.
                </div>
                <div id="ganttContainer" style="overflow-x:auto;"></div>
            </div>
        </div>

    </div>

    {{-- ── Sidebar ────────────────────────────────────────────────────────── --}}
    <div class="col-lg-4">

        {{-- Add Deliverable --}}
        <div class="kore-card mb-3">
            <div class="fw-600 mb-3" style="font-size:0.85rem;">
                <i class="bi bi-folder-plus me-1 text-primary"></i> Add Deliverable
            </div>
            <form action="{{ route('projects.deliverables.store', $project) }}" method="POST">
                @csrf
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.78rem;">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Phase 1 — Design" required>
                </div>
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.78rem;">Description</label>
                    <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Optional..."></textarea>
                </div>

                @if($isFixed)
                <div class="row g-2 mb-3">
                    <div class="col-7">
                        <label class="form-label" style="font-size:0.75rem;">Fixed Fee ($)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" name="deliverable_fee" class="form-control" step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>
                    <div class="col-5">
                        <label class="form-label" style="font-size:0.75rem;">Due Date</label>
                        <input type="date" name="due_date" class="form-control form-control-sm">
                    </div>
                </div>
                @else
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label" style="font-size:0.75rem;">Start Date</label>
                        <input type="date" name="start_date" class="form-control form-control-sm">
                    </div>
                    <div class="col-6">
                        <label class="form-label" style="font-size:0.75rem;">Due Date</label>
                        <input type="date" name="due_date" class="form-control form-control-sm">
                    </div>
                </div>
                @if($feeRates->count())
                <div class="mb-3">
                    <label class="form-label" style="font-size:0.75rem;">Role / Rate</label>
                    <select name="rate_role" class="form-select form-select-sm" onchange="setRateFromRole(this, 'sidebarRate')">
                        <option value="">— Select role —</option>
                        @foreach($feeRates as $fr)
                        <option value="{{ $fr->hourly_rate }}">${{ number_format($fr->hourly_rate, 0) }}/hr — {{ $fr->role_name }}</option>
                        @endforeach
                        <option value="custom">Custom rate</option>
                    </select>
                    <input type="number" name="rate" id="sidebarRate" class="form-control form-control-sm mt-1 d-none"
                        step="0.01" min="0" placeholder="Custom $/hr">
                </div>
                @endif
                @endif

                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-plus-lg me-1"></i> Add Deliverable
                </button>
            </form>
        </div>

        {{-- Template Library --}}
        @if($templates->count())
        <div class="kore-card mb-3">
            <div class="fw-600 mb-2" style="font-size:0.85rem;">
                <i class="bi bi-grid-1x2 me-1 text-success"></i> Apply System Template
            </div>
            <div style="font-size:0.75rem; color:#6b7280; margin-bottom:10px;">
                Import deliverables, milestones and tasks from a pre-built template.
                @if($project->start_date)
                Dates will be calculated from project start {{ $project->start_date->format('M j, Y') }}.
                @endif
            </div>
            <form action="{{ route('projects.apply-template', $project) }}" method="POST">
                @csrf
                <div class="mb-2">
                    <select name="template_id" class="form-select form-select-sm" required id="templateSelect"
                        onchange="showTemplatePreview(this)">
                        <option value="">— Choose template —</option>
                        @foreach($templates as $tmpl)
                        <option value="{{ $tmpl->id }}"
                            data-deliverables="{{ $tmpl->deliverables->count() }}"
                            data-hours="{{ $tmpl->total_budgeted_hours }}">
                            {{ $tmpl->name }}
                            @if($tmpl->workType) ({{ $tmpl->workType->name }})@endif
                        </option>
                        @endforeach
                    </select>
                </div>
                <div id="templatePreview" class="mb-2" style="display:none; font-size:0.72rem; color:#6b7280; background:#f9fafb; border-radius:6px; padding:8px;">
                    <i class="bi bi-info-circle me-1"></i>
                    <span id="templatePreviewText"></span>
                </div>
                <button type="submit" class="btn btn-sm btn-outline-success w-100"
                    onclick="return confirm('This will add template deliverables to the project. Continue?')">
                    <i class="bi bi-download me-1"></i> Apply Template
                </button>
            </form>
        </div>
        @endif

        {{-- WBS Info Card --}}
        <div class="kore-card" style="font-size:0.78rem; color:#6b7280;">
            <div class="fw-600 mb-2" style="color:#374151;">Work Breakdown Structure</div>
            @if($isFixed)
            <div class="kore-alert kore-alert-info mb-2" style="font-size:0.75rem;">
                <i class="bi bi-info-circle me-1"></i>
                <strong>Fixed Fee</strong> — Each deliverable/milestone has a set dollar amount.
            </div>
            @elseif($isTm)
            <div class="kore-alert kore-alert-info mb-2" style="font-size:0.75rem;">
                <i class="bi bi-info-circle me-1"></i>
                <strong>Time &amp; Material</strong> — Hours tracked at the task level. Milestones are optional.
            </div>
            @elseif($isHourly)
            <div class="kore-alert kore-alert-info mb-2" style="font-size:0.75rem;">
                <i class="bi bi-info-circle me-1"></i>
                <strong>{{ ucwords(str_replace('_', ' ', $billingType)) }}</strong> — Budget hours roll up from tasks.
            </div>
            @endif
            <p class="mb-1"><i class="bi bi-folder2-open me-1 text-primary"></i><strong>Deliverables</strong> — top-level phases or work packages.</p>
            <p class="mb-1"><i class="bi bi-flag me-1" style="color:#f59e0b;"></i><strong>Milestones</strong> — optional groupings within a deliverable.</p>
            <p class="mb-1"><i class="bi bi-circle me-1 text-secondary"></i><strong>Tasks</strong> — individual work items, assignable to users.</p>
            <p class="mb-0"><i class="bi bi-people me-1 text-info"></i><strong>Resources</strong> — click the avatar icon on any task to assign users &amp; hours.</p>
        </div>

    </div>
</div>

{{-- ══ MODALS ════════════════════════════════════════════════════════════════ --}}

{{-- Edit Deliverable --}}
<div class="modal fade" id="editDeliverableModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editDeliverableForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" style="font-size:0.9rem;">Edit Deliverable</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label" style="font-size:0.78rem;">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edName" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" style="font-size:0.78rem;">Description</label>
                        <textarea name="description" id="edDesc" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label" style="font-size:0.75rem;">Start Date</label>
                            <input type="date" name="start_date" id="edStart" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-size:0.75rem;">Due Date</label>
                            <input type="date" name="due_date" id="edDue" class="form-control form-control-sm">
                        </div>
                    </div>
                    @if($isFixed)
                    <div class="mb-2">
                        <label class="form-label" style="font-size:0.75rem;">Fixed Fee ($)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" name="deliverable_fee" id="edFee" class="form-control" step="0.01" min="0">
                        </div>
                    </div>
                    @else
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label" style="font-size:0.75rem;">Rate ($/hr)</label>
                            <input type="number" name="rate" id="edRate" class="form-control form-control-sm" step="0.01" min="0">
                        </div>
                    </div>
                    @endif
                    <div class="mb-0">
                        <label class="form-label" style="font-size:0.75rem;">Billing Status</label>
                        <select name="billing_status" id="edBillingStatus" class="form-select form-select-sm">
                            <option value="pending">Pending</option>
                            <option value="ready_to_bill">Ready to Bill</option>
                            <option value="invoiced">Invoiced</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Milestone --}}
<div class="modal fade" id="editMilestoneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editMilestoneForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" style="font-size:0.9rem;">Edit Milestone</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label" style="font-size:0.78rem;">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="emName" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" style="font-size:0.78rem;">Description</label>
                        <textarea name="description" id="emDesc" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label" style="font-size:0.75rem;">Start Date</label>
                            <input type="date" name="start_date" id="emStart" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-size:0.75rem;">Due Date</label>
                            <input type="date" name="due_date" id="emDue" class="form-control form-control-sm">
                        </div>
                    </div>
                    @if($isFixed)
                    <div class="mb-2">
                        <label class="form-label" style="font-size:0.75rem;">Fixed Fee ($)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" name="deliverable_fee" id="emFee" class="form-control" step="0.01" min="0">
                        </div>
                    </div>
                    @else
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label" style="font-size:0.75rem;">Budget Hours</label>
                            <input type="number" name="budget_hours" id="emHours" class="form-control form-control-sm" step="0.25" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-size:0.75rem;">Rate ($/hr)</label>
                            <input type="number" name="rate" id="emRate" class="form-control form-control-sm" step="0.01" min="0">
                        </div>
                    </div>
                    @endif
                    <div class="mb-0">
                        <label class="form-label" style="font-size:0.75rem;">Billing Status</label>
                        <select name="billing_status" id="emBillingStatus" class="form-select form-select-sm">
                            <option value="pending">Pending</option>
                            <option value="ready_to_bill">Ready to Bill</option>
                            <option value="invoiced">Invoiced</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Task --}}
<div class="modal fade" id="editTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editTaskForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" style="font-size:0.9rem;">Edit Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" style="font-size:0.78rem;">Task Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="etName" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" style="font-size:0.78rem;">Description</label>
                            <textarea name="description" id="etDesc" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label" style="font-size:0.78rem;">Start Date</label>
                            <input type="date" name="start_date" id="etStart" class="form-control form-control-sm">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label" style="font-size:0.78rem;">End Date</label>
                            <input type="date" name="end_date" id="etEnd" class="form-control form-control-sm">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label" style="font-size:0.78rem;">Status</label>
                            <select name="status" id="etStatus" class="form-select form-select-sm">
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="complete">Complete</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        @if($isHourly)
                        <div class="col-sm-4">
                            <label class="form-label" style="font-size:0.78rem;">Budget Hours</label>
                            <input type="number" name="budget_hours" id="etHours" class="form-control form-control-sm" step="0.25" min="0">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label" style="font-size:0.78rem;">Rate ($/hr)</label>
                            <input type="number" name="rate" id="etRate" class="form-control form-control-sm" step="0.01" min="0">
                        </div>
                        @endif
                    </div>

                    {{-- Dependencies --}}
                    <div class="mt-3 pt-3" style="border-top:1px solid #f3f4f6;">
                        <div class="fw-600 mb-2" style="font-size:0.8rem;">Dependencies
                            <span class="text-muted fw-400" style="font-size:0.72rem;">(tasks that must finish before this one starts)</span>
                        </div>
                        <div id="dependencyList" class="mb-2"></div>
                        <div class="d-flex gap-2">
                            <select id="depTaskSelect" class="form-select form-select-sm">
                                <option value="">— Select predecessor task —</option>
                            </select>
                            <input type="number" id="depLagDays" class="form-control form-control-sm" style="width:90px;" placeholder="Lag days" value="0">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addDependency()">
                                <i class="bi bi-plus-sm"></i> Add
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Resource Assignment Modal --}}
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-size:0.9rem;">
                    <i class="bi bi-people me-2 text-info"></i>
                    Assign Resources — <span id="assignTaskName" class="text-primary"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                {{-- Hours summary --}}
                <div class="d-flex gap-3 mb-3 p-2 rounded" style="background:#f9fafb; font-size:0.78rem;">
                    <div>
                        <div style="color:#9ca3af;">Task Budget</div>
                        <div class="fw-600" id="assignBudget">—</div>
                    </div>
                    <div>
                        <div style="color:#9ca3af;">Assigned</div>
                        <div class="fw-600" id="assignAllocated">0h</div>
                    </div>
                    <div>
                        <div style="color:#9ca3af;">Remaining</div>
                        <div class="fw-600" id="assignRemaining">—</div>
                    </div>
                </div>

                {{-- Current assignments --}}
                <div id="assignmentsList" class="mb-3"></div>

                {{-- Add assignment --}}
                <div style="border-top:1px solid #f3f4f6; padding-top:12px;">
                    <div class="fw-600 mb-2" style="font-size:0.8rem;">Add / Update Resource</div>
                    <div class="row g-2">
                        <div class="col-sm-5">
                            <select id="assignUserId" class="form-select form-select-sm">
                                <option value="">— Select user —</option>
                                @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->first_name }} {{ $u->last_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <input type="number" id="assignHours" class="form-control form-control-sm" step="0.25" min="0" placeholder="Hours">
                        </div>
                        <div class="col-sm-3">
                            <input type="text" id="assignRole" class="form-control form-control-sm" placeholder="Role (optional)">
                        </div>
                        <div class="col-sm-1">
                            <button type="button" class="btn btn-sm btn-primary w-100" onclick="saveAssignment()">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Gantt styles --}}
<style>
.gantt .bar-label { font-size: 11px !important; }
.gantt-pending    .bar { fill: #e5e7eb !important; }
.gantt-in_progress .bar { fill: #3b82f6 !important; }
.gantt-complete   .bar { fill: #22c55e !important; }
.gantt-cancelled  .bar { fill: #9ca3af !important; opacity:.6; }

.task-row { padding: 4px 6px; border-radius:4px; transition:background .15s; }
.task-row:hover { background:#f9fafb; }
.task-row .drag-handle { cursor:grab; color:#d1d5db; opacity:.6; }
.task-row:hover .drag-handle { opacity:1; }
.sortable-ghost { opacity:.4; background:#eff6ff; border-radius:4px; }

.assignee-avatar {
    display:inline-flex; align-items:center; justify-content:center;
    width:22px; height:22px; border-radius:50%;
    background:#4c8bf5; color:#fff; font-size:0.6rem; font-weight:600;
    cursor:pointer; border:1.5px solid #fff;
    margin-left:-4px;
}
.assignee-avatar:first-child { margin-left:0; }
</style>

@push('scripts')
{{-- SortableJS --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
{{-- Frappe Gantt --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.css">
<script src="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.js"></script>

<script>
const PROJECT_ID  = {{ $project->id }};
const IS_FIXED    = {{ $isFixed ? 'true' : 'false' }};
const IS_HOURLY   = {{ $isHourly ? 'true' : 'false' }};
const CSRF        = '{{ $csrfToken }}';

// ── All tasks for dependency selector ────────────────────────────────────────
const ALL_TASKS = @json(
    $project->deliverables->flatMap(function ($d) {
        $tasks = collect();
        foreach ($d->milestones as $m) {
            foreach ($m->tasks as $t) {
                $tasks->push(['id' => $t->id, 'name' => $d->name . ' / ' . $m->name . ' / ' . $t->name]);
            }
        }
        foreach ($d->directTasks as $t) {
            $tasks->push(['id' => $t->id, 'name' => $d->name . ' / ' . $t->name]);
        }
        return $tasks;
    })->values()
);

// ── View switching ────────────────────────────────────────────────────────────
let ganttInstance = null;
let currentView   = 'wbs';

function switchView(view) {
    currentView = view;
    document.getElementById('wbsPanel').style.display   = view === 'wbs'   ? '' : 'none';
    document.getElementById('ganttPanel').style.display = view === 'gantt' ? '' : 'none';
    document.getElementById('btnWbs').classList.toggle('active', view === 'wbs');
    document.getElementById('btnWbs').classList.toggle('btn-primary', view === 'wbs');
    document.getElementById('btnWbs').classList.toggle('btn-outline-primary', view !== 'wbs');
    document.getElementById('btnGantt').classList.toggle('active', view === 'gantt');
    document.getElementById('btnGantt').classList.toggle('btn-primary', view === 'gantt');
    document.getElementById('btnGantt').classList.toggle('btn-outline-primary', view !== 'gantt');

    if (view === 'gantt' && !ganttInstance) {
        loadGantt('Week');
    }
}

function ganttChangeView(mode) {
    if (ganttInstance) ganttInstance.change_view_mode(mode);
}

async function loadGantt(viewMode) {
    const resp = await fetch(`/projects/${PROJECT_ID}/gantt-data`);
    const tasks = await resp.json();

    document.getElementById('ganttNoTasks').style.display  = tasks.length ? 'none' : '';
    document.getElementById('ganttContainer').style.display = tasks.length ? '' : 'none';

    if (!tasks.length) return;

    ganttInstance = new Gantt('#ganttContainer', tasks, {
        view_mode: viewMode || 'Week',
        date_format: 'YYYY-MM-DD',
        bar_height: 22,
        padding: 14,
        on_date_change: function (task, start, end) {
            const id = task.id.replace('task-', '');
            fetch(`/tasks/${id}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({
                    _method: 'PUT', name: task.name,
                    start_date: start.toISOString().slice(0, 10),
                    end_date:   end.toISOString().slice(0, 10),
                    status: 'pending',
                }),
            });
        },
        on_click: function (task) {
            const id = parseInt(task.id.replace('task-', ''));
            // Could open edit modal; for now just highlight
        },
    });
}

// ── Rate from role helper ────────────────────────────────────────────────────
function setRateFromRole(sel, targetId) {
    const inp = document.getElementById(targetId);
    if (!inp) return;
    if (sel.value === 'custom') {
        inp.classList.remove('d-none');
        inp.focus();
    } else {
        inp.classList.add('d-none');
        inp.value = sel.value;
    }
}

// ── Template preview ─────────────────────────────────────────────────────────
function showTemplatePreview(sel) {
    const preview = document.getElementById('templatePreview');
    const text    = document.getElementById('templatePreviewText');
    if (!sel.value) { preview.style.display = 'none'; return; }
    const opt = sel.options[sel.selectedIndex];
    const d   = opt.dataset.deliverables || 0;
    const h   = parseFloat(opt.dataset.hours || 0);
    text.textContent = `${d} deliverable(s) • ${h.toFixed(1)}h total budgeted hours`;
    preview.style.display = '';
}

// ── Drag-and-drop reorder + move tasks ──────────────────────────────────────
function initSortable() {
    // Deliverable reorder
    new Sortable(document.getElementById('deliverablesContainer'), {
        animation: 150,
        handle: '.bi-folder2-open',
        ghostClass: 'sortable-ghost',
        onEnd(evt) {
            const items = [...evt.to.children].map((el, i) => ({
                id: parseInt(el.dataset.id), sort_order: i + 1
            }));
            postJson(`/projects/${PROJECT_ID}/wbs-reorder`, { type: 'deliverable', items });
        },
    });

    // Task containers (milestones + direct)
    document.querySelectorAll('.tasks-container').forEach(container => {
        new Sortable(container, {
            animation: 150,
            group: 'tasks',
            ghostClass: 'sortable-ghost',
            handle: '.drag-handle',
            onEnd(evt) {
                const taskId     = parseInt(evt.item.dataset.id);
                const parentType = evt.to.dataset.parentType;
                const parentId   = parseInt(evt.to.dataset.parentId);

                // Moved to a different container
                if (evt.from !== evt.to) {
                    postJson(`/tasks/${taskId}/move`, {
                        target_type: parentType,
                        target_id:   parentId,
                    });
                }

                // Reorder within container
                const items = [...evt.to.children].map((el, i) => ({
                    id: parseInt(el.dataset.id), sort_order: i + 1
                }));
                postJson(`/projects/${PROJECT_ID}/wbs-reorder`, { type: 'task', items });
            },
        });
    });
}

function postJson(url, data) {
    return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(data),
    });
}

// ── Edit Deliverable ──────────────────────────────────────────────────────────
function openEditDeliverable(id, name, desc, hours, rate, fee, start, due) {
    document.getElementById('editDeliverableForm').action = `/projects/${PROJECT_ID}/deliverables/${id}`;
    document.getElementById('edName').value  = name;
    document.getElementById('edDesc').value  = desc;
    document.getElementById('edStart').value = start || '';
    document.getElementById('edDue').value   = due || '';
    if (IS_FIXED) {
        const f = document.getElementById('edFee');
        if (f) f.value = fee || '';
    } else {
        const r = document.getElementById('edRate');
        if (r) r.value = rate || '';
    }
    new bootstrap.Modal(document.getElementById('editDeliverableModal')).show();
}

// ── Edit Milestone ────────────────────────────────────────────────────────────
function openEditMilestone(id, name, desc, hours, rate, fee, start, due, billingStatus) {
    document.getElementById('editMilestoneForm').action = `/milestones/${id}`;
    document.getElementById('emName').value          = name;
    document.getElementById('emDesc').value          = desc;
    document.getElementById('emStart').value         = start || '';
    document.getElementById('emDue').value           = due || '';
    document.getElementById('emBillingStatus').value = billingStatus || 'pending';
    if (IS_FIXED) {
        const f = document.getElementById('emFee'); if (f) f.value = fee || '';
    } else {
        const h = document.getElementById('emHours'); if (h) h.value = hours || '';
        const r = document.getElementById('emRate');  if (r) r.value = rate || '';
    }
    new bootstrap.Modal(document.getElementById('editMilestoneModal')).show();
}

// ── Edit Task ─────────────────────────────────────────────────────────────────
let currentTaskId = null;

function openEditTask(id, name, desc, start, end, status, hours, rate, deps) {
    currentTaskId = id;
    document.getElementById('editTaskForm').action = `/tasks/${id}`;
    document.getElementById('etName').value   = name;
    document.getElementById('etDesc').value   = desc;
    document.getElementById('etStart').value  = start || '';
    document.getElementById('etEnd').value    = end || '';
    document.getElementById('etStatus').value = status;
    if (IS_HOURLY) {
        const h = document.getElementById('etHours'); if (h) h.value = hours || '';
        const r = document.getElementById('etRate');  if (r) r.value = rate || '';
    }

    // Populate dependency selector
    const sel = document.getElementById('depTaskSelect');
    sel.innerHTML = '<option value="">— Select predecessor task —</option>';
    ALL_TASKS.forEach(t => {
        if (t.id !== id) {
            const opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = t.name;
            sel.appendChild(opt);
        }
    });

    // Show existing dependencies
    renderDependencies(deps || []);

    new bootstrap.Modal(document.getElementById('editTaskModal')).show();
}

function renderDependencies(deps) {
    const list = document.getElementById('dependencyList');
    if (!deps.length) { list.innerHTML = '<div style="font-size:0.75rem;color:#9ca3af;">No dependencies.</div>'; return; }
    list.innerHTML = deps.map(d => `
        <div class="d-flex align-items-center gap-2 mb-1" id="dep-row-${d.depends_on_id}">
            <i class="bi bi-arrow-return-right text-muted" style="font-size:0.75rem;"></i>
            <span style="font-size:0.78rem; flex-grow:1;">${d.depends_on?.name ?? 'Task #'+d.depends_on_id}</span>
            ${d.lag_days ? `<span class="badge bg-secondary-subtle text-secondary" style="font-size:0.65rem;">+${d.lag_days}d lag</span>` : ''}
            <button type="button" class="btn btn-link btn-sm py-0 px-1 text-danger" onclick="removeDependency(${d.depends_on_id})" style="font-size:0.72rem;">
                <i class="bi bi-x"></i>
            </button>
        </div>
    `).join('');
}

async function addDependency() {
    const sel     = document.getElementById('depTaskSelect');
    const lagInp  = document.getElementById('depLagDays');
    const depId   = parseInt(sel.value);
    if (!depId || !currentTaskId) return;

    const resp = await postJson(`/tasks/${currentTaskId}/dependencies`, {
        depends_on_id: depId, lag_days: parseInt(lagInp.value) || 0,
    });
    const data = await resp.json();
    if (data.success) {
        const opt   = sel.options[sel.selectedIndex];
        const row   = document.createElement('div');
        row.className = 'd-flex align-items-center gap-2 mb-1';
        row.id = `dep-row-${depId}`;
        row.innerHTML = `
            <i class="bi bi-arrow-return-right text-muted" style="font-size:0.75rem;"></i>
            <span style="font-size:0.78rem; flex-grow:1;">${opt.textContent}</span>
            <button type="button" class="btn btn-link btn-sm py-0 px-1 text-danger" onclick="removeDependency(${depId})" style="font-size:0.72rem;"><i class="bi bi-x"></i></button>
        `;
        const list = document.getElementById('dependencyList');
        if (list.querySelector('.text-muted')) list.innerHTML = '';
        list.appendChild(row);
        sel.value     = '';
        lagInp.value  = 0;
        // Refresh gantt if open
        if (currentView === 'gantt') { ganttInstance = null; loadGantt('Week'); }
    } else {
        alert(data.error || 'Could not add dependency.');
    }
}

async function removeDependency(dependsOnId) {
    if (!currentTaskId) return;
    const resp = await fetch(`/tasks/${currentTaskId}/dependencies/${dependsOnId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF },
    });
    const data = await resp.json();
    if (data.success) {
        const row = document.getElementById(`dep-row-${dependsOnId}`);
        if (row) row.remove();
        if (!document.getElementById('dependencyList').children.length) {
            document.getElementById('dependencyList').innerHTML = '<div style="font-size:0.75rem;color:#9ca3af;">No dependencies.</div>';
        }
        if (currentView === 'gantt') { ganttInstance = null; loadGantt('Week'); }
    }
}

// ── Resource Assignment ───────────────────────────────────────────────────────
let assignTaskId    = null;
let assignBudgetHrs = 0;

function openAssignModal(taskId, taskName, budgetHours, assignmentsJson) {
    assignTaskId    = taskId;
    assignBudgetHrs = parseFloat(budgetHours) || 0;

    document.getElementById('assignTaskName').textContent = taskName;
    document.getElementById('assignBudget').textContent   = assignBudgetHrs > 0 ? assignBudgetHrs.toFixed(1) + 'h' : '—';
    document.getElementById('assignUserId').value  = '';
    document.getElementById('assignHours').value   = '';
    document.getElementById('assignRole').value    = '';

    renderAssignmentsList(assignmentsJson || []);
    updateAssignSummary();
    new bootstrap.Modal(document.getElementById('assignModal')).show();
}

function renderAssignmentsList(assignments) {
    const list = document.getElementById('assignmentsList');
    if (!assignments.length) {
        list.innerHTML = '<div style="font-size:0.78rem; color:#9ca3af;">No resources assigned yet.</div>';
        return;
    }
    list.innerHTML = assignments.map(a => `
        <div class="d-flex align-items-center gap-2 mb-2 p-2 rounded" style="background:#f9fafb;" id="assign-row-${a.id}">
            <div class="assignee-avatar" style="background:#4c8bf5; width:28px; height:28px; font-size:0.68rem;">
                ${a.initials}
            </div>
            <div class="flex-grow-1">
                <div style="font-size:0.8rem; font-weight:500;">${a.user_name}</div>
                <div style="font-size:0.72rem; color:#9ca3af;">${a.role || 'No role assigned'}</div>
            </div>
            <div class="d-flex align-items-center gap-1">
                <input type="number" class="form-control form-control-sm" style="width:70px; font-size:0.78rem;"
                    value="${parseFloat(a.budget_hours).toFixed(1)}"
                    onchange="updateAssignmentHours(${a.id}, this.value, '${a.role || ''}')">
                <span style="font-size:0.72rem; color:#9ca3af;">h</span>
                <button type="button" class="btn btn-link btn-sm py-0 px-1 text-danger"
                    onclick="removeAssignment(${a.id})" style="font-size:0.75rem;">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
    updateAssignSummary();
}

function updateAssignSummary() {
    const rows     = document.querySelectorAll('[id^="assign-row-"] input[type=number]');
    let allocated  = 0;
    rows.forEach(inp => allocated += parseFloat(inp.value) || 0);

    document.getElementById('assignAllocated').textContent = allocated.toFixed(1) + 'h';
    const remaining = assignBudgetHrs > 0 ? (assignBudgetHrs - allocated) : null;
    const remEl = document.getElementById('assignRemaining');
    if (remaining !== null) {
        remEl.textContent = remaining.toFixed(1) + 'h';
        remEl.className = 'fw-600 ' + (remaining < 0 ? 'text-danger' : remaining === 0 ? 'text-success' : '');
    } else {
        remEl.textContent = '—';
        remEl.className = 'fw-600';
    }
}

async function saveAssignment() {
    const userId = document.getElementById('assignUserId').value;
    const hours  = document.getElementById('assignHours').value;
    const role   = document.getElementById('assignRole').value;
    if (!userId || !hours) { alert('Select a user and enter hours.'); return; }

    const resp = await postJson(`/tasks/${assignTaskId}/assignments`, {
        user_id: userId, budget_hours: hours, role,
    });
    const data = await resp.json();
    if (data.success) {
        // Update or add row
        const existing = document.getElementById(`assign-row-${data.assignment.id}`);
        if (existing) existing.remove();

        const list = document.getElementById('assignmentsList');
        if (list.querySelector('.text-muted')) list.innerHTML = '';

        const row = document.createElement('div');
        row.className = 'd-flex align-items-center gap-2 mb-2 p-2 rounded';
        row.style.background = '#f9fafb';
        row.id = `assign-row-${data.assignment.id}`;
        row.innerHTML = `
            <div class="assignee-avatar" style="background:#4c8bf5; width:28px; height:28px; font-size:0.68rem;">
                ${data.assignment.initials}
            </div>
            <div class="flex-grow-1">
                <div style="font-size:0.8rem; font-weight:500;">${data.assignment.user_name}</div>
                <div style="font-size:0.72rem; color:#9ca3af;">${data.assignment.role || 'No role assigned'}</div>
            </div>
            <div class="d-flex align-items-center gap-1">
                <input type="number" class="form-control form-control-sm" style="width:70px; font-size:0.78rem;"
                    value="${data.assignment.budget_hours.toFixed(1)}"
                    onchange="updateAssignmentHours(${data.assignment.id}, this.value, '${data.assignment.role || ''}')">
                <span style="font-size:0.72rem; color:#9ca3af;">h</span>
                <button type="button" class="btn btn-link btn-sm py-0 px-1 text-danger"
                    onclick="removeAssignment(${data.assignment.id})" style="font-size:0.75rem;">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        list.appendChild(row);
        document.getElementById('assignUserId').value = '';
        document.getElementById('assignHours').value  = '';
        document.getElementById('assignRole').value   = '';
        updateAssignSummary();
    }
}

async function updateAssignmentHours(assignId, hours, role) {
    await postJson(`/task-assignments/${assignId}`, {
        _method: 'PUT', budget_hours: hours, role,
    });
    updateAssignSummary();
}

async function removeAssignment(assignId) {
    const resp = await fetch(`/task-assignments/${assignId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF },
    });
    const data = await resp.json();
    if (data.success) {
        const row = document.getElementById(`assign-row-${assignId}`);
        if (row) row.remove();
        if (!document.getElementById('assignmentsList').children.length) {
            document.getElementById('assignmentsList').innerHTML = '<div style="font-size:0.78rem; color:#9ca3af;">No resources assigned yet.</div>';
        }
        updateAssignSummary();
    }
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', initSortable);
</script>
@endpush

@endsection
