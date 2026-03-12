@php
    $statusClass = match($task->status) {
        'complete'    => 'complete',
        'in_progress' => 'in_progress',
        'cancelled'   => 'cancelled',
        default       => 'pending',
    };
    $overdue    = $task->status !== 'complete' && $task->status !== 'cancelled'
                  && $task->end_date && $task->end_date->isPast();
    $assignJson = $task->assignments->map(fn ($a) => [
        'id'           => $a->id,
        'user_id'      => $a->user_id,
        'user_name'    => trim(($a->user->first_name ?? '') . ' ' . ($a->user->last_name ?? '')),
        'initials'     => strtoupper(substr($a->user->first_name ?? '', 0, 1) . substr($a->user->last_name ?? '', 0, 1)),
        'role'         => $a->role,
        'budget_hours' => (float) $a->budget_hours,
    ])->values()->toArray();
    $depsJson = $task->dependencies->map(fn ($d) => [
        'depends_on_id' => $d->depends_on_id,
        'lag_days'      => $d->lag_days,
        'depends_on'    => ['name' => $d->dependsOn?->name ?? 'Task #'.$d->depends_on_id],
    ])->values()->toArray();
@endphp

<div class="task-row" id="task-{{ $task->id }}" data-id="{{ $task->id }}">

    {{-- Drag handle --}}
    <i class="bi bi-grip-vertical drag-handle" style="flex-shrink:0;"></i>

    {{-- Status dot --}}
    <span class="status-dot {{ $statusClass }}" style="flex-shrink:0;"></span>

    {{-- Name — click edit button to open modal --}}
    <span class="flex-grow-1" style="font-size:0.8rem; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
        {{ $task->status === 'cancelled' ? 'text-decoration:line-through; color:#9ca3af;' : '' }}">
        {{ $task->name }}
        @if($task->dependencies->isNotEmpty())
        <span class="dep-badge ms-1" title="{{ $task->dependencies->count() }} predecessor(s)">
            <i class="bi bi-arrow-left-short"></i>{{ $task->dependencies->count() }}
        </span>
        @endif
    </span>

    {{-- Date range --}}
    @if($task->start_date || $task->end_date)
    <span style="font-size:0.7rem; white-space:nowrap; flex-shrink:0;"
        class="{{ $overdue ? 'text-danger fw-600' : 'text-muted' }}">
        @if($overdue)<i class="bi bi-exclamation-triangle"></i>@endif
        {{ $task->start_date?->format('M j') ?? '?' }} → {{ $task->end_date?->format('M j') ?? '?' }}
    </span>
    @endif

    {{-- Hours --}}
    @if($isHourly && $task->budget_hours > 0)
    <span style="font-size:0.68rem; color:#9ca3af; white-space:nowrap; flex-shrink:0;">
        <i class="bi bi-hourglass"></i> {{ number_format($task->budget_hours, 1) }}h
    </span>
    @endif

    {{-- Assignee avatars --}}
    @if($task->assignments->count())
    <div class="d-flex align-items-center" style="flex-shrink:0;"
        data-action="assign-task"
        data-id="{{ $task->id }}"
        data-name="{{ $task->name }}"
        data-hours="{{ $task->budget_hours ?? 0 }}"
        data-assignments="{{ htmlspecialchars(json_encode($assignJson), ENT_QUOTES) }}"
        title="Manage resources" style="cursor:pointer;">
        @foreach($task->assignments->take(3) as $a)
        @php $ini = strtoupper(substr($a->user->first_name ?? '', 0, 1) . substr($a->user->last_name ?? '', 0, 1)); @endphp
        <div class="av" title="{{ trim(($a->user->first_name ?? '') . ' ' . ($a->user->last_name ?? '')) }} ({{ number_format($a->budget_hours, 1) }}h)">{{ $ini }}</div>
        @endforeach
        @if($task->assignments->count() > 3)
        <div class="av" style="background:#6b7280;">+{{ $task->assignments->count() - 3 }}</div>
        @endif
    </div>
    @else
    <button type="button" class="btn btn-link btn-sm p-0 av av-add" style="flex-shrink:0;"
        data-action="assign-task"
        data-id="{{ $task->id }}"
        data-name="{{ $task->name }}"
        data-hours="{{ $task->budget_hours ?? 0 }}"
        data-assignments="[]"
        title="Assign resources">
        <i class="bi bi-person-plus" style="font-size:0.6rem;"></i>
    </button>
    @endif

    {{-- Quick status dropdown --}}
    <form action="{{ route('projects.tasks.update', $task) }}" method="POST" style="flex-shrink:0;">
        @csrf @method('PUT')
        <input type="hidden" name="name" value="{{ $task->name }}">
        <select name="status" class="form-select"
            style="font-size:0.68rem; height:22px; padding:0 18px 0 4px; border-radius:4px; width:auto;"
            onchange="this.form.submit()">
            <option value="pending"     {{ $task->status==='pending'     ? 'selected':'' }}>Pending</option>
            <option value="in_progress" {{ $task->status==='in_progress' ? 'selected':'' }}>In Progress</option>
            <option value="complete"    {{ $task->status==='complete'    ? 'selected':'' }}>Complete</option>
            <option value="cancelled"   {{ $task->status==='cancelled'   ? 'selected':'' }}>Cancelled</option>
        </select>
    </form>

    {{-- Edit button --}}
    <button type="button" class="btn btn-link btn-sm p-0 text-secondary"
        style="font-size:0.72rem; flex-shrink:0;"
        data-action="edit-task"
        data-id="{{ $task->id }}"
        data-name="{{ $task->name }}"
        data-desc="{{ $task->description ?? '' }}"
        data-start="{{ $task->start_date?->format('Y-m-d') ?? '' }}"
        data-end="{{ $task->end_date?->format('Y-m-d') ?? '' }}"
        data-status="{{ $task->status }}"
        data-hours="{{ $task->budget_hours ?? '' }}"
        data-rate="{{ $task->rate ?? '' }}"
        data-deps="{{ htmlspecialchars(json_encode($depsJson), ENT_QUOTES) }}"
        title="Edit task">
        <i class="bi bi-pencil"></i>
    </button>

    {{-- Delete --}}
    <form action="{{ route('projects.tasks.destroy', $task) }}" method="POST"
        onsubmit="return confirm('Delete task?')" style="flex-shrink:0;">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-link btn-sm p-0 text-danger" style="font-size:0.72rem;" title="Delete">
            <i class="bi bi-trash"></i>
        </button>
    </form>

</div>
