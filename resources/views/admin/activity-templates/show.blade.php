@extends('layouts.app')

@push('styles')
<style>
/* ── Layout ─────────────────────────────────────────────────── */
.tpl-deliverable   { border:1px solid #e5e7eb; border-radius:8px; margin-bottom:14px; overflow:hidden; }
.tpl-deliverable-header { background:#f9fafb; padding:10px 14px; display:flex; align-items:center; gap:8px; border-bottom:1px solid #e5e7eb; flex-wrap:wrap; }
.tpl-milestone     { border-left:3px solid #4c8bf5; background:#fff; margin:8px 14px; border-radius:6px; box-shadow:0 1px 3px rgba(0,0,0,.06); }
.tpl-milestone-header { padding:8px 12px; display:flex; align-items:center; gap:8px; border-bottom:1px solid #f3f4f6; flex-wrap:wrap; }
.tpl-task-row      { padding:6px 12px 6px 24px; display:flex; align-items:center; gap:8px; border-bottom:1px solid #f9fafb; font-size:0.8rem; flex-wrap:wrap; }
.tpl-task-row:last-child { border-bottom:none; }
.tpl-direct-task   { padding:6px 12px; display:flex; align-items:center; gap:8px; border-bottom:1px solid #f9fafb; font-size:0.8rem; flex-wrap:wrap; background:#fafffe; }
.tpl-direct-task:last-child { border-bottom:none; }
.add-row           { padding:8px 12px; background:#fafafa; border-top:1px dashed #e5e7eb; }

/* ── Badges ─────────────────────────────────────────────────── */
.badge-role   { display:inline-block; padding:1px 6px; border-radius:4px; font-size:0.68rem; background:#f3f4f6; color:#6b7280; font-weight:500; }
.badge-hours  { display:inline-block; padding:1px 6px; border-radius:4px; font-size:0.68rem; background:#ecfdf5; color:#059669; font-weight:600; }
.badge-days   { display:inline-block; padding:1px 6px; border-radius:4px; font-size:0.68rem; background:#eff6ff; color:#2563eb; font-weight:500; }
.badge-dep    { display:inline-block; padding:1px 6px; border-radius:4px; font-size:0.68rem; background:#fff7ed; color:#d97706; font-weight:500; }
.badge-max    { display:inline-block; padding:1px 6px; border-radius:4px; font-size:0.68rem; background:#faf5ff; color:#7c3aed; font-weight:600; }
.badge-over   { background:#fef2f2; color:#dc2626; }

/* ── Billing type banner ─────────────────────────────────────── */
.billing-banner { border-radius:6px; padding:8px 14px; margin-bottom:14px; display:flex; align-items:center; gap:10px; font-size:0.8rem; }

/* ── Field visibility controlled by JS ──────────────────────── */
.field-hours, .field-rate, .field-del-fee { transition:opacity .15s; }
.field-hidden { display:none !important; }
</style>
@endpush

@section('content')

@php
use App\Models\ActivityTemplate as AT;
$billingType  = $activityTemplate->billing_type;
$activeFields = $activityTemplate->active_fields;   // ['hours'], ['hours','rate'], etc.
$showRate     = in_array('rate', $activeFields);
$showDelFee   = in_array('del_fee', $activeFields);

$billingBannerStyle = [
    'fixed'            => 'background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0;',
    'time_and_material'=> 'background:#eff6ff; color:#1e3a8a; border:1px solid #bfdbfe;',
    'hybrid'           => 'background:#faf5ff; color:#4c1d95; border:1px solid #e9d5ff;',
    'retainer'         => 'background:#fff7ed; color:#78350f; border:1px solid #fed7aa;',
    'per_deliverable'  => 'background:#fef2f2; color:#7f1d1d; border:1px solid #fecaca;',
];

$billingDesc = [
    'fixed'            => 'Fixed Fee — capture <strong>hours only</strong>. Rates are set by the fee schedule at invoicing time.',
    'time_and_material'=> 'Time & Material — capture <strong>hours + role</strong>. Rates resolve per-role from the fee schedule.',
    'hybrid'           => 'Hybrid — capture <strong>hours, role, and optional deliverable fees</strong>.',
    'retainer'         => 'Retainer — capture <strong>hours per period</strong>. Flat retainer fee is set on the proposal.',
    'per_deliverable'  => 'Per Deliverable — capture <strong>deliverable fees + optional hours</strong>.',
];
@endphp

{{-- Back + Title --}}
<div class="d-flex align-items-start justify-content-between mb-3">
    <div>
        <a href="{{ route('admin.templates') }}" style="font-size:0.78rem; color:#6b7280; text-decoration:none;">
            <i class="bi bi-arrow-left me-1"></i>Templates
        </a>
        <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
            <h4 class="mb-0 fw-700" style="font-size:1rem;">{{ $activityTemplate->name }}</h4>
            @if($billingType)
            <span class="badge" style="{{ $billingBannerStyle[$billingType] ?? '' }} font-size:0.72rem; font-weight:600; padding:2px 8px;">
                {{ $activityTemplate->billing_type_label }}
            </span>
            @endif
            @if($activityTemplate->billing_cycle)
            <span class="badge" style="background:#f3f4f6; color:#6b7280; font-size:0.7rem;">
                {{ $activityTemplate->billing_cycle_label }}
            </span>
            @endif
        </div>
        <div style="font-size:0.75rem; color:#6b7280;">
            @if($activityTemplate->workType) {{ $activityTemplate->workType->name }} · @endif
            {{ number_format($activityTemplate->total_budgeted_hours, 1) }} hrs total
        </div>
    </div>
    <button class="btn btn-sm btn-outline-secondary" data-action="edit-template">
        <i class="bi bi-pencil me-1"></i>Edit Template
    </button>
</div>

{{-- Billing type guidance banner --}}
@if($billingType && isset($billingDesc[$billingType]))
<div class="billing-banner" style="{{ $billingBannerStyle[$billingType] ?? 'background:#f9fafb; color:#374151; border:1px solid #e5e7eb;' }}">
    <i class="bi bi-info-circle-fill" style="font-size:1rem; flex-shrink:0;"></i>
    <div>{!! $billingDesc[$billingType] !!}
        @if($activityTemplate->billing_cycle)
        · Billing cycle: <strong>{{ $activityTemplate->billing_cycle_label }}</strong>.
        @endif
    </div>
</div>
@endif

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger py-2 mb-3" style="font-size:0.8rem;">{{ session('error') }}</div>
@endif

<div class="row g-3">

    {{-- ── Deliverables tree ──────────────────────────────────────────────── --}}
    <div class="col-lg-8">

        @forelse($activityTemplate->deliverables as $deliverable)
        @php
            $delivTotal = $deliverable->total_hours;
            $isOver     = $deliverable->max_hours && $delivTotal > $deliverable->max_hours;
        @endphp
        <div class="tpl-deliverable">

            {{-- Deliverable header --}}
            <div class="tpl-deliverable-header">
                <i class="bi bi-folder2 text-primary" style="font-size:0.85rem;"></i>
                <span class="fw-600" style="font-size:0.85rem; flex:1;">{{ $deliverable->name }}</span>

                @if($deliverable->description)
                <span style="font-size:0.75rem; color:#9ca3af;">{{ Str::limit($deliverable->description, 60) }}</span>
                @endif

                {{-- Hours budget --}}
                @if($deliverable->max_hours)
                <span class="badge-max {{ $isOver ? 'badge-over' : '' }}" title="Hour budget for this deliverable">
                    {{ number_format($delivTotal, 1) }} / {{ number_format($deliverable->max_hours, 1) }}h cap
                    @if($isOver) <i class="bi bi-exclamation-triangle-fill ms-1"></i> @endif
                </span>
                @else
                <span class="badge-hours">{{ number_format($delivTotal, 1) }}h total</span>
                @endif

                {{-- Dependency --}}
                @if($deliverable->dependsOn)
                <span class="badge-dep" title="Starts after: {{ $deliverable->dependsOn->name }}">
                    <i class="bi bi-arrow-right-circle me-1"></i>after: {{ Str::limit($deliverable->dependsOn->name, 25) }}
                </span>
                @endif

                <button class="btn btn-xs btn-outline-secondary ms-auto"
                    style="font-size:0.7rem; padding:1px 7px;"
                    data-action="edit-deliverable"
                    data-id="{{ $deliverable->id }}"
                    data-name="{{ $deliverable->name }}"
                    data-description="{{ $deliverable->description }}"
                    data-max-hours="{{ $deliverable->max_hours }}"
                    data-depends-on="{{ $deliverable->depends_on_deliverable_id }}">Edit</button>

                <form method="POST" action="{{ route('admin.templates.deliverables.destroy', $deliverable) }}"
                    onsubmit="return confirm('Remove this deliverable and all its contents?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-xs btn-outline-danger" style="font-size:0.7rem; padding:1px 7px;">
                        <i class="bi bi-trash3"></i>
                    </button>
                </form>
            </div>

            {{-- ── Milestones (optional grouping) ─────────────────────────── --}}
            @foreach($deliverable->activities as $activity)
            <div class="tpl-milestone">
                <div class="tpl-milestone-header">
                    <i class="bi bi-flag text-warning" style="font-size:0.8rem;"></i>
                    <span class="fw-600" style="font-size:0.82rem; flex:1;">{{ $activity->name }}</span>

                    @if($activity->assigned_role)
                    <span class="badge-role">{{ $activity->assigned_role }}</span>
                    @endif

                    @if($activity->budgeted_hours)
                    <span class="badge-hours">{{ number_format($activity->budgeted_hours, 1) }}h</span>
                    @endif

                    @if($activity->relative_start_day !== null)
                    <span class="badge-days">Day {{ $activity->relative_start_day }}–{{ $activity->relative_end_day ?? '?' }}</span>
                    @endif

                    @if($activity->dependsOn)
                    <span class="badge-dep" title="Starts after: {{ $activity->dependsOn->name }}">
                        <i class="bi bi-arrow-right-circle me-1"></i>after: {{ Str::limit($activity->dependsOn->name, 20) }}
                    </span>
                    @endif

                    <button class="btn btn-xs btn-outline-secondary"
                        style="font-size:0.68rem; padding:1px 6px;"
                        data-action="edit-activity"
                        data-id="{{ $activity->id }}"
                        data-name="{{ $activity->name }}"
                        data-description="{{ $activity->description }}"
                        data-relative-start-day="{{ $activity->relative_start_day }}"
                        data-relative-end-day="{{ $activity->relative_end_day }}"
                        data-assigned-role="{{ $activity->assigned_role }}"
                        data-budgeted-hours="{{ $activity->budgeted_hours }}"
                        data-depends-on="{{ $activity->depends_on_activity_id }}"
                        data-deliverable-id="{{ $deliverable->id }}">Edit</button>

                    <form method="POST" action="{{ route('admin.templates.activities.destroy', $activity) }}"
                        onsubmit="return confirm('Remove this milestone and its tasks?')" class="mb-0">
                        @csrf @method('DELETE')
                        <button class="btn btn-xs btn-outline-danger" style="font-size:0.68rem; padding:1px 6px;">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </form>
                </div>

                {{-- Tasks under milestone --}}
                @foreach($activity->tasks as $task)
                <div class="tpl-task-row">
                    <i class="bi bi-check2-square" style="font-size:0.75rem; color:#9ca3af;"></i>
                    <span style="flex:1;">{{ $task->name }}</span>
                    @if($task->assigned_role)<span class="badge-role">{{ $task->assigned_role }}</span>@endif
                    @if($task->estimated_hours)<span class="badge-hours">{{ number_format($task->estimated_hours, 1) }}h</span>@endif
                    @if($task->relative_due_day !== null)<span class="badge-days">Due day {{ $task->relative_due_day }}</span>@endif
                    @if($task->dependsOn)
                    <span class="badge-dep" title="After: {{ $task->dependsOn->name }}">
                        <i class="bi bi-arrow-right-circle me-1"></i>after: {{ Str::limit($task->dependsOn->name, 18) }}
                    </span>
                    @endif

                    <button class="btn btn-xs btn-outline-secondary"
                        style="font-size:0.65rem; padding:1px 5px;"
                        data-action="edit-task"
                        data-id="{{ $task->id }}"
                        data-name="{{ $task->name }}"
                        data-description="{{ $task->description }}"
                        data-relative-due-day="{{ $task->relative_due_day }}"
                        data-assigned-role="{{ $task->assigned_role }}"
                        data-estimated-hours="{{ $task->estimated_hours }}"
                        data-depends-on="{{ $task->depends_on_task_id }}"
                        data-activity-id="{{ $activity->id }}"
                        data-context="milestone">Edit</button>

                    <form method="POST" action="{{ route('admin.templates.tasks.destroy', $task) }}"
                        onsubmit="return confirm('Remove task?')" class="mb-0">
                        @csrf @method('DELETE')
                        <button class="btn btn-xs btn-outline-danger" style="font-size:0.65rem; padding:1px 5px;">
                            <i class="bi bi-x"></i>
                        </button>
                    </form>
                </div>
                @endforeach

                {{-- Add task under milestone --}}
                <div class="add-row">
                    <form method="POST" action="{{ route('admin.templates.tasks.store', $activity) }}"
                        class="row g-1 align-items-center">
                        @csrf
                        <div class="col-sm-4">
                            <input type="text" name="name" class="form-control form-control-sm"
                                placeholder="+ Task name" required style="font-size:0.78rem;">
                        </div>
                        <div class="col-sm-2 field-hours">
                            <input type="number" name="estimated_hours" class="form-control form-control-sm"
                                step="0.25" min="0" placeholder="Hrs" style="font-size:0.78rem;">
                        </div>
                        <div class="col-sm-2">
                            <input type="text" name="assigned_role" class="form-control form-control-sm"
                                placeholder="Role" style="font-size:0.78rem;">
                        </div>
                        <div class="col-sm-2">
                            <select name="depends_on_task_id" class="form-select form-select-sm" style="font-size:0.78rem;" title="Depends on (task must complete first)">
                                <option value="">no dependency</option>
                                @foreach($activity->tasks as $pt)
                                <option value="{{ $pt->id }}">↳ {{ $pt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-2">
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100"
                                style="font-size:0.75rem; padding:3px 8px;">Add Task</button>
                        </div>
                    </form>
                </div>
            </div>
            @endforeach

            {{-- ── Direct Tasks (no milestone) ──────────────────────────────── --}}
            @if($deliverable->directTasks->isNotEmpty())
            <div style="border-top:1px dashed #e5e7eb; margin:0 14px 8px;">
                <div style="font-size:0.7rem; color:#9ca3af; padding:6px 0 2px; font-weight:600; letter-spacing:0.03em;">
                    DIRECT TASKS (no milestone)
                </div>
                @foreach($deliverable->directTasks as $task)
                <div class="tpl-direct-task">
                    <i class="bi bi-check2-square" style="font-size:0.75rem; color:#059669;"></i>
                    <span style="flex:1;">{{ $task->name }}</span>
                    @if($task->assigned_role)<span class="badge-role">{{ $task->assigned_role }}</span>@endif
                    @if($task->estimated_hours)<span class="badge-hours">{{ number_format($task->estimated_hours, 1) }}h</span>@endif
                    @if($task->relative_due_day !== null)<span class="badge-days">Due day {{ $task->relative_due_day }}</span>@endif
                    @if($task->dependsOn)
                    <span class="badge-dep">
                        <i class="bi bi-arrow-right-circle me-1"></i>after: {{ Str::limit($task->dependsOn->name, 18) }}
                    </span>
                    @endif

                    <button class="btn btn-xs btn-outline-secondary"
                        style="font-size:0.65rem; padding:1px 5px;"
                        data-action="edit-task"
                        data-id="{{ $task->id }}"
                        data-name="{{ $task->name }}"
                        data-description="{{ $task->description }}"
                        data-relative-due-day="{{ $task->relative_due_day }}"
                        data-assigned-role="{{ $task->assigned_role }}"
                        data-estimated-hours="{{ $task->estimated_hours }}"
                        data-depends-on="{{ $task->depends_on_task_id }}"
                        data-deliverable-id="{{ $deliverable->id }}"
                        data-context="direct">Edit</button>

                    <form method="POST" action="{{ route('admin.templates.tasks.destroy', $task) }}"
                        onsubmit="return confirm('Remove task?')" class="mb-0">
                        @csrf @method('DELETE')
                        <button class="btn btn-xs btn-outline-danger" style="font-size:0.65rem; padding:1px 5px;">
                            <i class="bi bi-x"></i>
                        </button>
                    </form>
                </div>
                @endforeach
            </div>
            @endif

            {{-- ── Add Milestone ────────────────────────────────────────────── --}}
            <div class="p-3" style="background:#f9fafb; border-top:1px solid #f3f4f6;">
                <div style="font-size:0.7rem; color:#6b7280; font-weight:600; margin-bottom:6px;">
                    <i class="bi bi-flag me-1 text-warning"></i>Add Milestone
                    <span style="font-weight:400; color:#9ca3af;">(optional grouping)</span>
                </div>
                <form method="POST" action="{{ route('admin.templates.activities.store', $deliverable) }}"
                    class="row g-1 align-items-center">
                    @csrf
                    <div class="col-sm-3">
                        <input type="text" name="name" class="form-control form-control-sm"
                            placeholder="Milestone name" required style="font-size:0.78rem;">
                    </div>
                    <div class="col-sm-2 field-hours">
                        <input type="number" name="budgeted_hours" class="form-control form-control-sm"
                            step="0.25" min="0" placeholder="Hrs" style="font-size:0.78rem;">
                    </div>
                    <div class="col-sm-1">
                        <input type="number" name="relative_start_day" class="form-control form-control-sm"
                            min="0" placeholder="Start" style="font-size:0.78rem;" title="Relative start day">
                    </div>
                    <div class="col-sm-1">
                        <input type="number" name="relative_end_day" class="form-control form-control-sm"
                            min="0" placeholder="End" style="font-size:0.78rem;" title="Relative end day">
                    </div>
                    <div class="col-sm-2">
                        <input type="text" name="assigned_role" class="form-control form-control-sm"
                            placeholder="Role" style="font-size:0.78rem;">
                    </div>
                    <div class="col-sm-3">
                        <select name="depends_on_activity_id" class="form-select form-select-sm" style="font-size:0.78rem;" title="Starts after milestone">
                            <option value="">no dependency</option>
                            @foreach($deliverable->activities as $pa)
                            <option value="{{ $pa->id }}">↳ after: {{ $pa->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-12 mt-1">
                        <button type="submit" class="btn btn-sm btn-outline-secondary"
                            style="font-size:0.75rem; padding:3px 12px;">Add Milestone</button>
                    </div>
                </form>
            </div>

            {{-- ── Add Direct Task (no milestone) ─────────────────────────── --}}
            <div class="p-3 pt-0" style="background:#f9fafb;">
                <div style="font-size:0.7rem; color:#059669; font-weight:600; margin-bottom:6px;">
                    <i class="bi bi-check2-square me-1"></i>Add Task directly to deliverable
                    <span style="font-weight:400; color:#9ca3af;">(without milestone)</span>
                </div>
                <form method="POST" action="{{ route('admin.templates.deliverables.tasks.store', $deliverable) }}"
                    class="row g-1 align-items-center">
                    @csrf
                    <div class="col-sm-4">
                        <input type="text" name="name" class="form-control form-control-sm"
                            placeholder="Task name" required style="font-size:0.78rem;">
                    </div>
                    <div class="col-sm-2 field-hours">
                        <input type="number" name="estimated_hours" class="form-control form-control-sm"
                            step="0.25" min="0" placeholder="Hrs" style="font-size:0.78rem;">
                    </div>
                    <div class="col-sm-2">
                        <input type="text" name="assigned_role" class="form-control form-control-sm"
                            placeholder="Role" style="font-size:0.78rem;">
                    </div>
                    <div class="col-sm-2">
                        <select name="depends_on_task_id" class="form-select form-select-sm" style="font-size:0.78rem;" title="Depends on">
                            <option value="">no dependency</option>
                            @foreach($deliverable->directTasks as $pt)
                            <option value="{{ $pt->id }}">↳ {{ $pt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <button type="submit" class="btn btn-sm btn-outline-success w-100"
                            style="font-size:0.75rem; padding:3px 8px;">Add Task</button>
                    </div>
                </form>
            </div>

        </div>
        @empty
        <div class="kore-card text-center py-4" style="font-size:0.85rem; color:#9ca3af;">
            No deliverables yet. Add your first one on the right.
        </div>
        @endforelse

    </div>

    {{-- ── Sidebar ──────────────────────────────────────────────────────────── --}}
    <div class="col-lg-4">

        {{-- Add Deliverable --}}
        <div class="kore-card mb-3">
            <div class="fw-600 mb-2" style="font-size:0.82rem; color:#374151;">
                <i class="bi bi-folder-plus me-1 text-primary"></i>Add Deliverable
            </div>
            <form method="POST" action="{{ route('admin.templates.deliverables.store', $activityTemplate) }}">
                @csrf
                <div class="mb-2">
                    <input type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror"
                        placeholder="Deliverable name" required>
                    @error('name')<div class="invalid-feedback" style="font-size:0.72rem;">{{ $message }}</div>@enderror
                </div>
                <div class="mb-2">
                    <textarea name="description" class="form-control form-control-sm" rows="2"
                        placeholder="Description (optional)"></textarea>
                </div>

                {{-- Max hours (always shown — acts as hour budget regardless of billing type) --}}
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.73rem; font-weight:600; color:#374151;">
                        Hour Budget <span style="color:#9ca3af; font-weight:400;">(cap — milestones + tasks must not exceed)</span>
                    </label>
                    <input type="number" name="max_hours" class="form-control form-control-sm"
                        step="0.25" min="0" placeholder="e.g. 80">
                </div>

                {{-- Dependency --}}
                <div class="mb-3">
                    <label class="form-label" style="font-size:0.73rem; font-weight:600; color:#374151;">
                        Starts after <span style="color:#9ca3af; font-weight:400;">(optional dependency)</span>
                    </label>
                    <select name="depends_on_deliverable_id" class="form-select form-select-sm">
                        <option value="">— none —</option>
                        @foreach($activityTemplate->deliverables as $d)
                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-sm w-100">Add Deliverable</button>
            </form>
        </div>

        {{-- Summary --}}
        <div class="kore-card mb-3">
            <div class="fw-600 mb-2" style="font-size:0.82rem; color:#374151;">Summary</div>
            @php
                $delivCount  = $activityTemplate->deliverables->count();
                $msCount     = $activityTemplate->deliverables->sum(fn($d) => $d->activities->count());
                $taskCount   = $activityTemplate->deliverables->sum(fn($d) =>
                    $d->activities->sum(fn($a) => $a->tasks->count()) + $d->directTasks->count()
                );
            @endphp
            <div class="d-flex gap-3 text-center">
                <div style="flex:1;">
                    <div class="fw-700" style="font-size:1.1rem; color:#4c8bf5;">{{ $delivCount }}</div>
                    <div style="font-size:0.72rem; color:#9ca3af;">Deliverables</div>
                </div>
                <div style="flex:1;">
                    <div class="fw-700" style="font-size:1.1rem; color:#f59e0b;">{{ $msCount }}</div>
                    <div style="font-size:0.72rem; color:#9ca3af;">Milestones</div>
                </div>
                <div style="flex:1;">
                    <div class="fw-700" style="font-size:1.1rem; color:#059669;">{{ $taskCount }}</div>
                    <div style="font-size:0.72rem; color:#9ca3af;">Tasks</div>
                </div>
                <div style="flex:1;">
                    <div class="fw-700" style="font-size:1.1rem; color:#374151;">{{ number_format($activityTemplate->total_budgeted_hours, 1) }}</div>
                    <div style="font-size:0.72rem; color:#9ca3af;">Hrs</div>
                </div>
            </div>
        </div>

        {{-- Billing type guide --}}
        <div class="kore-card" style="font-size:0.78rem; color:#374151;">
            <div class="fw-600 mb-2" style="font-size:0.82rem;">Field Guide by Billing Type</div>
            <table class="table table-sm mb-0" style="font-size:0.72rem;">
                <thead><tr style="background:#f9fafb;">
                    <th>Type</th><th class="text-center">Hours</th><th class="text-center">Rate</th><th class="text-center">Del. Fee</th>
                </tr></thead>
                <tbody>
                    <tr><td>Fixed Fee</td><td class="text-center text-success">✓</td><td class="text-center text-muted">—</td><td class="text-center text-muted">—</td></tr>
                    <tr><td>T&amp;M</td><td class="text-center text-success">✓</td><td class="text-center text-success">✓</td><td class="text-center text-muted">—</td></tr>
                    <tr><td>Hybrid</td><td class="text-center text-success">✓</td><td class="text-center text-success">✓</td><td class="text-center text-success">✓</td></tr>
                    <tr><td>Retainer</td><td class="text-center text-success">✓</td><td class="text-center text-muted">—</td><td class="text-center text-muted">—</td></tr>
                    <tr><td>Per Deliv.</td><td class="text-center text-muted">opt.</td><td class="text-center text-muted">—</td><td class="text-center text-success">✓</td></tr>
                </tbody>
            </table>
            <div style="font-size:0.68rem; color:#9ca3af; margin-top:6px;">
                Dates (start/end) are set in the <strong>project</strong>, not the template. Relative days are used here to define offsets.
            </div>
        </div>

    </div>
</div>

{{-- ════════════════════════ MODALS ════════════════════════ --}}

{{-- Edit Template Modal --}}
<div class="modal fade" id="editTemplateModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.templates.update', $activityTemplate) }}" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header py-2">
                <h6 class="modal-title fw-600">Edit Template</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.78rem; font-weight:600;">Name *</label>
                    <input type="text" name="name" id="editTplName" class="form-control form-control-sm" required>
                </div>
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.78rem; font-weight:600;">
                        Billing Type
                        <span style="font-weight:400; color:#9ca3af;">— dictates fields shown</span>
                    </label>
                    <select name="billing_type" id="editTplBillingType" class="form-select form-select-sm">
                        <option value="">— Any / Not specified —</option>
                        @foreach(\App\Models\ActivityTemplate::BILLING_TYPES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.78rem; font-weight:600;">Billing Cycle</label>
                    <select name="billing_cycle" id="editTplBillingCycle" class="form-select form-select-sm">
                        <option value="">— Not specified —</option>
                        @foreach(\App\Models\ActivityTemplate::BILLING_CYCLES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <div id="editCycleHint" style="font-size:0.7rem; color:#6b7280; margin-top:3px;"></div>
                </div>
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.78rem; font-weight:600;">Work Type</label>
                    <select name="work_type_id" id="editTplWorkType" class="form-select form-select-sm">
                        <option value="">— Any —</option>
                        @foreach($workTypes as $wt)
                        <option value="{{ $wt->id }}" @selected($activityTemplate->work_type_id == $wt->id)>{{ $wt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.78rem; font-weight:600;">Description</label>
                    <textarea name="description" id="editTplDesc" class="form-control form-control-sm" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Deliverable Modal --}}
<div class="modal fade" id="editDeliverableModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="editDeliverableForm" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header py-2">
                <h6 class="modal-title fw-600">Edit Deliverable</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.78rem; font-weight:600;">Name *</label>
                    <input type="text" name="name" id="editDelName" class="form-control form-control-sm" required>
                </div>
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.78rem; font-weight:600;">Description</label>
                    <textarea name="description" id="editDelDesc" class="form-control form-control-sm" rows="2"></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.78rem; font-weight:600;">
                        Hour Budget <span style="color:#9ca3af; font-weight:400;">(cap)</span>
                    </label>
                    <input type="number" name="max_hours" id="editDelMaxHours"
                        class="form-control form-control-sm" step="0.25" min="0" placeholder="Leave blank for no cap">
                </div>
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.78rem; font-weight:600;">
                        Starts after <span style="color:#9ca3af; font-weight:400;">(dependency)</span>
                    </label>
                    <select name="depends_on_deliverable_id" id="editDelDependsOn" class="form-select form-select-sm">
                        <option value="">— none —</option>
                        @foreach($activityTemplate->deliverables as $d)
                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Milestone Modal --}}
<div class="modal fade" id="editActivityModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="editActivityForm" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header py-2">
                <h6 class="modal-title fw-600">Edit Milestone</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.78rem; font-weight:600;">Name *</label>
                    <input type="text" name="name" id="editActName" class="form-control form-control-sm" required>
                </div>
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.78rem; font-weight:600;">Description</label>
                    <textarea name="description" id="editActDesc" class="form-control form-control-sm" rows="2"></textarea>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600;">
                            Relative Start Day
                            <span class="text-muted fw-normal" style="font-size:0.7rem;">(from project start)</span>
                        </label>
                        <input type="number" name="relative_start_day" id="editActStartDay"
                            class="form-control form-control-sm" min="0" placeholder="e.g. 0">
                    </div>
                    <div class="col-6">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600;">Relative End Day</label>
                        <input type="number" name="relative_end_day" id="editActEndDay"
                            class="form-control form-control-sm" min="0" placeholder="e.g. 14">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600;">Assigned Role</label>
                        <input type="text" name="assigned_role" id="editActRole"
                            class="form-control form-control-sm" placeholder="e.g. Engineer">
                    </div>
                    <div class="col-6 field-hours">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600;">Budgeted Hours</label>
                        <input type="number" name="budgeted_hours" id="editActHours"
                            class="form-control form-control-sm" step="0.25" min="0" placeholder="0">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.78rem; font-weight:600;">
                        Starts after <span style="color:#9ca3af; font-weight:400;">(milestone dependency)</span>
                    </label>
                    <select name="depends_on_activity_id" id="editActDependsOn" class="form-select form-select-sm">
                        <option value="">— none —</option>
                        {{-- Options populated by JS per deliverable --}}
                    </select>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Task Modal --}}
<div class="modal fade" id="editTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="editTaskForm" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header py-2">
                <h6 class="modal-title fw-600">Edit Task</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.78rem; font-weight:600;">Name *</label>
                    <input type="text" name="name" id="editTaskName" class="form-control form-control-sm" required>
                </div>
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.78rem; font-weight:600;">Description</label>
                    <textarea name="description" id="editTaskDesc" class="form-control form-control-sm" rows="2"></textarea>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600;">
                            Relative Due Day
                            <span class="text-muted fw-normal" style="font-size:0.7rem;">(from project start)</span>
                        </label>
                        <input type="number" name="relative_due_day" id="editTaskDueDay"
                            class="form-control form-control-sm" min="0" placeholder="e.g. 10">
                    </div>
                    <div class="col-6 field-hours">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600;">Estimated Hours</label>
                        <input type="number" name="estimated_hours" id="editTaskHours"
                            class="form-control form-control-sm" step="0.25" min="0" placeholder="0">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.78rem; font-weight:600;">Assigned Role</label>
                    <input type="text" name="assigned_role" id="editTaskRole"
                        class="form-control form-control-sm" placeholder="e.g. Drafter">
                </div>
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.78rem; font-weight:600;">
                        Depends on <span style="color:#9ca3af; font-weight:400;">(task must complete first)</span>
                    </label>
                    <select name="depends_on_task_id" id="editTaskDependsOn" class="form-select form-select-sm">
                        <option value="">— none —</option>
                        {{-- Options populated by JS per activity/deliverable context --}}
                    </select>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// ── Data from server ──────────────────────────────────────────────────────
const BILLING_TYPE        = @json($activityTemplate->billing_type ?? '');
const TYPE_CYCLES         = @json(\App\Models\ActivityTemplate::BILLING_TYPE_CYCLES);
const CYCLE_LABELS        = @json(\App\Models\ActivityTemplate::BILLING_CYCLES);

// Full deliverable/milestone/task map for populating dependency selects
const DELIVERABLES = @json(
    $activityTemplate->deliverables->map(fn($d) => [
        'id'         => $d->id,
        'name'       => $d->name,
        'activities' => $d->activities->map(fn($a) => [
            'id'    => $a->id,
            'name'  => $a->name,
            'tasks' => $a->tasks->map(fn($t) => ['id' => $t->id, 'name' => $t->name]),
        ]),
        'directTasks' => $d->directTasks->map(fn($t) => ['id' => $t->id, 'name' => $t->name]),
    ])
);

// ── Per-deliverable ID → activity/task lookup ─────────────────────────────
const deliverableActivities = {};
const deliverableTasks      = {};
for (const d of DELIVERABLES) {
    deliverableActivities[d.id] = d.activities;
    deliverableTasks[d.id]      = d.directTasks;
}

// ── Helper: populate a <select> with options ──────────────────────────────
function populateSelect(select, items, currentId) {
    const blank = select.querySelector('option[value=""]');
    select.innerHTML = '';
    if (blank) select.appendChild(blank);
    for (const item of items) {
        const opt = document.createElement('option');
        opt.value = item.id;
        opt.textContent = item.name;
        if (currentId && String(item.id) === String(currentId)) opt.selected = true;
        select.appendChild(opt);
    }
}

// ── Click delegation ──────────────────────────────────────────────────────
document.addEventListener('click', function(e) {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const action = btn.dataset.action;

    // ── Edit Template ──────────────────────────────────────────────────────
    if (action === 'edit-template') {
        document.getElementById('editTplName').value         = @json($activityTemplate->name);
        document.getElementById('editTplDesc').value          = @json($activityTemplate->description ?? '');
        document.getElementById('editTplBillingType').value   = @json($activityTemplate->billing_type ?? '');
        document.getElementById('editTplBillingCycle').value  = @json($activityTemplate->billing_cycle ?? '');
        updateCycleHint('editTplBillingType', 'editCycleHint');
        new bootstrap.Modal(document.getElementById('editTemplateModal')).show();
    }

    // ── Edit Deliverable ───────────────────────────────────────────────────
    if (action === 'edit-deliverable') {
        document.getElementById('editDelName').value         = btn.dataset.name;
        document.getElementById('editDelDesc').value         = btn.dataset.description ?? '';
        document.getElementById('editDelMaxHours').value     = btn.dataset.maxHours ?? '';
        const depSel = document.getElementById('editDelDependsOn');
        depSel.value = btn.dataset.dependsOn ?? '';
        document.getElementById('editDeliverableForm').action =
            '/admin/templates/deliverables/' + btn.dataset.id;
        new bootstrap.Modal(document.getElementById('editDeliverableModal')).show();
    }

    // ── Edit Activity/Milestone ────────────────────────────────────────────
    if (action === 'edit-activity') {
        document.getElementById('editActName').value      = btn.dataset.name;
        document.getElementById('editActDesc').value      = btn.dataset.description ?? '';
        document.getElementById('editActStartDay').value  = btn.dataset.relativeStartDay ?? '';
        document.getElementById('editActEndDay').value    = btn.dataset.relativeEndDay ?? '';
        document.getElementById('editActRole').value      = btn.dataset.assignedRole ?? '';
        document.getElementById('editActHours').value     = btn.dataset.budgetedHours ?? '';

        // Populate sibling milestones (same deliverable) for dependency select
        const delivId  = btn.dataset.deliverableId;
        const actId    = btn.dataset.id;
        const siblings = (deliverableActivities[delivId] || []).filter(a => String(a.id) !== String(actId));
        populateSelect(document.getElementById('editActDependsOn'), siblings, btn.dataset.dependsOn);

        document.getElementById('editActivityForm').action =
            '/admin/templates/activities/' + actId;
        new bootstrap.Modal(document.getElementById('editActivityModal')).show();
    }

    // ── Edit Task ──────────────────────────────────────────────────────────
    if (action === 'edit-task') {
        document.getElementById('editTaskName').value    = btn.dataset.name;
        document.getElementById('editTaskDesc').value    = btn.dataset.description ?? '';
        document.getElementById('editTaskDueDay').value  = btn.dataset.relativeDueDay ?? '';
        document.getElementById('editTaskHours').value   = btn.dataset.estimatedHours ?? '';
        document.getElementById('editTaskRole').value    = btn.dataset.assignedRole ?? '';

        const taskId  = btn.dataset.id;
        const context = btn.dataset.context; // 'milestone' or 'direct'
        let siblings  = [];

        if (context === 'milestone') {
            const actId = btn.dataset.activityId;
            for (const d of DELIVERABLES) {
                const act = d.activities.find(a => String(a.id) === String(actId));
                if (act) { siblings = act.tasks.filter(t => String(t.id) !== String(taskId)); break; }
            }
        } else {
            // direct task under deliverable
            const delId = btn.dataset.deliverableId;
            siblings = (deliverableTasks[delId] || []).filter(t => String(t.id) !== String(taskId));
        }

        populateSelect(document.getElementById('editTaskDependsOn'), siblings, btn.dataset.dependsOn);

        document.getElementById('editTaskForm').action =
            '/admin/templates/tasks/' + taskId;
        new bootstrap.Modal(document.getElementById('editTaskModal')).show();
    }
});

// ── Billing cycle hint helper ────────────────────────────────────────────
function updateCycleHint(typeSelectId, hintId) {
    const type = document.getElementById(typeSelectId)?.value;
    const hint = document.getElementById(hintId);
    if (!hint) return;
    if (type && TYPE_CYCLES[type]) {
        const recommended = TYPE_CYCLES[type].map(c => CYCLE_LABELS[c]).join(', ');
        hint.textContent = 'Recommended cycles: ' + recommended;
    } else {
        hint.textContent = '';
    }
}

document.getElementById('editTplBillingType')?.addEventListener('change', function() {
    updateCycleHint('editTplBillingType', 'editCycleHint');
});
</script>
@endpush

@endsection
