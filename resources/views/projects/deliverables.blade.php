@extends('layouts.app')

@push('styles')
<style>
.task-row { display:flex; align-items:center; gap:8px; padding:5px 8px; border-radius:6px; transition:background .12s; }
.task-row:hover { background:#f8fafc; }
.drag-handle { cursor:grab; color:#d1d5db; font-size:0.72rem; flex-shrink:0; }
.task-row:hover .drag-handle { color:#9ca3af; }
.sortable-ghost { opacity:.35; background:#eff6ff; border-radius:6px; }

.status-dot { width:9px; height:9px; border-radius:50%; flex-shrink:0; border:1.5px solid currentColor; }
.status-dot.pending     { background:transparent; color:#9ca3af; }
.status-dot.in_progress { background:#3b82f6; color:#3b82f6; }
.status-dot.complete    { background:#22c55e; color:#22c55e; }
.status-dot.cancelled   { background:#d1d5db; color:#d1d5db; }

.av { display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; border-radius:50%; background:#4c8bf5; color:#fff; font-size:0.58rem; font-weight:600; border:1.5px solid #fff; margin-left:-4px; cursor:pointer; }
.av:first-child { margin-left:0; }
.av-add { background:#f3f4f6; color:#9ca3af; border:1.5px dashed #d1d5db; }

.deliverable-card { border-radius:10px; border:1px solid #e5e7eb; background:#fff; padding:16px; margin-bottom:12px; }
.deliverable-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:10px; }
.deliverable-title { font-weight:600; font-size:0.9rem; color:#111827; display:flex; align-items:center; gap:8px; }
.deliverable-meta { font-size:0.72rem; color:#9ca3af; margin-top:3px; }

.milestone-block { border-left:2px solid #e5e7eb; padding-left:12px; margin-bottom:10px; }
.milestone-header { display:flex; align-items:center; gap:6px; font-size:0.8rem; color:#374151; font-weight:500; padding:3px 0; }
.ms-actions { margin-left:auto; display:flex; gap:2px; opacity:0; transition:opacity .15s; }
.milestone-block:hover .ms-actions { opacity:1; }

.add-form-row { display:flex; gap:6px; align-items:center; padding:4px 0; flex-wrap:wrap; }
.add-form-row input, .add-form-row select { font-size:0.76rem; height:28px; border-radius:4px; border:1px solid #e5e7eb; padding:0 8px; }
.add-form-row input:focus, .add-form-row select:focus { outline:none; border-color:#4c8bf5; }
.btn-add-inline { font-size:0.72rem; padding:3px 10px; height:28px; border-radius:4px; white-space:nowrap; }

.section-actions { display:flex; gap:12px; padding-top:6px; margin-top:4px; border-top:1px dashed #f3f4f6; }
.link-action { font-size:0.72rem; color:#4c8bf5; background:none; border:none; cursor:pointer; padding:0; }
.link-action:hover { text-decoration:underline; }
.link-action.muted { color:#9ca3af; }

.mini-progress { height:3px; border-radius:2px; background:#f3f4f6; margin-top:4px; overflow:hidden; }
.mini-progress-fill { height:100%; background:#22c55e; border-radius:2px; }

.sidebar-card { border-radius:10px; border:1px solid #e5e7eb; background:#fff; padding:16px; margin-bottom:12px; }
.sidebar-card-title { font-size:0.82rem; font-weight:600; color:#111827; margin-bottom:12px; display:flex; align-items:center; gap:6px; }

.modal-sm-label { font-size:0.76rem; font-weight:500; color:#374151; margin-bottom:4px; }

.gantt .bar-label { font-size:11px !important; }
.gantt-pending .bar     { fill:#e5e7eb !important; }
.gantt-in_progress .bar { fill:#3b82f6 !important; }
.gantt-complete .bar    { fill:#22c55e !important; }
.gantt-cancelled .bar   { fill:#d1d5db !important; opacity:.6; }

.dep-badge { display:inline-flex; align-items:center; gap:3px; font-size:0.65rem; background:#f3f4f6; border:1px solid #e5e7eb; border-radius:4px; padding:1px 5px; color:#6b7280; }
</style>
@endpush

@section('content')
@php
    $ref  = $project->year . '-' . str_pad($project->project_number, 3, '0', STR_PAD_LEFT);
    $csrf = csrf_token();

    // Build flat task list for dependency selector
    $allTasksFlat = collect();
    foreach ($project->deliverables as $d) {
        foreach ($d->milestones as $m) {
            foreach ($m->tasks as $t) {
                $allTasksFlat->push(['id' => $t->id, 'label' => $d->name . ' / ' . $m->name . ' / ' . $t->name]);
            }
        }
        foreach ($d->directTasks as $t) {
            $allTasksFlat->push(['id' => $t->id, 'label' => $d->name . ' / ' . $t->name]);
        }
    }
@endphp

{{-- ── Header ──────────────────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div class="flex-grow-1 min-w-0">
        <div class="fw-700" style="font-size:0.95rem;">{{ $project->title }}</div>
        <div class="d-flex align-items-center gap-2 flex-wrap" style="margin-top:2px;">
            <span style="font-size:0.72rem; color:#6b7280;">Project {{ $ref }}</span>
            @if($project->company)
                <span style="font-size:0.72rem; color:#9ca3af;">&bull; {{ $project->company->name }}</span>
            @endif
            @if($project->proposal)
            @php
                $btBadgeClass = match($billingType) {
                    'fixed'           => 'bg-success',
                    'time_and_material' => 'bg-primary',
                    'hybrid'          => 'bg-purple text-white',
                    'retainer'        => 'bg-warning text-dark',
                    'per_deliverable' => 'bg-info text-dark',
                    default           => 'bg-secondary',
                };
            @endphp
                <span class="badge {{ $btBadgeClass }}" style="font-size:0.62rem;">
                    {{ ucwords(str_replace('_', ' ', $billingType)) }}
                </span>
            @endif
        </div>
    </div>
    <div class="btn-group btn-group-sm">
        <button id="btnWbs"   class="btn btn-primary"         onclick="switchView('wbs')">
            <i class="bi bi-diagram-3 me-1"></i>WBS
        </button>
        <button id="btnGantt" class="btn btn-outline-primary" onclick="switchView('gantt')">
            <i class="bi bi-bar-chart-steps me-1"></i>Gantt
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

{{-- ════════════════════ LEFT — WBS / Gantt ═══════════════════════════════════ --}}
<div class="col-lg-8">

<div id="wbsPanel">
<div id="deliverablesContainer">
@forelse($project->deliverables as $deliverable)
@php
    $dHours   = $deliverable->computed_hours;
    $dStart   = $deliverable->computed_start;
    $dEnd     = $deliverable->computed_end;
    $allTasks = $deliverable->milestones->flatMap->tasks->merge($deliverable->directTasks);
    $totalT   = $allTasks->count();
    $doneT    = $allTasks->where('status', 'complete')->count();
    $pct      = $totalT ? round($doneT / $totalT * 100) : 0;
@endphp

<div class="deliverable-card" id="deliverable-{{ $deliverable->id }}" data-id="{{ $deliverable->id }}">

    {{-- Deliverable header --}}
    <div class="deliverable-header">
        <div class="flex-grow-1 min-w-0">
            <div class="deliverable-title">
                <i class="bi bi-grip-vertical drag-handle"></i>
                <i class="bi bi-folder2-open text-primary" style="flex-shrink:0;"></i>
                <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $deliverable->name }}</span>
                @if($dHours > 0)
                    <span class="badge bg-primary-subtle text-primary" style="font-size:0.62rem; flex-shrink:0;">{{ number_format($dHours, 1) }}h</span>
                @endif
                @if($showDeliverableFee && $deliverable->deliverable_fee > 0)
                    @php $bsColor = match($deliverable->billing_status ?? 'pending') { 'paid' => '#22c55e', 'invoiced' => '#3b82f6', 'ready_to_bill' => '#f59e0b', default => '#9ca3af' }; @endphp
                    <span class="badge" style="background:#ecfdf5; color:#059669; font-size:0.62rem; flex-shrink:0;">${{ number_format($deliverable->deliverable_fee, 0) }}</span>
                    <span class="badge" style="background:#f3f4f6; color:{{ $bsColor }}; font-size:0.6rem; flex-shrink:0;">{{ ucwords(str_replace('_',' ',$deliverable->billing_status ?? 'pending')) }}</span>
                @endif
            </div>
            <div class="deliverable-meta d-flex flex-wrap gap-2">
                @if($dStart || $dEnd)
                    <span><i class="bi bi-calendar3 me-1"></i>{{ $dStart?->format('M j') ?? '?' }} → {{ $dEnd?->format('M j, Y') ?? '?' }}</span>
                @endif
                @if($totalT > 0)
                    <span>{{ $doneT }}/{{ $totalT }} tasks</span>
                @endif
                @if($deliverable->description)
                    <span class="text-truncate d-none d-md-inline" style="max-width:200px;">{{ $deliverable->description }}</span>
                @endif
            </div>
            @if($totalT > 0)
            <div class="mini-progress" style="max-width:180px;">
                <div class="mini-progress-fill" style="width:{{ $pct }}%"></div>
            </div>
            @endif
        </div>
        <div class="d-flex gap-1 ms-2" style="flex-shrink:0;">
            <button class="btn btn-sm btn-outline-secondary p-1"
                data-action="edit-deliverable"
                data-id="{{ $deliverable->id }}"
                data-name="{{ $deliverable->name }}"
                data-desc="{{ $deliverable->description ?? '' }}"
                data-start="{{ $deliverable->start_date?->format('Y-m-d') ?? '' }}"
                data-due="{{ $deliverable->due_date?->format('Y-m-d') ?? '' }}"
                data-fee="{{ $deliverable->deliverable_fee ?? '' }}"
                data-billing="{{ $deliverable->billing_status ?? 'pending' }}"
                title="Edit">
                <i class="bi bi-pencil" style="font-size:0.72rem;"></i>
            </button>
            <form action="{{ route('projects.deliverables.destroy', [$project, $deliverable]) }}" method="POST"
                onsubmit="return confirm('Delete this deliverable and all its content?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger p-1" title="Delete">
                    <i class="bi bi-trash" style="font-size:0.72rem;"></i>
                </button>
            </form>
        </div>
    </div>

    {{-- Milestones --}}
    @foreach($deliverable->milestones as $milestone)
    @php $mHours = $milestone->computed_hours; @endphp
    <div class="milestone-block" id="milestone-{{ $milestone->id }}" data-id="{{ $milestone->id }}">
        <div class="milestone-header">
            <i class="bi bi-grip-vertical drag-handle" style="font-size:0.72rem;"></i>
            <i class="bi bi-flag" style="color:#f59e0b; font-size:0.78rem;"></i>
            <span>{{ $milestone->name }}</span>
            @if($mHours > 0)
                <span style="font-size:0.7rem; color:#9ca3af; font-weight:400;">{{ number_format($mHours, 1) }}h</span>
            @endif
            @php $mcs = $milestone->computed_start; $mce = $milestone->computed_end; @endphp
            @if($mcs || $mce)
                <span style="font-size:0.68rem; color:#d1d5db;">{{ $mcs?->format('M j') ?? '?' }} → {{ $mce?->format('M j') ?? '?' }}</span>
            @endif
            <div class="ms-actions">
                <button class="btn btn-link btn-sm p-0 text-secondary" style="font-size:0.72rem;"
                    data-action="edit-milestone"
                    data-id="{{ $milestone->id }}"
                    data-name="{{ $milestone->name }}"
                    data-desc="{{ $milestone->description ?? '' }}"
                    data-start="{{ $milestone->start_date?->format('Y-m-d') ?? '' }}"
                    data-due="{{ $milestone->due_date?->format('Y-m-d') ?? '' }}"
                    data-fee="{{ $milestone->deliverable_fee ?? '' }}"
                    data-billing="{{ $milestone->billing_status ?? 'pending' }}"
                    title="Edit"><i class="bi bi-pencil"></i>
                </button>
                <form action="{{ route('projects.milestones.destroy', $milestone) }}" method="POST"
                    onsubmit="return confirm('Delete milestone and its tasks?')" class="d-inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-link btn-sm p-0 text-danger" style="font-size:0.72rem;">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        </div>
        <div class="tasks-container ps-2" data-parent-type="milestone" data-parent-id="{{ $milestone->id }}">
            @foreach($milestone->tasks as $task)
                @include('projects._task_row', ['task' => $task, 'isHourly' => $isHourly])
            @endforeach
        </div>
        <div id="addTaskForm-ms-{{ $milestone->id }}" style="display:none; padding:4px 0 4px 8px;">
            <form action="{{ route('projects.tasks.store', $milestone) }}" method="POST">
                @csrf
                <div class="add-form-row">
                    <input type="text" name="name" placeholder="Task name" required style="flex:1; min-width:130px;">
                    <input type="date" name="start_date" title="Start date">
                    <input type="date" name="end_date"   title="End date">
                    <input type="number" name="budget_hours" step="0.25" min="0" placeholder="Hours" style="width:68px;">
                    @if($isHourly)
                        <input type="number" name="rate" step="0.01" min="0" placeholder="$/hr" style="width:62px;">
                    @endif
                    <button type="submit" class="btn btn-primary btn-add-inline">Add</button>
                    <button type="button" class="btn btn-link btn-sm p-0 text-muted"
                        onclick="toggleForm('addTaskForm-ms-{{ $milestone->id }}')">Cancel</button>
                </div>
            </form>
        </div>
        <button class="link-action muted ps-2" onclick="toggleForm('addTaskForm-ms-{{ $milestone->id }}')">
            <i class="bi bi-plus-sm"></i> Add task
        </button>
    </div>
    @endforeach

    {{-- Direct tasks --}}
    @php $showDirect = $isTm || $deliverable->milestones->isEmpty() || $deliverable->directTasks->isNotEmpty(); @endphp
    @if($showDirect)
    <div class="ms-0">
        @if($deliverable->milestones->isNotEmpty())
        <div style="font-size:0.67rem; color:#d1d5db; text-transform:uppercase; letter-spacing:.04em; margin:6px 0 2px 8px;">Direct Tasks</div>
        @endif
        <div class="tasks-container ps-2" data-parent-type="deliverable" data-parent-id="{{ $deliverable->id }}">
            @foreach($deliverable->directTasks as $task)
                @include('projects._task_row', ['task' => $task, 'isHourly' => $isHourly])
            @endforeach
        </div>
        <div id="addTaskForm-d-{{ $deliverable->id }}" style="display:none; padding:4px 0 4px 8px;">
            <form action="{{ route('projects.deliverables.direct-tasks.store', $deliverable) }}" method="POST">
                @csrf
                <div class="add-form-row">
                    <input type="text" name="name" placeholder="Task name" required style="flex:1; min-width:130px;">
                    <input type="date" name="start_date" title="Start date">
                    <input type="date" name="end_date"   title="End date">
                    <input type="number" name="budget_hours" step="0.25" min="0" placeholder="Hours" style="width:68px;">
                    @if($isHourly)
                        <input type="number" name="rate" step="0.01" min="0" placeholder="$/hr" style="width:62px;">
                    @endif
                    <button type="submit" class="btn btn-primary btn-add-inline">Add</button>
                    <button type="button" class="btn btn-link btn-sm p-0 text-muted"
                        onclick="toggleForm('addTaskForm-d-{{ $deliverable->id }}')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Bottom actions --}}
    <div class="section-actions">
        @if($showDirect)
        <button class="link-action" onclick="toggleForm('addTaskForm-d-{{ $deliverable->id }}')">
            <i class="bi bi-plus-sm"></i> Add task
        </button>
        @endif
        <button class="link-action muted" onclick="toggleForm('addMsForm-{{ $deliverable->id }}')">
            <i class="bi bi-plus-sm"></i> Add milestone
        </button>
    </div>
    <div id="addMsForm-{{ $deliverable->id }}" style="display:none; margin-top:8px;">
        <form action="{{ route('projects.milestones.store', [$project, $deliverable]) }}" method="POST">
            @csrf
            <div class="add-form-row">
                <input type="text" name="name" placeholder="Milestone name" required style="flex:1; min-width:150px;">
                <input type="date" name="start_date" title="Start date">
                <input type="date" name="due_date"   title="Due date">
                @if($showDeliverableFee)
                    <input type="number" name="deliverable_fee" step="0.01" min="0" placeholder="Fee $" style="width:90px;">
                @endif
                <button type="submit" class="btn btn-outline-primary btn-add-inline">Add</button>
                <button type="button" class="btn btn-link btn-sm p-0 text-muted"
                    onclick="toggleForm('addMsForm-{{ $deliverable->id }}')">Cancel</button>
            </div>
        </form>
    </div>

</div>
@empty
<div class="kore-card text-center py-5" style="color:#9ca3af;">
    <i class="bi bi-folder2-open fs-2 d-block mb-2"></i>
    <div style="font-size:0.85rem;">No deliverables yet.</div>
    <div style="font-size:0.75rem; margin-top:4px;">Use the panel on the right to add one or apply a template.</div>
</div>
@endforelse
</div>{{-- end deliverablesContainer --}}
</div>{{-- end wbsPanel --}}

{{-- Gantt --}}
<div id="ganttPanel" style="display:none;">
    <div class="kore-card" style="padding:16px;">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="fw-600" style="font-size:0.85rem;">Gantt Chart
                <span style="font-size:0.7rem; font-weight:400; color:#9ca3af; margin-left:6px;">
                    Drag bars to reschedule — successors auto-adjust
                </span>
            </div>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary" onclick="ganttChangeView('Day')">Day</button>
                <button class="btn btn-outline-secondary active" onclick="ganttChangeView('Week')">Week</button>
                <button class="btn btn-outline-secondary" onclick="ganttChangeView('Month')">Month</button>
            </div>
        </div>
        <div id="ganttNoTasks" class="text-center py-4" style="display:none; color:#9ca3af; font-size:0.82rem;">
            <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>No tasks with start &amp; end dates.<br>
            Add dates to tasks in the WBS view.
        </div>
        <div id="ganttContainer" style="overflow-x:auto;"></div>
    </div>
</div>

</div>{{-- col-lg-8 --}}

{{-- ════════════════════ RIGHT — Sidebar ══════════════════════════════════════ --}}
<div class="col-lg-4">

    {{-- Add Deliverable --}}
    <div class="sidebar-card">
        <div class="sidebar-card-title"><i class="bi bi-folder-plus text-primary"></i> Add Deliverable</div>
        <form action="{{ route('projects.deliverables.store', $project) }}" method="POST">
            @csrf
            <div class="mb-2">
                <label class="modal-sm-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Phase 1 — Design" required>
            </div>
            <div class="mb-2">
                <label class="modal-sm-label">Description</label>
                <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Optional..."></textarea>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="modal-sm-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm">
                </div>
                <div class="col-6">
                    <label class="modal-sm-label">Due Date</label>
                    <input type="date" name="due_date" class="form-control form-control-sm">
                </div>
            </div>
            @if($showDeliverableFee)
            <div class="mb-2">
                <label class="modal-sm-label">{{ $isPerDeliverable ? 'Deliverable Fee ($)' : 'Fixed Fee ($)' }}</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">$</span>
                    <input type="number" name="deliverable_fee" class="form-control" step="0.01" min="0" placeholder="0.00">
                </div>
            </div>
            @endif
            <button type="submit" class="btn btn-primary btn-sm w-100 mt-1">
                <i class="bi bi-plus-lg me-1"></i>Add Deliverable
            </button>
        </form>
    </div>

    {{-- Templates --}}
    @if($templates->count())
    <div class="sidebar-card">
        <div class="sidebar-card-title"><i class="bi bi-grid-1x2 text-success"></i> Apply Template</div>
        <p style="font-size:0.73rem; color:#6b7280; margin-bottom:10px;">
            Import deliverables, milestones &amp; tasks from a saved template.
            @if($project->start_date) Dates offset from {{ $project->start_date->format('M j, Y') }}.@endif
        </p>
        <form action="{{ route('projects.apply-template', $project) }}" method="POST">
            @csrf
            <select name="template_id" class="form-select form-select-sm mb-2" required id="tmplSel"
                onchange="showTmplPreview(this)">
                <option value="">— Choose template —</option>
                @foreach($templates as $tmpl)
                @php $isMatch = $tmpl->billing_type === $billingType; @endphp
                <option value="{{ $tmpl->id }}"
                    data-d="{{ $tmpl->deliverables->count() }}"
                    data-h="{{ $tmpl->total_budgeted_hours }}"
                    data-match="{{ $isMatch ? '1' : '0' }}">
                    {{ $isMatch ? '✓ ' : '' }}{{ $tmpl->name }}{{ $tmpl->workType ? ' ('.$tmpl->workType->name.')' : '' }}{{ $tmpl->billing_type && !$isMatch ? ' ['.ucwords(str_replace('_',' ',$tmpl->billing_type)).']' : '' }}
                </option>
                @endforeach
            </select>
            <div id="tmplPreview" style="display:none; font-size:0.72rem; color:#6b7280; background:#f9fafb; border-radius:6px; padding:6px 8px; margin-bottom:8px;"></div>
            <button type="submit" class="btn btn-outline-success btn-sm w-100"
                onclick="return confirm('Add template deliverables to this project?')">
                <i class="bi bi-download me-1"></i>Apply Template
            </button>
        </form>
    </div>
    @endif

    {{-- Import from Spreadsheet --}}
    <div class="sidebar-card">
        <div class="sidebar-card-title"><i class="bi bi-file-earmark-arrow-up text-info"></i> Import from Spreadsheet</div>
        <p style="font-size:0.73rem; color:#6b7280; margin-bottom:10px;">
            Bulk-import deliverables, milestones &amp; tasks from an Excel or Google Sheets file.
        </p>
        <button type="button" class="btn btn-outline-info btn-sm w-100"
            data-bs-toggle="modal" data-bs-target="#importDelivsModal">
            <i class="bi bi-upload me-1"></i>Import Spreadsheet
        </button>
        <div class="mt-2 text-center">
            <a href="{{ route('projects.deliverables.import.sample') }}" style="font-size:0.72rem; color:#9ca3af;">
                <i class="bi bi-download me-1"></i>Download sample .xlsx
            </a>
        </div>
    </div>

    {{-- Info --}}
    <div class="sidebar-card" style="font-size:0.76rem; color:#6b7280;">
        <div class="sidebar-card-title" style="color:#374151;"><i class="bi bi-info-circle"></i> Guide</div>
        @if($isTm)
        <div class="alert alert-info py-1 px-2 mb-2" style="font-size:0.72rem;">
            <strong>Time &amp; Material</strong> — hours &amp; rates at task level. Invoices pull actual timesheet entries.
        </div>
        @elseif($isFixed)
        <div class="alert alert-success py-1 px-2 mb-2" style="font-size:0.72rem;">
            <strong>Fixed Fee</strong> — set dollar amounts on deliverables/milestones. Hours tracked but not billed per-task.
        </div>
        @elseif($isHybrid)
        <div class="alert py-1 px-2 mb-2" style="font-size:0.72rem; background:#faf5ff; border-color:#7c3aed; color:#5b21b6;">
            <strong>Hybrid</strong> — set deliverable fees AND track hours/rates. Both components appear in invoices.
        </div>
        @elseif($isRetainer)
        <div class="alert alert-warning py-1 px-2 mb-2" style="font-size:0.72rem;">
            <strong>Retainer</strong> — fixed recurring amount. Track hours for utilization reporting only.
        </div>
        @elseif($isPerDeliverable)
        <div class="alert py-1 px-2 mb-2" style="font-size:0.72rem; background:#eff6ff; border-color:#2563eb; color:#1d4ed8;">
            <strong>Per Deliverable</strong> — set a fee on each deliverable. Invoice generated when deliverable is marked ready to bill.
        </div>
        @endif
        <p class="mb-1"><i class="bi bi-folder2-open me-1 text-primary"></i><strong>Deliverables</strong> — top-level phases.</p>
        <p class="mb-1"><i class="bi bi-flag me-1" style="color:#f59e0b;"></i><strong>Milestones</strong> — optional groupings within a deliverable.</p>
        <p class="mb-1"><i class="bi bi-circle me-1 text-secondary"></i><strong>Tasks</strong> — hold dates, hours, rates &amp; assignees.</p>
        <p class="mb-1"><i class="bi bi-arrow-right me-1 text-danger"></i><strong>Dependencies</strong> — set in task edit; dates auto-cascade to successors.</p>
        <p class="mb-0"><i class="bi bi-arrows-move me-1 text-secondary"></i>Drag tasks to reorder or move between milestones.</p>
    </div>

</div>

</div>{{-- row --}}

{{-- ══════════════════════════════ MODALS ══════════════════════════════════════ --}}

{{-- Edit Deliverable --}}
<div class="modal fade" id="modalEditDeliverable" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <form id="formEditDeliverable" method="POST">
            @csrf <input type="hidden" name="_method" value="PUT">
            <div class="modal-header py-3"><h6 class="modal-title">Edit Deliverable</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="modal-sm-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="edName" class="form-control form-control-sm" required>
                </div>
                <div class="mb-3">
                    <label class="modal-sm-label">Description</label>
                    <textarea name="description" id="edDesc" class="form-control form-control-sm" rows="2"></textarea>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="modal-sm-label">Start Date</label>
                        <input type="date" name="start_date" id="edStart" class="form-control form-control-sm"></div>
                    <div class="col-6"><label class="modal-sm-label">Due Date</label>
                        <input type="date" name="due_date" id="edDue" class="form-control form-control-sm"></div>
                </div>
                @if($showDeliverableFee)
                <div class="mb-3"><label class="modal-sm-label">{{ $isPerDeliverable ? 'Deliverable Fee ($)' : 'Fixed Fee ($)' }}</label>
                    <div class="input-group input-group-sm"><span class="input-group-text">$</span>
                        <input type="number" name="deliverable_fee" id="edFee" class="form-control" step="0.01" min="0">
                    </div>
                </div>
                @endif
                <div><label class="modal-sm-label">Billing Status</label>
                    <select name="billing_status" id="edBilling" class="form-select form-select-sm">
                        <option value="pending">Pending</option>
                        <option value="ready_to_bill">Ready to Bill</option>
                        <option value="invoiced">Invoiced</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
            </div>
        </form>
    </div></div>
</div>

{{-- Edit Milestone --}}
<div class="modal fade" id="modalEditMilestone" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <form id="formEditMilestone" method="POST">
            @csrf <input type="hidden" name="_method" value="PUT">
            <div class="modal-header py-3"><h6 class="modal-title">Edit Milestone</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label class="modal-sm-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="emName" class="form-control form-control-sm" required>
                </div>
                <div class="mb-3"><label class="modal-sm-label">Description</label>
                    <textarea name="description" id="emDesc" class="form-control form-control-sm" rows="2"></textarea>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="modal-sm-label">Start Date</label>
                        <input type="date" name="start_date" id="emStart" class="form-control form-control-sm"></div>
                    <div class="col-6"><label class="modal-sm-label">Due Date</label>
                        <input type="date" name="due_date" id="emDue" class="form-control form-control-sm"></div>
                </div>
                @if($showDeliverableFee)
                <div class="mb-3"><label class="modal-sm-label">{{ $isPerDeliverable ? 'Deliverable Fee ($)' : 'Fixed Fee ($)' }}</label>
                    <div class="input-group input-group-sm"><span class="input-group-text">$</span>
                        <input type="number" name="deliverable_fee" id="emFee" class="form-control" step="0.01" min="0">
                    </div>
                </div>
                @endif
                <div><label class="modal-sm-label">Billing Status</label>
                    <select name="billing_status" id="emBilling" class="form-select form-select-sm">
                        <option value="pending">Pending</option>
                        <option value="ready_to_bill">Ready to Bill</option>
                        <option value="invoiced">Invoiced</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
            </div>
        </form>
    </div></div>
</div>

{{-- Edit Task --}}
<div class="modal fade" id="modalEditTask" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <form id="formEditTask" method="POST">
            @csrf <input type="hidden" name="_method" value="PUT">
            <div class="modal-header py-3"><h6 class="modal-title">Edit Task</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-12"><label class="modal-sm-label">Task Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="etName" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12"><label class="modal-sm-label">Description</label>
                        <textarea name="description" id="etDesc" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <div class="col-sm-4"><label class="modal-sm-label">Start Date</label>
                        <input type="date" name="start_date" id="etStart" class="form-control form-control-sm">
                    </div>
                    <div class="col-sm-4"><label class="modal-sm-label">End Date</label>
                        <input type="date" name="end_date" id="etEnd" class="form-control form-control-sm">
                    </div>
                    <div class="col-sm-4"><label class="modal-sm-label">Status</label>
                        <select name="status" id="etStatus" class="form-select form-select-sm">
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="complete">Complete</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-sm-4"><label class="modal-sm-label">Budget Hours</label>
                        <input type="number" name="budget_hours" id="etHours" class="form-control form-control-sm" step="0.25" min="0" placeholder="0.00">
                    </div>
                    @if($isHourly)
                    <div class="col-sm-4"><label class="modal-sm-label">Rate ($/hr)</label>
                        @if($feeRates->count())
                        <select id="etRateSel" class="form-select form-select-sm mb-1" onchange="applyRate(this)">
                            <option value="">— Role / rate —</option>
                            @foreach($feeRates as $fr)
                            <option value="{{ $fr->hourly_rate }}">{{ $fr->role_name }} — ${{ number_format($fr->hourly_rate, 0) }}/hr</option>
                            @endforeach
                            <option value="custom">Custom rate</option>
                        </select>
                        @endif
                        <input type="number" name="rate" id="etRate" class="form-control form-control-sm" step="0.01" min="0" placeholder="$/hr">
                    </div>
                    @endif
                </div>

                {{-- Dependencies section --}}
                <div style="border-top:1px solid #f3f4f6; padding-top:14px;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-600" style="font-size:0.8rem;">
                            Predecessors
                            <span class="text-muted fw-400" style="font-size:0.7rem;">
                                (finish-to-start — successor dates auto-adjust)
                            </span>
                        </div>
                    </div>
                    <div id="depList" class="mb-2"></div>
                    <div class="d-flex gap-2 align-items-end">
                        <div class="flex-grow-1">
                            <label class="modal-sm-label">Predecessor task</label>
                            <select id="depSelect" class="form-select form-select-sm">
                                <option value="">— Select task —</option>
                                @foreach($allTasksFlat as $ft)
                                <option value="{{ $ft['id'] }}">{{ $ft['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="width:90px;">
                            <label class="modal-sm-label">Lag (days)</label>
                            <input type="number" id="depLag" class="form-control form-control-sm" value="0" min="-99" max="999">
                        </div>
                        <div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addDep()">
                                <i class="bi bi-plus-sm"></i> Add
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Save &amp; Cascade Dates</button>
            </div>
        </form>
    </div></div>
</div>

{{-- Resource Assignment --}}
<div class="modal fade" id="modalAssign" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header py-3">
            <h6 class="modal-title"><i class="bi bi-people me-2 text-info"></i>Resources — <span id="assignTaskName"></span></h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="d-flex gap-4 p-2 rounded mb-3" style="background:#f9fafb; font-size:0.78rem;">
                <div><div style="color:#9ca3af; font-size:0.68rem;">Task Budget</div><div class="fw-600" id="assignBudget">—</div></div>
                <div><div style="color:#9ca3af; font-size:0.68rem;">Assigned</div><div class="fw-600" id="assignAlloc">0h</div></div>
                <div><div style="color:#9ca3af; font-size:0.68rem;">Remaining</div><div class="fw-600" id="assignRem">—</div></div>
            </div>
            <div id="assignList" class="mb-3"></div>
            <div style="border-top:1px solid #f3f4f6; padding-top:12px;">
                <div class="fw-600 mb-2" style="font-size:0.8rem;">Add / Update Resource</div>
                <div class="row g-2">
                    <div class="col-sm-5">
                        <select id="assignUser" class="form-select form-select-sm">
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
                        <input type="text" id="assignRole" class="form-control form-control-sm" placeholder="Role">
                    </div>
                    <div class="col-sm-1">
                        <button type="button" class="btn btn-primary btn-sm w-100" onclick="saveAssign()">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer py-2">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        </div>
    </div></div>
</div>

@push('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.css">
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.js"></script>
<script>
const PROJECT_ID = {{ $project->id }};
const IS_HOURLY  = {{ $isHourly ? 'true' : 'false' }};
const CSRF       = '{{ $csrf }}';

// ── Utilities ─────────────────────────────────────────────────────────────────
function postJson(url, data) {
    return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(data),
    });
}

function toggleForm(id) {
    const el = document.getElementById(id);
    if (!el) return;
    const visible = el.style.display !== 'none';
    el.style.display = visible ? 'none' : 'block';
    if (!visible) el.querySelector('input[name="name"]')?.focus();
}

function showTmplPreview(sel) {
    const p = document.getElementById('tmplPreview');
    if (!sel.value) { p.style.display = 'none'; return; }
    const o = sel.options[sel.selectedIndex];
    p.textContent = `${o.dataset.d} deliverable(s) · ${parseFloat(o.dataset.h || 0).toFixed(1)}h`;
    p.style.display = 'block';
}

function applyRate(sel) {
    const r = document.getElementById('etRate');
    if (r && sel.value && sel.value !== 'custom') r.value = sel.value;
}

// ── Data-attribute driven edit modals ─────────────────────────────────────────
document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const { action } = btn.dataset;

    if (action === 'edit-deliverable') {
        const d = btn.dataset;
        document.getElementById('formEditDeliverable').action = `/projects/${PROJECT_ID}/deliverables/${d.id}`;
        document.getElementById('edName').value    = d.name    || '';
        document.getElementById('edDesc').value    = d.desc    || '';
        document.getElementById('edStart').value   = d.start   || '';
        document.getElementById('edDue').value     = d.due     || '';
        document.getElementById('edBilling').value = d.billing || 'pending';
        const fee = document.getElementById('edFee'); if (fee) fee.value = d.fee || '';
        new bootstrap.Modal(document.getElementById('modalEditDeliverable')).show();
    }

    if (action === 'edit-milestone') {
        const d = btn.dataset;
        document.getElementById('formEditMilestone').action = `/milestones/${d.id}`;
        document.getElementById('emName').value    = d.name    || '';
        document.getElementById('emDesc').value    = d.desc    || '';
        document.getElementById('emStart').value   = d.start   || '';
        document.getElementById('emDue').value     = d.due     || '';
        document.getElementById('emBilling').value = d.billing || 'pending';
        const fee = document.getElementById('emFee'); if (fee) fee.value = d.fee || '';
        new bootstrap.Modal(document.getElementById('modalEditMilestone')).show();
    }

    if (action === 'edit-task') {
        const d = btn.dataset;
        currentTaskId = parseInt(d.id);
        document.getElementById('formEditTask').action = `/tasks/${d.id}`;
        document.getElementById('etName').value   = d.name   || '';
        document.getElementById('etDesc').value   = d.desc   || '';
        document.getElementById('etStart').value  = d.start  || '';
        document.getElementById('etEnd').value    = d.end    || '';
        document.getElementById('etStatus').value = d.status || 'pending';
        const h = document.getElementById('etHours'); if (h) h.value = d.hours || '';
        const r = document.getElementById('etRate');  if (r) r.value = d.rate  || '';
        const rs = document.getElementById('etRateSel'); if (rs) rs.value = '';
        renderDeps(JSON.parse(d.deps || '[]'));
        new bootstrap.Modal(document.getElementById('modalEditTask')).show();
    }

    if (action === 'assign-task') {
        const d = btn.dataset;
        openAssignModal(parseInt(d.id), d.name, parseFloat(d.hours || 0), JSON.parse(d.assignments || '[]'));
    }
});

// ── Task dependencies ─────────────────────────────────────────────────────────
let currentTaskId = null;

function renderDeps(deps) {
    const list = document.getElementById('depList');
    if (!deps || !deps.length) {
        list.innerHTML = '<div style="font-size:0.75rem;color:#9ca3af;">No predecessors set.</div>';
        return;
    }
    list.innerHTML = deps.map(d => `
        <div class="d-flex align-items-center gap-2 mb-1" id="dep-${d.depends_on_id}">
            <i class="bi bi-arrow-return-right" style="font-size:0.75rem;color:#9ca3af;"></i>
            <span style="font-size:0.78rem;flex-grow:1;">${d.depends_on?.name ?? 'Task #'+d.depends_on_id}</span>
            ${d.lag_days ? `<span class="dep-badge">${d.lag_days > 0 ? '+' : ''}${d.lag_days}d lag</span>` : ''}
            <button type="button" class="btn btn-link btn-sm p-0 text-danger" style="font-size:0.72rem;" onclick="removeDep(${d.depends_on_id})">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>`).join('');
}

async function addDep() {
    const depId = parseInt(document.getElementById('depSelect').value);
    const lag   = parseInt(document.getElementById('depLag').value) || 0;
    if (!depId || !currentTaskId) return;

    const resp = await postJson(`/tasks/${currentTaskId}/dependencies`, { depends_on_id: depId, lag_days: lag });
    const data = await resp.json();
    if (data.success) {
        const sel = document.getElementById('depSelect');
        const name = sel.options[sel.selectedIndex].text;
        const list = document.getElementById('depList');
        if (list.querySelector('.text-muted')) list.innerHTML = '';
        list.insertAdjacentHTML('beforeend', `
            <div class="d-flex align-items-center gap-2 mb-1" id="dep-${depId}">
                <i class="bi bi-arrow-return-right" style="font-size:0.75rem;color:#9ca3af;"></i>
                <span style="font-size:0.78rem;flex-grow:1;">${name}</span>
                ${lag ? `<span class="dep-badge">${lag > 0 ? '+' : ''}${lag}d lag</span>` : ''}
                <button type="button" class="btn btn-link btn-sm p-0 text-danger" style="font-size:0.72rem;" onclick="removeDep(${depId})">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>`);
        sel.value = ''; document.getElementById('depLag').value = 0;
        if (currentView === 'gantt') { ganttInstance = null; loadGantt('Week'); }
    } else {
        alert(data.error || 'Could not add dependency.');
    }
}

async function removeDep(depId) {
    const resp = await fetch(`/tasks/${currentTaskId}/dependencies/${depId}`, {
        method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF }
    });
    const data = await resp.json();
    if (data.success) {
        document.getElementById(`dep-${depId}`)?.remove();
        const list = document.getElementById('depList');
        if (!list.children.length) list.innerHTML = '<div style="font-size:0.75rem;color:#9ca3af;">No predecessors set.</div>';
        if (currentView === 'gantt') { ganttInstance = null; loadGantt('Week'); }
    }
}

// ── Drag-and-drop ─────────────────────────────────────────────────────────────
function initSortable() {
    const dc = document.getElementById('deliverablesContainer');
    if (dc) {
        new Sortable(dc, {
            animation: 150,
            handle: '.deliverable-card > .deliverable-header .bi-grip-vertical',
            ghostClass: 'sortable-ghost',
            onEnd(evt) {
                const items = [...evt.to.children].filter(el => el.dataset.id)
                    .map((el, i) => ({ id: parseInt(el.dataset.id), sort_order: i + 1 }));
                postJson(`/projects/${PROJECT_ID}/wbs-reorder`, { type: 'deliverable', items });
            },
        });
    }

    document.querySelectorAll('.tasks-container').forEach(c => {
        new Sortable(c, {
            animation: 150, group: 'tasks', handle: '.drag-handle', ghostClass: 'sortable-ghost',
            onEnd(evt) {
                const taskId = parseInt(evt.item.dataset.id);
                if (evt.from !== evt.to) {
                    postJson(`/tasks/${taskId}/move`, {
                        target_type: evt.to.dataset.parentType,
                        target_id: parseInt(evt.to.dataset.parentId),
                    });
                }
                const items = [...evt.to.children].filter(el => el.dataset.id)
                    .map((el, i) => ({ id: parseInt(el.dataset.id), sort_order: i + 1 }));
                postJson(`/projects/${PROJECT_ID}/wbs-reorder`, { type: 'task', items });
            },
        });
    });
}

// ── Gantt ─────────────────────────────────────────────────────────────────────
let ganttInstance = null;
let currentView   = 'wbs';

function switchView(v) {
    currentView = v;
    document.getElementById('wbsPanel').style.display   = v === 'wbs'   ? '' : 'none';
    document.getElementById('ganttPanel').style.display = v === 'gantt' ? '' : 'none';
    document.getElementById('btnWbs').className   = `btn btn-sm ${v === 'wbs'   ? 'btn-primary' : 'btn-outline-primary'}`;
    document.getElementById('btnGantt').className = `btn btn-sm ${v === 'gantt' ? 'btn-primary' : 'btn-outline-primary'}`;
    if (v === 'gantt' && !ganttInstance) loadGantt('Week');
}

function ganttChangeView(m) { if (ganttInstance) ganttInstance.change_view_mode(m); }

async function loadGantt(mode) {
    const resp  = await fetch(`/projects/${PROJECT_ID}/gantt-data`);
    const tasks = await resp.json();
    document.getElementById('ganttNoTasks').style.display   = tasks.length ? 'none' : '';
    document.getElementById('ganttContainer').style.display = tasks.length ? '' : 'none';
    if (!tasks.length) return;

    ganttInstance = new Gantt('#ganttContainer', tasks, {
        view_mode: mode || 'Week',
        date_format: 'YYYY-MM-DD',
        bar_height: 22,
        padding: 14,
        on_date_change(task, start, end) {
            const id = task.id.replace('task-', '');
            fetch(`/tasks/${id}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({
                    _method: 'PUT', name: task.name, status: 'pending',
                    start_date: start.toISOString().slice(0, 10),
                    end_date:   end.toISOString().slice(0, 10),
                }),
            }).then(() => { ganttInstance = null; loadGantt(mode); });
        },
    });
}

// ── Resource assignment ───────────────────────────────────────────────────────
let assignTaskId = null, assignBudget = 0;

function openAssignModal(tid, name, hrs, assignments) {
    assignTaskId = tid; assignBudget = hrs;
    document.getElementById('assignTaskName').textContent = name;
    document.getElementById('assignBudget').textContent  = hrs > 0 ? hrs.toFixed(1) + 'h' : '—';
    document.getElementById('assignUser').value  = '';
    document.getElementById('assignHours').value = '';
    document.getElementById('assignRole').value  = '';
    renderAssignList(assignments || []);
    new bootstrap.Modal(document.getElementById('modalAssign')).show();
}

function renderAssignList(list) {
    const el = document.getElementById('assignList');
    if (!list.length) { el.innerHTML = '<div style="font-size:0.78rem;color:#9ca3af;">No resources assigned.</div>'; updateAssignSummary(); return; }
    el.innerHTML = list.map(a => `
        <div class="d-flex align-items-center gap-2 mb-2 p-2 rounded" style="background:#f9fafb;" id="arow-${a.id}">
            <div class="av" style="width:28px;height:28px;font-size:0.65rem;">${a.initials}</div>
            <div class="flex-grow-1"><div style="font-size:0.8rem;font-weight:500;">${a.user_name}</div><div style="font-size:0.7rem;color:#9ca3af;">${a.role || ''}</div></div>
            <input type="number" class="form-control form-control-sm" style="width:72px;font-size:0.78rem;"
                value="${parseFloat(a.budget_hours).toFixed(1)}"
                onchange="updateAssignHours(${a.id}, this.value, '${(a.role||'').replace(/'/g,"\\'")}')">
            <span style="font-size:0.72rem;color:#9ca3af;">h</span>
            <button type="button" class="btn btn-link btn-sm p-0 text-danger" onclick="removeAssign(${a.id})">
                <i class="bi bi-trash" style="font-size:0.72rem;"></i>
            </button>
        </div>`).join('');
    updateAssignSummary();
}

function updateAssignSummary() {
    let alloc = 0;
    document.querySelectorAll('[id^="arow-"] input[type=number]').forEach(i => alloc += parseFloat(i.value) || 0);
    document.getElementById('assignAlloc').textContent = alloc.toFixed(1) + 'h';
    const rem = assignBudget > 0 ? assignBudget - alloc : null;
    const el  = document.getElementById('assignRem');
    el.textContent = rem !== null ? rem.toFixed(1) + 'h' : '—';
    el.style.color = rem !== null ? (rem < 0 ? '#ef4444' : rem === 0 ? '#22c55e' : '') : '';
}

async function saveAssign() {
    const uid = document.getElementById('assignUser').value;
    const hrs = document.getElementById('assignHours').value;
    const rol = document.getElementById('assignRole').value;
    if (!uid || !hrs) { alert('Select a user and enter hours.'); return; }
    const resp = await postJson(`/tasks/${assignTaskId}/assignments`, { user_id: uid, budget_hours: hrs, role: rol });
    const data = await resp.json();
    if (!data.success) return;
    const a = data.assignment;
    document.getElementById(`arow-${a.id}`)?.remove();
    const el = document.getElementById('assignList');
    if (el.querySelector('.text-muted')) el.innerHTML = '';
    el.insertAdjacentHTML('beforeend', `
        <div class="d-flex align-items-center gap-2 mb-2 p-2 rounded" style="background:#f9fafb;" id="arow-${a.id}">
            <div class="av" style="width:28px;height:28px;font-size:0.65rem;">${a.initials}</div>
            <div class="flex-grow-1"><div style="font-size:0.8rem;font-weight:500;">${a.user_name}</div><div style="font-size:0.7rem;color:#9ca3af;">${a.role||''}</div></div>
            <input type="number" class="form-control form-control-sm" style="width:72px;font-size:0.78rem;"
                value="${a.budget_hours.toFixed(1)}"
                onchange="updateAssignHours(${a.id}, this.value, '${(a.role||'').replace(/'/g,"\\'")}')">
            <span style="font-size:0.72rem;color:#9ca3af;">h</span>
            <button type="button" class="btn btn-link btn-sm p-0 text-danger" onclick="removeAssign(${a.id})">
                <i class="bi bi-trash" style="font-size:0.72rem;"></i>
            </button>
        </div>`);
    document.getElementById('assignUser').value  = '';
    document.getElementById('assignHours').value = '';
    document.getElementById('assignRole').value  = '';
    updateAssignSummary();
}

async function updateAssignHours(id, hrs, role) {
    await postJson(`/task-assignments/${id}`, { _method: 'PUT', budget_hours: hrs, role });
    updateAssignSummary();
}

async function removeAssign(id) {
    const r = await fetch(`/task-assignments/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF } });
    const d = await r.json();
    if (d.success) {
        document.getElementById(`arow-${id}`)?.remove();
        if (!document.getElementById('assignList').children.length) {
            document.getElementById('assignList').innerHTML = '<div style="font-size:0.78rem;color:#9ca3af;">No resources assigned.</div>';
        }
        updateAssignSummary();
    }
}

document.addEventListener('DOMContentLoaded', initSortable);
</script>
@endpush

{{-- ── Import Deliverables Modal ───────────────────────────────────────── --}}
<div class="modal fade" id="importDelivsModal" tabindex="-1" aria-labelledby="importDelivsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0" id="importDelivsModalLabel">
                    <i class="bi bi-file-earmark-arrow-up me-2 text-info"></i>Import from Spreadsheet
                </h6>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
            </div>

            @if($errors->has('import_file'))
            <div class="alert alert-danger py-2 mb-0 rounded-0" style="font-size:0.78rem;">
                {{ $errors->first('import_file') }}
            </div>
            @endif

            <form method="POST" action="{{ route('projects.deliverables.import', $project) }}"
                  enctype="multipart/form-data" id="importDelivsForm">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            {{-- Drop zone --}}
                            <label class="form-label" style="font-size:0.75rem; font-weight:600; color:#374151;">
                                Spreadsheet File <span class="text-danger">*</span>
                                <span style="font-weight:400; color:#9ca3af;">— .xlsx, .xls, or .csv</span>
                            </label>
                            <div id="delvsDropZone" class="import-drop-zone" onclick="document.getElementById('delvsFileInput').click()">
                                <i class="bi bi-cloud-arrow-up" style="font-size:1.8rem; color:#9ca3af;"></i>
                                <div style="font-size:0.8rem; color:#6b7280; margin-top:6px;">
                                    Drag & drop, or <span style="color:#0e7490; font-weight:600;">click to browse</span>
                                </div>
                                <div style="font-size:0.7rem; color:#9ca3af; margin-top:3px;">.xlsx · .xls · .csv — max 10 MB</div>
                                <div id="delvsFileName" class="mt-1" style="font-size:0.78rem; font-weight:600; color:#374151; display:none;"></div>
                            </div>
                            <input type="file" id="delvsFileInput" name="file" accept=".xlsx,.xls,.csv" class="d-none" required>
                        </div>

                        <div class="col-md-4">
                            {{-- Start date --}}
                            <label class="form-label" style="font-size:0.75rem; font-weight:600; color:#374151;">
                                Start Date
                                <span style="font-weight:400; color:#9ca3af;">— for relative days</span>
                            </label>
                            <input type="date" name="start_date" class="form-control form-control-sm"
                                value="{{ old('start_date', optional($project->start_date)->format('Y-m-d')) }}">
                            <div style="font-size:0.7rem; color:#9ca3af; margin-top:4px;">
                                Defaults to project start date ({{ optional($project->start_date)->format('M j, Y') ?? 'today' }}).
                                Start Day &amp; End Day columns in the spreadsheet are offset from this date.
                            </div>

                            <hr style="border-color:#f3f4f6; margin:14px 0 10px;">

                            {{-- Sample download --}}
                            <a href="{{ route('projects.deliverables.import.sample') }}"
                               class="btn btn-outline-secondary btn-sm w-100" style="font-size:0.75rem;">
                                <i class="bi bi-download me-1"></i>Download sample .xlsx
                            </a>
                            <div style="font-size:0.7rem; color:#9ca3af; margin-top:5px; text-align:center;">
                                Google Sheets: File → Download → Microsoft Excel (.xlsx)
                            </div>
                        </div>

                        {{-- Column cheatsheet --}}
                        <div class="col-12">
                            <div style="background:#f9fafb; border-radius:6px; padding:10px 12px; font-size:0.71rem; color:#6b7280;">
                                <strong style="color:#374151;">Column order (row 1 = header, skipped automatically):</strong><br>
                                <span class="badge bg-secondary me-1 mt-1">A: ID</span>
                                <span class="badge bg-secondary me-1 mt-1">B: Type</span>
                                <span class="badge bg-danger me-1 mt-1">C: Name *</span>
                                <span class="badge bg-secondary me-1 mt-1">D: Description</span>
                                <span class="badge bg-secondary me-1 mt-1">E: Parent ID</span>
                                <span class="badge bg-secondary me-1 mt-1">F: Depends On</span>
                                <span class="badge bg-secondary me-1 mt-1">G: Budget Hours</span>
                                <span class="badge bg-secondary me-1 mt-1">H: Assigned Role</span>
                                <span class="badge bg-secondary me-1 mt-1">I: Start Day</span>
                                <span class="badge bg-secondary me-1 mt-1">J: End Day</span>
                                <div class="mt-2">
                                    Type must be <code>DELIVERABLE</code>, <code>MILESTONE</code>, or <code>TASK</code>.
                                    Parent ID links a MILESTONE to its DELIVERABLE and a TASK to its MILESTONE (or DELIVERABLE).
                                    Depends On links tasks to predecessors (comma-separated IDs from column A).
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-info text-white" id="importDelivsBtn">
                        <i class="bi bi-upload me-1"></i>Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
.import-drop-zone {
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: border-color .2s, background .2s;
    background: #fafafa;
}
.import-drop-zone:hover, .import-drop-zone.drag-over {
    border-color: #0e7490;
    background: #ecfeff;
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    const dropZone  = document.getElementById('delvsDropZone');
    const fileInput = document.getElementById('delvsFileInput');
    const fileLabel = document.getElementById('delvsFileName');

    if (!dropZone) return;

    fileInput.addEventListener('change', () => {
        if (fileInput.files[0]) { fileLabel.textContent = fileInput.files[0].name; fileLabel.style.display = 'block'; }
    });
    dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault(); dropZone.classList.remove('drag-over');
        const file = e.dataTransfer.files[0];
        if (file) {
            const dt = new DataTransfer(); dt.items.add(file); fileInput.files = dt.files;
            fileLabel.textContent = file.name; fileLabel.style.display = 'block';
        }
    });

    document.getElementById('importDelivsForm').addEventListener('submit', function () {
        const btn = document.getElementById('importDelivsBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Importing…';
    });

    // Auto-open modal if there was a validation error on import_file
    @if($errors->has('import_file'))
    document.addEventListener('DOMContentLoaded', function () {
        var modal = new bootstrap.Modal(document.getElementById('importDelivsModal'));
        modal.show();
    });
    @endif
}());
</script>
@endpush

@endsection
