@php
    $statusIcons = [
        'complete'    => 'bi-check-circle-fill text-success',
        'in_progress' => 'bi-play-circle-fill text-primary',
        'cancelled'   => 'bi-x-circle-fill text-secondary',
        'pending'     => 'bi-circle text-secondary',
    ];
    $icon      = $statusIcons[$task->status] ?? 'bi-circle text-secondary';
    $overdue   = $task->status !== 'complete' && $task->status !== 'cancelled'
                 && $task->end_date && $task->end_date->isPast();
    $depsJson  = $task->dependencies->toArray();
    $assignJson = $task->assignments->map(fn ($a) => [
        'id'           => $a->id,
        'user_id'      => $a->user_id,
        'user_name'    => ($a->user->first_name ?? '') . ' ' . ($a->user->last_name ?? ''),
        'initials'     => strtoupper(substr($a->user->first_name ?? '', 0, 1) . substr($a->user->last_name ?? '', 0, 1)),
        'role'         => $a->role,
        'budget_hours' => (float) $a->budget_hours,
    ])->values()->toArray();
@endphp
<div class="task-row d-flex align-items-center gap-2" id="task-{{ $task->id }}" data-id="{{ $task->id }}">

    {{-- Drag handle --}}
    <i class="bi bi-grip-vertical drag-handle" style="font-size:0.78rem; flex-shrink:0;"></i>

    {{-- Status icon --}}
    <i class="bi {{ $icon }}" style="font-size:0.8rem; flex-shrink:0;"></i>

    {{-- Name (click to edit) --}}
    <span style="font-size:0.8rem; flex-grow:1; cursor:pointer;"
        class="{{ $task->status === 'cancelled' ? 'text-decoration-line-through text-muted' : '' }}"
        onclick="openEditTask(
            {{ $task->id }},
            @json($task->name),
            @json($task->description ?? ''),
            '{{ $task->start_date?->format('Y-m-d') ?? '' }}',
            '{{ $task->end_date?->format('Y-m-d') ?? '' }}',
            '{{ $task->status }}',
            {{ $task->budget_hours ?? 0 }},
            {{ $task->rate ?? 0 }},
            @json($depsJson)
        )" title="Click to edit">
        {{ $task->name }}
    </span>

    {{-- Date range --}}
    @if($task->start_date || $task->end_date)
    <span style="font-size:0.7rem; white-space:nowrap;" class="{{ $overdue ? 'text-danger fw-600' : 'text-muted' }}">
        @if($overdue)<i class="bi bi-exclamation-triangle me-1"></i>@endif
        {{ $task->start_date?->format('M j') ?? '?' }} → {{ $task->end_date?->format('M j') ?? '?' }}
    </span>
    @endif

    {{-- Hours badge --}}
    @if($isHourly && $task->budget_hours > 0)
    <span style="font-size:0.68rem; color:#9ca3af; white-space:nowrap;">
        <i class="bi bi-hourglass me-1"></i>{{ number_format($task->budget_hours, 1) }}h
    </span>
    @endif

    {{-- Assignee avatars --}}
    @if($task->assignments->count())
    <div class="d-flex align-items-center" style="flex-shrink:0; cursor:pointer;"
        onclick="openAssignModal({{ $task->id }}, @json($task->name), {{ $task->budget_hours ?? 0 }}, @json($assignJson))"
        title="Manage resources">
        @foreach($task->assignments->take(3) as $a)
        @php $initials = strtoupper(substr($a->user->first_name ?? '', 0, 1) . substr($a->user->last_name ?? '', 0, 1)); @endphp
        <div class="assignee-avatar" title="{{ $a->user->first_name }} {{ $a->user->last_name }} ({{ number_format($a->budget_hours, 1) }}h)">
            {{ $initials }}
        </div>
        @endforeach
        @if($task->assignments->count() > 3)
        <div class="assignee-avatar" style="background:#6b7280;">+{{ $task->assignments->count() - 3 }}</div>
        @endif
    </div>
    @else
    <button class="btn btn-link btn-sm py-0 px-1 text-muted"
        onclick="openAssignModal({{ $task->id }}, @json($task->name), {{ $task->budget_hours ?? 0 }}, @json($assignJson))"
        title="Assign resources" style="font-size:0.72rem;">
        <i class="bi bi-person-plus"></i>
    </button>
    @endif

    {{-- Quick status --}}
    <form action="{{ route('projects.tasks.update', $task) }}" method="POST" class="d-flex align-items-center" style="flex-shrink:0;">
        @csrf @method('PUT')
        <input type="hidden" name="name" value="{{ $task->name }}">
        <input type="hidden" name="status" class="status-val" value="{{ $task->status }}">
        <select class="form-select form-select-sm py-0"
            style="font-size:0.68rem; width:auto; height:22px; padding:0 20px 0 4px;"
            onchange="this.previousElementSibling.value = this.value; this.form.submit()">
            <option value="pending"     {{ $task->status==='pending'     ? 'selected':'' }}>Pending</option>
            <option value="in_progress" {{ $task->status==='in_progress' ? 'selected':'' }}>In Progress</option>
            <option value="complete"    {{ $task->status==='complete'    ? 'selected':'' }}>Complete</option>
            <option value="cancelled"   {{ $task->status==='cancelled'   ? 'selected':'' }}>Cancelled</option>
        </select>
    </form>

    {{-- Delete --}}
    <form action="{{ route('projects.tasks.destroy', $task) }}" method="POST"
        onsubmit="return confirm('Delete task?')" class="d-inline" style="flex-shrink:0;">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-link btn-sm py-0 px-1 text-danger" style="font-size:0.72rem;" title="Delete">
            <i class="bi bi-trash"></i>
        </button>
    </form>

</div>
