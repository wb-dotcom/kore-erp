@extends('layouts.app')

@push('styles')
<style>
.tpl-deliverable { border:1px solid #e5e7eb; border-radius:8px; margin-bottom:12px; overflow:hidden; }
.tpl-deliverable-header { background:#f9fafb; padding:10px 14px; display:flex; align-items:center; gap:8px; border-bottom:1px solid #e5e7eb; }
.tpl-activity { border-left:3px solid #4c8bf5; background:#fff; margin:8px 14px; border-radius:6px; box-shadow:0 1px 3px rgba(0,0,0,.06); }
.tpl-activity-header { padding:8px 12px; display:flex; align-items:center; gap:8px; border-bottom:1px solid #f3f4f6; }
.tpl-task-row { padding:6px 12px 6px 24px; display:flex; align-items:center; gap:8px; border-bottom:1px solid #f9fafb; font-size:0.8rem; }
.tpl-task-row:last-child { border-bottom:none; }
.add-row { padding:8px 12px; background:#fafafa; }
.badge-role { display:inline-block; padding:1px 6px; border-radius:4px; font-size:0.68rem;
    background:#f3f4f6; color:#6b7280; font-weight:500; }
.badge-hours { display:inline-block; padding:1px 6px; border-radius:4px; font-size:0.68rem;
    background:#ecfdf5; color:#059669; font-weight:600; }
.badge-days { display:inline-block; padding:1px 6px; border-radius:4px; font-size:0.68rem;
    background:#eff6ff; color:#2563eb; font-weight:500; }
</style>
@endpush

@section('content')

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <a href="{{ route('admin.templates') }}" style="font-size:0.78rem; color:#6b7280; text-decoration:none;">
            <i class="bi bi-arrow-left me-1"></i>Templates
        </a>
        <h4 class="mb-0 fw-700 mt-1" style="font-size:1rem;">{{ $activityTemplate->name }}</h4>
        <div style="font-size:0.75rem; color:#6b7280;">
            @if($activityTemplate->workType) {{ $activityTemplate->workType->name }} · @endif
            {{ number_format($activityTemplate->total_budgeted_hours, 1) }} hrs total
        </div>
    </div>
    <button class="btn btn-sm btn-outline-secondary" data-action="edit-template">
        <i class="bi bi-pencil me-1"></i>Edit Template
    </button>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger py-2 mb-3" style="font-size:0.8rem;">{{ session('error') }}</div>
@endif

<div class="row g-3">

    {{-- Deliverables tree --}}
    <div class="col-lg-8">

        @forelse($activityTemplate->deliverables as $deliverable)
        <div class="tpl-deliverable">
            <div class="tpl-deliverable-header">
                <i class="bi bi-folder2 text-primary" style="font-size:0.85rem;"></i>
                <span class="fw-600" style="font-size:0.85rem; flex:1;">{{ $deliverable->name }}</span>
                @if($deliverable->description)
                <span style="font-size:0.75rem; color:#9ca3af; flex:2;">{{ $deliverable->description }}</span>
                @endif
                <button class="btn btn-xs btn-outline-secondary ms-auto"
                    style="font-size:0.7rem; padding:1px 7px;"
                    data-action="edit-deliverable"
                    data-id="{{ $deliverable->id }}"
                    data-name="{{ $deliverable->name }}"
                    data-description="{{ $deliverable->description }}">Edit</button>
                <form method="POST" action="{{ route('admin.templates.deliverables.destroy', $deliverable) }}"
                    onsubmit="return confirm('Remove this deliverable and all its contents?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-xs btn-outline-danger" style="font-size:0.7rem; padding:1px 7px;">
                        <i class="bi bi-trash3"></i>
                    </button>
                </form>
            </div>

            {{-- Activities --}}
            @foreach($deliverable->activities as $activity)
            <div class="tpl-activity">
                <div class="tpl-activity-header">
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
                    <button class="btn btn-xs btn-outline-secondary"
                        style="font-size:0.68rem; padding:1px 6px;"
                        data-action="edit-activity"
                        data-id="{{ $activity->id }}"
                        data-name="{{ $activity->name }}"
                        data-description="{{ $activity->description }}"
                        data-relative-start-day="{{ $activity->relative_start_day }}"
                        data-relative-end-day="{{ $activity->relative_end_day }}"
                        data-assigned-role="{{ $activity->assigned_role }}"
                        data-budgeted-hours="{{ $activity->budgeted_hours }}">Edit</button>
                    <form method="POST" action="{{ route('admin.templates.activities.destroy', $activity) }}"
                        onsubmit="return confirm('Remove this activity and its tasks?')" class="mb-0">
                        @csrf @method('DELETE')
                        <button class="btn btn-xs btn-outline-danger" style="font-size:0.68rem; padding:1px 6px;">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </form>
                </div>

                {{-- Tasks --}}
                @foreach($activity->tasks as $task)
                <div class="tpl-task-row">
                    <i class="bi bi-check2-square" style="font-size:0.75rem; color:#9ca3af;"></i>
                    <span style="flex:1;">{{ $task->name }}</span>
                    @if($task->assigned_role)<span class="badge-role">{{ $task->assigned_role }}</span>@endif
                    @if($task->estimated_hours)<span class="badge-hours">{{ number_format($task->estimated_hours, 1) }}h</span>@endif
                    @if($task->relative_due_day !== null)<span class="badge-days">Due day {{ $task->relative_due_day }}</span>@endif
                    <button class="btn btn-xs btn-outline-secondary"
                        style="font-size:0.65rem; padding:1px 5px;"
                        data-action="edit-task"
                        data-id="{{ $task->id }}"
                        data-name="{{ $task->name }}"
                        data-description="{{ $task->description }}"
                        data-relative-due-day="{{ $task->relative_due_day }}"
                        data-assigned-role="{{ $task->assigned_role }}"
                        data-estimated-hours="{{ $task->estimated_hours }}">Edit</button>
                    <form method="POST" action="{{ route('admin.templates.tasks.destroy', $task) }}"
                        onsubmit="return confirm('Remove task?')" class="mb-0">
                        @csrf @method('DELETE')
                        <button class="btn btn-xs btn-outline-danger" style="font-size:0.65rem; padding:1px 5px;">
                            <i class="bi bi-x"></i>
                        </button>
                    </form>
                </div>
                @endforeach

                {{-- Add Task inline --}}
                <div class="add-row">
                    <form method="POST" action="{{ route('admin.templates.tasks.store', $activity) }}"
                        class="row g-1 align-items-center">
                        @csrf
                        <div class="col-sm-4">
                            <input type="text" name="name" class="form-control form-control-sm"
                                placeholder="+ Task name" required style="font-size:0.78rem;">
                        </div>
                        <div class="col-sm-2">
                            <input type="number" name="estimated_hours" class="form-control form-control-sm"
                                step="0.25" min="0" placeholder="Hrs" style="font-size:0.78rem;">
                        </div>
                        <div class="col-sm-2">
                            <input type="number" name="relative_due_day" class="form-control form-control-sm"
                                min="0" placeholder="Due day" style="font-size:0.78rem;">
                        </div>
                        <div class="col-sm-2">
                            <input type="text" name="assigned_role" class="form-control form-control-sm"
                                placeholder="Role" style="font-size:0.78rem;">
                        </div>
                        <div class="col-sm-2">
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100"
                                style="font-size:0.75rem; padding:3px 8px;">Add Task</button>
                        </div>
                    </form>
                </div>
            </div>
            @endforeach

            {{-- Add Activity --}}
            <div class="p-3" style="background:#f9fafb; border-top:1px solid #f3f4f6;">
                <form method="POST" action="{{ route('admin.templates.activities.store', $deliverable) }}"
                    class="row g-1 align-items-center">
                    @csrf
                    <div class="col-sm-3">
                        <input type="text" name="name" class="form-control form-control-sm"
                            placeholder="+ Milestone / Activity name" required style="font-size:0.78rem;">
                    </div>
                    <div class="col-sm-2">
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
                        <button type="submit" class="btn btn-sm btn-outline-secondary w-100"
                            style="font-size:0.75rem; padding:3px 8px;">Add Milestone</button>
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

    {{-- Sidebar --}}
    <div class="col-lg-4">

        {{-- Add Deliverable --}}
        <div class="kore-card mb-3">
            <div class="fw-600 mb-2" style="font-size:0.82rem; color:#374151;">Add Deliverable</div>
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
                <button type="submit" class="btn btn-primary btn-sm w-100">Add Deliverable</button>
            </form>
        </div>

        {{-- Summary --}}
        <div class="kore-card">
            <div class="fw-600 mb-2" style="font-size:0.82rem; color:#374151;">Summary</div>
            @php
                $delivCount   = $activityTemplate->deliverables->count();
                $actCount     = $activityTemplate->deliverables->sum(fn($d) => $d->activities->count());
                $taskCount    = $activityTemplate->deliverables->sum(fn($d) => $d->activities->sum(fn($a) => $a->tasks->count()));
            @endphp
            <div class="d-flex gap-3 text-center">
                <div style="flex:1;">
                    <div class="fw-700" style="font-size:1.1rem; color:#4c8bf5;">{{ $delivCount }}</div>
                    <div style="font-size:0.72rem; color:#9ca3af;">Deliverables</div>
                </div>
                <div style="flex:1;">
                    <div class="fw-700" style="font-size:1.1rem; color:#f59e0b;">{{ $actCount }}</div>
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

    </div>
</div>

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
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Activity Modal --}}
<div class="modal fade" id="editActivityModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="editActivityForm" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header py-2">
                <h6 class="modal-title fw-600">Edit Milestone / Activity</h6>
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
                        <label class="form-label" style="font-size:0.78rem; font-weight:600;">Relative Start Day</label>
                        <input type="number" name="relative_start_day" id="editActStartDay"
                            class="form-control form-control-sm" min="0" placeholder="e.g. 0">
                    </div>
                    <div class="col-6">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600;">Relative End Day</label>
                        <input type="number" name="relative_end_day" id="editActEndDay"
                            class="form-control form-control-sm" min="0" placeholder="e.g. 14">
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600;">Assigned Role</label>
                        <input type="text" name="assigned_role" id="editActRole"
                            class="form-control form-control-sm" placeholder="e.g. Engineer">
                    </div>
                    <div class="col-6">
                        <label class="form-label" style="font-size:0.78rem; font-weight:600;">Budgeted Hours</label>
                        <input type="number" name="budgeted_hours" id="editActHours"
                            class="form-control form-control-sm" step="0.25" min="0" placeholder="0">
                    </div>
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
                        <label class="form-label" style="font-size:0.78rem; font-weight:600;">Relative Due Day</label>
                        <input type="number" name="relative_due_day" id="editTaskDueDay"
                            class="form-control form-control-sm" min="0" placeholder="e.g. 10">
                    </div>
                    <div class="col-6">
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
document.addEventListener('click', function(e) {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;

    const action = btn.dataset.action;

    if (action === 'edit-template') {
        document.getElementById('editTplName').value = @json($activityTemplate->name);
        document.getElementById('editTplDesc').value  = @json($activityTemplate->description ?? '');
        new bootstrap.Modal(document.getElementById('editTemplateModal')).show();
    }

    if (action === 'edit-deliverable') {
        document.getElementById('editDelName').value = btn.dataset.name;
        document.getElementById('editDelDesc').value = btn.dataset.description ?? '';
        document.getElementById('editDeliverableForm').action =
            '/admin/templates/deliverables/' + btn.dataset.id;
        new bootstrap.Modal(document.getElementById('editDeliverableModal')).show();
    }

    if (action === 'edit-activity') {
        document.getElementById('editActName').value     = btn.dataset.name;
        document.getElementById('editActDesc').value     = btn.dataset.description ?? '';
        document.getElementById('editActStartDay').value = btn.dataset.relativeStartDay ?? '';
        document.getElementById('editActEndDay').value   = btn.dataset.relativeEndDay ?? '';
        document.getElementById('editActRole').value     = btn.dataset.assignedRole ?? '';
        document.getElementById('editActHours').value    = btn.dataset.budgetedHours ?? '';
        document.getElementById('editActivityForm').action =
            '/admin/templates/activities/' + btn.dataset.id;
        new bootstrap.Modal(document.getElementById('editActivityModal')).show();
    }

    if (action === 'edit-task') {
        document.getElementById('editTaskName').value    = btn.dataset.name;
        document.getElementById('editTaskDesc').value    = btn.dataset.description ?? '';
        document.getElementById('editTaskDueDay').value  = btn.dataset.relativeDueDay ?? '';
        document.getElementById('editTaskHours').value   = btn.dataset.estimatedHours ?? '';
        document.getElementById('editTaskRole').value    = btn.dataset.assignedRole ?? '';
        document.getElementById('editTaskForm').action =
            '/admin/templates/tasks/' + btn.dataset.id;
        new bootstrap.Modal(document.getElementById('editTaskModal')).show();
    }
});
</script>
@endpush

@endsection
