@extends('layouts.app')

@section('content')

@php $ref = $project->year.'-'.str_pad($project->project_number, 3, '0', STR_PAD_LEFT); @endphp

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">Deliverables — {{ $project->title }}</h4>
        <div style="font-size:0.75rem; color:#6b7280;">
            Project {{ $ref }}
            @if($project->proposal)
            &bull;
            <span class="badge {{ $isFixed ? 'bg-success' : 'bg-primary' }}" style="font-size:0.65rem;">
                {{ $isFixed ? 'Fixed Fee' : ucwords(str_replace('_',' ', $billingType)) }}
            </span>
            @if($project->proposal->feeSchedule)
            &bull; <i class="bi bi-cash-stack me-1"></i>{{ $project->proposal->feeSchedule->name }}
            @endif
            @endif
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

<div class="row g-4">

    {{-- Work Breakdown --}}
    <div class="col-lg-8">
        @forelse($project->deliverables as $deliverable)
        <div class="kore-card mb-3" id="deliverable-{{ $deliverable->id }}">

            {{-- Deliverable Header --}}
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="flex-grow-1">
                    <div class="fw-600" style="font-size:0.9rem;">
                        <i class="bi bi-folder2-open me-1" style="color:#4c8bf5;"></i>
                        <span class="deliverable-name-display">{{ $deliverable->name }}</span>
                    </div>
                    @if($deliverable->description)
                    <div class="deliverable-desc-display" style="font-size:0.78rem; color:#6b7280; margin-top:2px;">{{ $deliverable->description }}</div>
                    @endif
                    <div class="d-flex gap-3 mt-1" style="font-size:0.72rem; color:#9ca3af;">
                        @if($isHourly && $deliverable->budget_hours > 0)
                        <span><i class="bi bi-hourglass me-1"></i>{{ number_format($deliverable->budget_hours, 1) }}h budget</span>
                        @endif
                        @if($isFixed && $deliverable->deliverable_fee > 0)
                        <span class="text-success fw-600">${{ number_format($deliverable->deliverable_fee, 0) }} fixed</span>
                        @elseif($isHourly && $deliverable->deliverable_fee > 0)
                        <span class="text-primary fw-600">${{ number_format($deliverable->deliverable_fee, 0) }} est.</span>
                        @endif
                        @if($deliverable->due_date)
                        <span><i class="bi bi-calendar3 me-1"></i>Due {{ $deliverable->due_date->format('M j') }}</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-secondary"
                        onclick="openEditDeliverable({{ $deliverable->id }}, '{{ addslashes($deliverable->name) }}', '{{ addslashes($deliverable->description ?? '') }}', {{ $deliverable->budget_hours ?? 0 }}, {{ $deliverable->rate ?? 0 }}, {{ $deliverable->deliverable_fee ?? 0 }}, '{{ $deliverable->due_date?->format('Y-m-d') ?? '' }}')"
                        title="Edit deliverable">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <form action="{{ route('projects.deliverables.destroy', [$project, $deliverable]) }}" method="POST"
                        onsubmit="return confirm('Delete deliverable and all its milestones and tasks?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete deliverable">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Milestones --}}
            @foreach($deliverable->milestones as $milestone)
            <div class="ms-2 mb-3 pb-2" style="border-left:2px solid #e5e7eb; padding-left:14px;" id="milestone-{{ $milestone->id }}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-500" style="font-size:0.82rem; color:#374151;">
                        <i class="bi bi-flag me-1" style="color:#f59e0b;"></i>
                        <span class="milestone-name-display">{{ $milestone->name }}</span>
                        @if($isFixed && $milestone->deliverable_fee > 0)
                        <span class="ms-2 badge bg-success-subtle text-success" style="font-size:0.65rem;">${{ number_format($milestone->deliverable_fee, 0) }}</span>
                        @elseif($isHourly && $milestone->budget_hours > 0)
                        <span class="ms-2 text-muted" style="font-size:0.7rem;">{{ number_format($milestone->budget_hours,1) }}h</span>
                        @endif
                    </div>
                    <div class="d-flex gap-1">
                        <button class="btn btn-link btn-sm py-0 px-1 text-secondary"
                            onclick="openEditMilestone({{ $milestone->id }}, '{{ addslashes($milestone->name) }}', '{{ addslashes($milestone->description ?? '') }}', {{ $milestone->budget_hours ?? 0 }}, {{ $milestone->rate ?? 0 }}, {{ $milestone->deliverable_fee ?? 0 }}, '{{ $milestone->due_date?->format('Y-m-d') ?? '' }}', '{{ $milestone->billing_status ?? 'pending' }}')"
                            title="Edit milestone" style="font-size:0.75rem;">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form action="{{ route('projects.milestones.destroy', $milestone) }}" method="POST"
                            onsubmit="return confirm('Delete milestone and all its tasks?')" class="d-inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-link btn-sm py-0 px-1 text-danger" title="Delete" style="font-size:0.75rem;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Tasks --}}
                @foreach($milestone->tasks as $task)
                <div class="d-flex align-items-center gap-2 mb-1 ms-2 task-row" id="task-{{ $task->id }}">
                    @php
                        $icons = [
                            'complete'    => 'bi-check-circle-fill text-success',
                            'in_progress' => 'bi-play-circle-fill text-primary',
                            'cancelled'   => 'bi-x-circle-fill text-secondary',
                            'pending'     => 'bi-circle text-secondary',
                        ];
                        $icon = $icons[$task->status] ?? 'bi-circle text-secondary';
                    @endphp
                    <i class="bi {{ $icon }}" style="font-size:0.8rem; flex-shrink:0;"></i>
                    <span style="font-size:0.8rem; flex-grow:1;" class="{{ $task->status === 'cancelled' ? 'text-decoration-line-through text-muted' : '' }}">
                        {{ $task->name }}
                    </span>
                    @if($task->end_date)
                    @php $overdue = $task->status !== 'complete' && $task->status !== 'cancelled' && $task->end_date->isPast(); @endphp
                    <span style="font-size:0.72rem;" class="{{ $overdue ? 'text-danger fw-600' : 'text-muted' }}">
                        {{ $overdue ? '⚠ ' : '' }}Due {{ $task->end_date->format('M d') }}
                    </span>
                    @endif

                    {{-- Quick status --}}
                    <form action="{{ route('projects.tasks.update', $task) }}" method="POST" class="d-flex align-items-center gap-1">
                        @csrf @method('PUT')
                        <input type="hidden" name="name" value="{{ $task->name }}">
                        <select name="status" class="form-select form-select-sm py-0"
                            style="font-size:0.7rem; width:auto; height:24px;"
                            onchange="this.form.submit()">
                            <option value="pending"     {{ $task->status=='pending'     ? 'selected':'' }}>Pending</option>
                            <option value="in_progress" {{ $task->status=='in_progress' ? 'selected':'' }}>In Progress</option>
                            <option value="complete"    {{ $task->status=='complete'    ? 'selected':'' }}>Complete</option>
                            <option value="cancelled"   {{ $task->status=='cancelled'   ? 'selected':'' }}>Cancelled</option>
                        </select>
                    </form>

                    @if($isHourly && $task->budget_hours > 0)
                    <span style="font-size:0.68rem; color:#9ca3af;"><i class="bi bi-hourglass me-1"></i>{{ number_format($task->budget_hours, 1) }}h</span>
                    @endif

                    {{-- Edit task --}}
                    <button class="btn btn-link btn-sm py-0 px-1 text-secondary"
                        onclick="openEditTask({{ $task->id }}, '{{ addslashes($task->name) }}', '{{ addslashes($task->description ?? '') }}', '{{ $task->start_date?->format('Y-m-d') ?? '' }}', '{{ $task->end_date?->format('Y-m-d') ?? '' }}', '{{ $task->status }}', {{ $task->budget_hours ?? 0 }}, {{ $task->rate ?? 0 }})"
                        title="Edit task" style="font-size:0.75rem;">
                        <i class="bi bi-pencil"></i>
                    </button>

                    {{-- Delete task --}}
                    <form action="{{ route('projects.tasks.destroy', $task) }}" method="POST"
                        onsubmit="return confirm('Delete task?')" class="d-inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-link btn-sm py-0 px-1 text-danger" style="font-size:0.75rem;">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
                @endforeach

                {{-- Add Task --}}
                <details class="ms-2 mt-2">
                    <summary style="font-size:0.75rem; color:#4c8bf5; cursor:pointer; list-style:none;">
                        <i class="bi bi-plus-sm"></i> Add task
                    </summary>
                    <form action="{{ route('projects.tasks.store', $milestone) }}" method="POST" class="mt-2">
                        @csrf
                        <div class="row g-2">
                            <div class="col-sm-12">
                                <input type="text" name="name" class="form-control form-control-sm" placeholder="Task name" required>
                            </div>
                            @if($isHourly)
                            <div class="col-sm-4">
                                <input type="number" name="budget_hours" class="form-control form-control-sm" placeholder="Budget hrs" step="0.25" min="0">
                            </div>
                            @endif
                            <div class="col-sm-{{ $isHourly ? '4' : '5' }}">
                                <input type="date" name="end_date" class="form-control form-control-sm" placeholder="Due date">
                            </div>
                            <div class="col-sm-{{ $isHourly ? '4' : '4' }}">
                                <select name="status" class="form-select form-select-sm">
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">In Progress</option>
                                </select>
                            </div>
                            <div class="col-sm-3">
                                <button type="submit" class="btn btn-sm btn-primary w-100">Add</button>
                            </div>
                        </div>
                    </form>
                </details>
            </div>
            @endforeach

            {{-- Add Milestone --}}
            <details class="ms-2 mt-2">
                <summary style="font-size:0.75rem; color:#6b7280; cursor:pointer; list-style:none;">
                    <i class="bi bi-plus-sm"></i> Add milestone
                </summary>
                <form action="{{ route('projects.milestones.store', [$project, $deliverable]) }}" method="POST" class="mt-2">
                    @csrf
                    <div class="row g-2">
                        <div class="col-sm-5">
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
                        <div class="col-sm-3">
                            <input type="number" name="budget_hours" class="form-control form-control-sm" step="0.25" min="0" placeholder="Budget hrs">
                        </div>
                        @endif
                        <div class="col-sm-2">
                            <input type="date" name="due_date" class="form-control form-control-sm">
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
            No deliverables yet.
        </div>
        @endforelse
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">
        {{-- Add Deliverable --}}
        <div class="kore-card mb-3">
            <div class="fw-600 mb-3" style="font-size:0.85rem;">Add Deliverable</div>
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
                {{-- Fixed fee: only show fixed amount and due date --}}
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
                {{-- Hourly / T&M: show hours + role from schedule --}}
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label" style="font-size:0.75rem;">Budget Hours</label>
                        <input type="number" name="budget_hours" class="form-control form-control-sm" step="0.25" min="0" placeholder="0.00">
                    </div>
                    <div class="col-6">
                        <label class="form-label" style="font-size:0.75rem;">Due Date</label>
                        <input type="date" name="due_date" class="form-control form-control-sm">
                    </div>
                </div>
                @if($feeRates->count())
                <div class="mb-3">
                    <label class="form-label" style="font-size:0.75rem;">Role / Rate (from schedule)</label>
                    <select name="rate_role" class="form-select form-select-sm" onchange="setRateFromRole(this)">
                        <option value="">— Select role —</option>
                        @foreach($feeRates as $fr)
                        <option value="{{ $fr->hourly_rate }}">${{ number_format($fr->hourly_rate,0) }}/hr — {{ $fr->role_name }}</option>
                        @endforeach
                        <option value="custom">Custom rate</option>
                    </select>
                    <input type="number" name="rate" id="rateCustomInput" class="form-control form-control-sm mt-1 d-none"
                        step="0.01" min="0" placeholder="Custom $/hr">
                </div>
                @else
                <div class="mb-3">
                    <label class="form-label" style="font-size:0.75rem;">Rate ($/hr)</label>
                    <input type="number" name="rate" class="form-control form-control-sm" step="0.01" min="0" placeholder="0.00">
                </div>
                @endif
                @endif

                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-plus-lg me-1"></i> Add Deliverable
                </button>
            </form>
        </div>

        {{-- Billing type info card --}}
        <div class="kore-card" style="font-size:0.78rem; color:#6b7280;">
            <div class="fw-600 mb-2" style="color:#374151;">Work Breakdown Structure</div>
            @if($isFixed)
            <div class="kore-alert kore-alert-info mb-2" style="font-size:0.75rem;">
                <i class="bi bi-info-circle me-1"></i>
                <strong>Fixed Fee</strong> — Each deliverable/milestone has a set dollar amount. No hourly rates.
            </div>
            @elseif($isHourly)
            <div class="kore-alert kore-alert-info mb-2" style="font-size:0.75rem;">
                <i class="bi bi-info-circle me-1"></i>
                <strong>{{ ucwords(str_replace('_',' ',$billingType)) }}</strong> — Track budget hours per deliverable. Rates come from the proposal's fee schedule.
            </div>
            @endif
            <p class="mb-1"><strong>Deliverables</strong> are top-level phases or work packages.</p>
            <p class="mb-1"><strong>Milestones</strong> group related tasks within a deliverable.</p>
            <p class="mb-0"><strong>Tasks</strong> are individual work items that can be assigned.</p>
        </div>
    </div>

</div>

{{-- Edit Deliverable Modal --}}
<div class="modal fade" id="editDeliverableModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editDeliverableForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Deliverable</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem;">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editDeliverableName" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem;">Description</label>
                        <textarea name="description" id="editDeliverableDesc" class="form-control form-control-sm" rows="2"></textarea>
                    </div>

                    @if($isFixed)
                    <div class="row g-2 mb-2">
                        <div class="col-7">
                            <label class="form-label" style="font-size:0.75rem;">Fixed Fee ($)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">$</span>
                                <input type="number" name="deliverable_fee" id="editDeliverableFee" class="form-control" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-5">
                            <label class="form-label" style="font-size:0.75rem;">Due Date</label>
                            <input type="date" name="due_date" id="editDeliverableDueDate" class="form-control form-control-sm">
                        </div>
                    </div>
                    @else
                    <div class="row g-2 mb-2">
                        <div class="col-4">
                            <label class="form-label" style="font-size:0.75rem;">Budget Hours</label>
                            <input type="number" name="budget_hours" id="editDeliverableBudgetHours" class="form-control form-control-sm" step="0.25" min="0">
                        </div>
                        <div class="col-4">
                            <label class="form-label" style="font-size:0.75rem;">Rate ($/hr)</label>
                            <input type="number" name="rate" id="editDeliverableRate" class="form-control form-control-sm" step="0.01" min="0">
                        </div>
                        <div class="col-4">
                            <label class="form-label" style="font-size:0.75rem;">Due Date</label>
                            <input type="date" name="due_date" id="editDeliverableDueDate" class="form-control form-control-sm">
                        </div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Milestone Modal --}}
<div class="modal fade" id="editMilestoneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editMilestoneForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Milestone</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem;">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editMilestoneName" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem;">Description</label>
                        <textarea name="description" id="editMilestoneDesc" class="form-control form-control-sm" rows="2"></textarea>
                    </div>

                    @if($isFixed)
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label" style="font-size:0.75rem;">Fixed Fee ($)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">$</span>
                                <input type="number" name="deliverable_fee" id="editMilestoneFee" class="form-control" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-size:0.75rem;">Due Date</label>
                            <input type="date" name="due_date" id="editMilestoneDueDate" class="form-control form-control-sm">
                        </div>
                    </div>
                    @else
                    <div class="row g-2 mb-2">
                        <div class="col-4">
                            <label class="form-label" style="font-size:0.75rem;">Budget Hours</label>
                            <input type="number" name="budget_hours" id="editMilestoneBudgetHours" class="form-control form-control-sm" step="0.25" min="0">
                        </div>
                        <div class="col-4">
                            <label class="form-label" style="font-size:0.75rem;">Rate ($/hr)</label>
                            <input type="number" name="rate" id="editMilestoneRate" class="form-control form-control-sm" step="0.01" min="0">
                        </div>
                        <div class="col-4">
                            <label class="form-label" style="font-size:0.75rem;">Due Date</label>
                            <input type="date" name="due_date" id="editMilestoneDueDate" class="form-control form-control-sm">
                        </div>
                    </div>
                    @endif

                    <div class="mb-0">
                        <label class="form-label" style="font-size:0.75rem;">Billing Status</label>
                        <select name="billing_status" id="editMilestoneBillingStatus" class="form-select form-select-sm">
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

{{-- Edit Task Modal --}}
<div class="modal fade" id="editTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editTaskForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem;">Task Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editTaskName" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.78rem;">Description</label>
                        <textarea name="description" id="editTaskDesc" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label" style="font-size:0.78rem;">Start Date</label>
                            <input type="date" name="start_date" id="editTaskStart" class="form-control form-control-sm">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" style="font-size:0.78rem;">Due Date</label>
                            <input type="date" name="end_date" id="editTaskEnd" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-{{ $isFixed ? '12' : '6' }}">
                            <label class="form-label" style="font-size:0.78rem;">Status</label>
                            <select name="status" id="editTaskStatus" class="form-select form-select-sm">
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="complete">Complete</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        @if($isHourly)
                        <div class="col-sm-3">
                            <label class="form-label" style="font-size:0.78rem;">Budget Hours</label>
                            <input type="number" name="budget_hours" id="editTaskBudgetHours" class="form-control form-control-sm" step="0.25" min="0">
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label" style="font-size:0.78rem;">Rate ($/hr)</label>
                            <input type="number" name="rate" id="editTaskRate" class="form-control form-control-sm" step="0.01" min="0">
                        </div>
                        @endif
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

@push('scripts')
<script>
const PROJECT_ID  = {{ $project->id }};
const IS_FIXED    = {{ $isFixed ? 'true' : 'false' }};
const IS_HOURLY   = {{ $isHourly ? 'true' : 'false' }};

function setRateFromRole(sel) {
    const custom = document.getElementById('rateCustomInput');
    if (!custom) return;
    if (sel.value === 'custom') {
        custom.classList.remove('d-none');
        custom.focus();
    } else {
        custom.classList.add('d-none');
        custom.value = sel.value;
    }
}

function openEditDeliverable(id, name, desc, budgetHours, rate, fee, dueDate) {
    document.getElementById('editDeliverableForm').action = `/projects/${PROJECT_ID}/deliverables/${id}`;
    document.getElementById('editDeliverableName').value = name;
    document.getElementById('editDeliverableDesc').value = desc;
    if (IS_FIXED) {
        document.getElementById('editDeliverableFee').value     = fee || '';
    } else {
        document.getElementById('editDeliverableBudgetHours').value = budgetHours || '';
        document.getElementById('editDeliverableRate').value        = rate || '';
    }
    document.getElementById('editDeliverableDueDate').value = dueDate || '';
    new bootstrap.Modal(document.getElementById('editDeliverableModal')).show();
}

function openEditMilestone(id, name, desc, budgetHours, rate, fee, dueDate, billingStatus) {
    document.getElementById('editMilestoneForm').action = `/milestones/${id}`;
    document.getElementById('editMilestoneName').value  = name;
    document.getElementById('editMilestoneDesc').value  = desc;
    if (IS_FIXED) {
        document.getElementById('editMilestoneFee').value = fee || '';
    } else {
        document.getElementById('editMilestoneBudgetHours').value = budgetHours || '';
        document.getElementById('editMilestoneRate').value        = rate || '';
    }
    document.getElementById('editMilestoneDueDate').value       = dueDate || '';
    document.getElementById('editMilestoneBillingStatus').value = billingStatus || 'pending';
    new bootstrap.Modal(document.getElementById('editMilestoneModal')).show();
}

function openEditTask(id, name, desc, start, end, status, budgetHours, rate) {
    document.getElementById('editTaskForm').action = `/tasks/${id}`;
    document.getElementById('editTaskName').value   = name;
    document.getElementById('editTaskDesc').value   = desc;
    document.getElementById('editTaskStart').value  = start;
    document.getElementById('editTaskEnd').value    = end;
    document.getElementById('editTaskStatus').value = status;
    if (IS_HOURLY) {
        document.getElementById('editTaskBudgetHours').value = budgetHours || '';
        document.getElementById('editTaskRate').value        = rate || '';
    }
    new bootstrap.Modal(document.getElementById('editTaskModal')).show();
}
</script>
@endpush

@endsection
