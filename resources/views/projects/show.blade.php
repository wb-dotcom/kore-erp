@extends('layouts.app')

@section('content')

@php
    $ref = $project->year.'-'.str_pad($project->project_number, 3, '0', STR_PAD_LEFT);
@endphp

{{-- Header --}}
<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h4 class="mb-0 fw-700" style="font-size:1rem;">{{ $project->title }}</h4>
            <div style="font-size:0.75rem; color:#6b7280;">
                Project {{ $ref }}
                @if($project->company)
                    · <a href="{{ route('companies.show', $project->company) }}"
                        class="text-decoration-none" style="color:#4c8bf5;">{{ $project->company->name }}</a>
                @endif
            </div>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('projects.deliverables', $project) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-list-task me-1"></i> Deliverables
        </a>
        <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

<div class="row g-4">

    {{-- Left: Details --}}
    <div class="col-lg-8">

        {{-- Summary Card --}}
        <div class="kore-card mb-4">
            <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">
                Project Info
            </div>
            <div class="row g-3">
                <div class="col-sm-4">
                    <div style="font-size:0.72rem; color:#9ca3af; font-weight:600; text-transform:uppercase;">Status</div>
                    @php
                        $cls = match($project->status?->name) {
                            'Active'    => 'active',
                            'Complete'  => 'approved',
                            'On Hold'   => 'draft',
                            'Cancelled' => 'rejected',
                            default     => 'draft',
                        };
                    @endphp
                    <span class="badge badge-{{ $cls }} mt-1">{{ $project->status?->name ?? '—' }}</span>
                </div>
                <div class="col-sm-4">
                    <div style="font-size:0.72rem; color:#9ca3af; font-weight:600; text-transform:uppercase;">Project Type</div>
                    <div style="font-size:0.85rem; margin-top:2px;">{{ $project->projectType?->name ?? '—' }}</div>
                </div>
                <div class="col-sm-4">
                    <div style="font-size:0.72rem; color:#9ca3af; font-weight:600; text-transform:uppercase;">Budget</div>
                    <div style="font-size:0.85rem; margin-top:2px; font-weight:600;">
                        {{ $project->total_budget ? '$'.number_format($project->total_budget, 2) : '—' }}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div style="font-size:0.72rem; color:#9ca3af; font-weight:600; text-transform:uppercase;">Project Manager</div>
                    <div style="font-size:0.85rem; margin-top:2px;">{{ $project->projectManager?->full_name ?? '—' }}</div>
                </div>
                <div class="col-sm-4">
                    <div style="font-size:0.72rem; color:#9ca3af; font-weight:600; text-transform:uppercase;">Start Date</div>
                    <div style="font-size:0.85rem; margin-top:2px;">{{ $project->start_date?->format('M d, Y') ?? '—' }}</div>
                </div>
                <div class="col-sm-4">
                    <div style="font-size:0.72rem; color:#9ca3af; font-weight:600; text-transform:uppercase;">End Date</div>
                    <div style="font-size:0.85rem; margin-top:2px;">{{ $project->end_date?->format('M d, Y') ?? '—' }}</div>
                </div>
                @if($project->proposal)
                <div class="col-12">
                    <div style="font-size:0.72rem; color:#9ca3af; font-weight:600; text-transform:uppercase;">Linked Proposal</div>
                    <div style="font-size:0.85rem; margin-top:2px;">
                        <a href="{{ route('proposals.show', $project->proposal) }}" class="text-decoration-none" style="color:#4c8bf5;">
                            {{ $project->proposal->ref }} — {{ $project->proposal->title }}
                        </a>
                        <span class="badge bg-success ms-1" style="font-size:0.65rem;">Billable</span>
                        @if($project->proposal->billing_type)
                        <span class="badge bg-light text-dark border ms-1" style="font-size:0.65rem;">
                            {{ ucwords(str_replace('_', ' ', $project->proposal->billing_type)) }}
                        </span>
                        @endif
                    </div>
                </div>
                @else
                <div class="col-12">
                    <div style="font-size:0.72rem; color:#9ca3af; font-weight:600; text-transform:uppercase;">Billing</div>
                    <div style="font-size:0.85rem; margin-top:2px;">
                        <span class="badge bg-secondary">Non-Billable</span>
                        <span style="font-size:0.75rem; color:#6b7280; margin-left:6px;">No proposal linked</span>
                    </div>
                </div>
                @endif
                @if($project->notes)
                <div class="col-12">
                    <div style="font-size:0.72rem; color:#9ca3af; font-weight:600; text-transform:uppercase;">Notes</div>
                    <div style="font-size:0.85rem; margin-top:4px; white-space:pre-wrap;">{{ $project->notes }}</div>
                </div>
                @endif
            </div>
        </div>

        {{-- Tabs: Deliverables + Communications --}}
        <ul class="nav nav-tabs mb-0" id="projectTabs" role="tablist" style="border-bottom:none;">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="deliverables-tab" data-bs-toggle="tab"
                    data-bs-target="#deliverables-pane" type="button" role="tab"
                    style="font-size:0.8rem;">
                    <i class="bi bi-list-task me-1"></i> Deliverables
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="comms-tab" data-bs-toggle="tab"
                    data-bs-target="#comms-pane" type="button" role="tab"
                    style="font-size:0.8rem;">
                    <i class="bi bi-envelope me-1"></i> Communications
                    @if($communications->count() > 0)
                    <span class="badge bg-primary ms-1" style="font-size:0.65rem;">{{ $communications->count() }}</span>
                    @endif
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="notes-tab" data-bs-toggle="tab"
                    data-bs-target="#notes-pane" type="button" role="tab"
                    style="font-size:0.8rem;">
                    <i class="bi bi-journal-text me-1"></i> Notes &amp; Todos
                    @php $openTodos = $project->projectNotes->where('is_todo', true)->where('is_done', false)->count(); @endphp
                    @if($openTodos > 0)
                    <span class="badge bg-warning text-dark ms-1" style="font-size:0.65rem;">{{ $openTodos }}</span>
                    @endif
                </button>
            </li>
        </ul>

        <div class="tab-content">

        {{-- Deliverables Tab --}}
        <div class="tab-pane fade show active" id="deliverables-pane" role="tabpanel">
        <div class="kore-card" style="border-top-left-radius:0; border-top-right-radius:0;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="fw-600" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">
                    Deliverables & Tasks
                </div>
                <a href="{{ route('projects.deliverables', $project) }}" class="btn btn-sm btn-outline-primary" style="font-size:0.75rem;">
                    <i class="bi bi-pencil-square me-1"></i> Manage
                </a>
            </div>

            @forelse($project->deliverables as $deliverable)
            <div class="mb-3 border rounded" style="overflow:hidden;">
                <div class="d-flex align-items-center gap-2 px-3 py-2" style="background:#f8fafc; border-bottom:1px solid #e5e7eb;">
                    <i class="bi bi-folder2 text-primary"></i>
                    <span class="fw-600" style="font-size:0.85rem; flex:1;">{{ $deliverable->name }}</span>
                    @if($deliverable->budget_hours > 0)
                    <span style="font-size:0.72rem; color:#6b7280;">
                        <i class="bi bi-clock me-1"></i>{{ number_format($deliverable->budget_hours, 1) }}h
                    </span>
                    @endif
                    @if($deliverable->deliverable_fee)
                    <span style="font-size:0.72rem; color:#059669; font-weight:600;">
                        ${{ number_format($deliverable->deliverable_fee, 0) }}
                    </span>
                    @endif
                    @if($deliverable->due_date)
                    <span style="font-size:0.72rem; color:#6b7280;">
                        <i class="bi bi-calendar2 me-1"></i>{{ $deliverable->due_date->format('M d') }}
                    </span>
                    @endif
                </div>
                @foreach($deliverable->milestones as $milestone)
                @php
                    $bsColors = ['pending'=>'secondary','ready_to_bill'=>'warning','invoiced'=>'primary','paid'=>'success'];
                @endphp
                <div class="px-3 py-2 border-bottom" style="background:#fff;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-flag" style="color:#f59e0b; font-size:0.8rem;"></i>
                        <span style="font-size:0.8rem; color:#374151; font-weight:500; flex:1;">{{ $milestone->name }}</span>
                        @if($milestone->budget_hours > 0)
                        <span style="font-size:0.7rem; color:#6b7280;"><i class="bi bi-clock me-1"></i>{{ number_format($milestone->budget_hours, 1) }}h</span>
                        @endif
                        @if($milestone->deliverable_fee)
                        <span style="font-size:0.7rem; color:#059669; font-weight:600;">${{ number_format($milestone->deliverable_fee, 0) }}</span>
                        @endif
                        @if($milestone->due_date)
                        <span style="font-size:0.7rem; color:#6b7280;"><i class="bi bi-calendar2 me-1"></i>{{ $milestone->due_date->format('M d') }}</span>
                        @endif
                        @if($milestone->billing_status && $milestone->billing_status !== 'pending')
                        <span class="badge bg-{{ $bsColors[$milestone->billing_status] ?? 'secondary' }}" style="font-size:0.62rem;">
                            {{ ucwords(str_replace('_', ' ', $milestone->billing_status)) }}
                        </span>
                        @endif
                    </div>
                    @foreach($milestone->tasks as $task)
                    <div class="ms-3 d-flex align-items-center gap-2 mt-1">
                        @php
                            $tIcon = match($task->status) {
                                'complete'    => 'bi-check-circle-fill text-success',
                                'in_progress' => 'bi-play-circle-fill text-primary',
                                default       => 'bi-circle text-secondary',
                            };
                        @endphp
                        <i class="bi {{ $tIcon }}" style="font-size:0.72rem;"></i>
                        <span style="font-size:0.75rem; color:#6b7280; flex:1;">{{ $task->name }}</span>
                        @if($task->budget_hours > 0)
                        <span style="font-size:0.68rem; color:#9ca3af;">{{ number_format($task->budget_hours, 1) }}h</span>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endforeach
            </div>
            @empty
            <div class="text-center py-3" style="font-size:0.8rem; color:#9ca3af;">
                No deliverables yet.
                <a href="{{ route('projects.deliverables', $project) }}" class="text-primary">Add one</a>
            </div>
            @endforelse
        </div>
        </div>{{-- /deliverables-pane --}}

        {{-- Communications Tab --}}
        <div class="tab-pane fade" id="comms-pane" role="tabpanel">
        <div class="kore-card" style="border-top-left-radius:0; border-top-right-radius:0;">
            @forelse($communications as $comm)
            <div class="border-bottom pb-3 mb-3">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div>
                        <span class="fw-600" style="font-size:0.82rem;">{{ $comm->subject ?? '(No Subject)' }}</span>
                        <span class="badge {{ $comm->isMatched() ? 'bg-success' : 'bg-secondary' }} ms-2" style="font-size:0.65rem;">
                            {{ $comm->processing_status }}
                        </span>
                    </div>
                    <div style="font-size:0.72rem; color:#9ca3af;">
                        {{ $comm->sent_at?->format('M d, Y H:i') ?? $comm->created_at->format('M d, Y H:i') }}
                    </div>
                </div>
                <div style="font-size:0.75rem; color:#6b7280; margin-bottom:6px;">
                    <i class="bi bi-person me-1"></i>{{ $comm->sender_display }}
                </div>
                @if($comm->body_text)
                <div style="font-size:0.78rem; color:#374151; white-space:pre-wrap; max-height:100px; overflow:hidden; position:relative;"
                    class="comm-body-preview">{{ \Illuminate\Support\Str::limit($comm->body_text, 300) }}</div>
                @endif
                @if($comm->documents->count() > 0)
                <div class="mt-2 d-flex flex-wrap gap-2">
                    @foreach($comm->documents as $doc)
                    <a href="{{ route('documents.download', $doc) }}" class="badge bg-light text-dark border text-decoration-none"
                        style="font-size:0.72rem; font-weight:500;" title="{{ $doc->original_filename }}">
                        <i class="bi bi-paperclip me-1"></i>{{ \Illuminate\Support\Str::limit($doc->original_filename, 25) }}
                        <span class="text-muted ms-1">{{ $doc->file_size_human }}</span>
                    </a>
                    @endforeach
                </div>
                @endif
            </div>
            @empty
            <div class="text-center py-4" style="font-size:0.82rem; color:#9ca3af;">
                <i class="bi bi-envelope-x" style="font-size:1.5rem; display:block; margin-bottom:8px;"></i>
                No inbound emails matched to this project yet.
            </div>
            @endforelse
        </div>
        </div>{{-- /comms-pane --}}

        {{-- Notes & Todos Tab --}}
        <div class="tab-pane fade" id="notes-pane" role="tabpanel">
        <div class="kore-card" style="border-top-left-radius:0; border-top-right-radius:0;">

            {{-- Add Note/Todo Form --}}
            <div class="mb-4 p-3 rounded" style="background:#f8fafc; border:1px dashed #e5e7eb;">
                <div class="fw-600 mb-2" style="font-size:0.78rem; color:#374151;">Add Note or Todo</div>
                <form id="add-note-form" onsubmit="submitNote(event)">
                    <div class="mb-2">
                        <textarea id="note-content" class="form-control form-control-sm" rows="2"
                                  placeholder="Type a PM note, decision, or todo item..." required
                                  style="font-size:0.82rem;"></textarea>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="checkbox" id="note-is-todo">
                            <label class="form-check-label" for="note-is-todo" style="font-size:0.75rem;">
                                <i class="bi bi-check-square me-1"></i>Mark as Todo
                            </label>
                        </div>
                        <input type="date" id="note-due-date" class="form-control form-control-sm" style="width:140px; font-size:0.75rem;" placeholder="Due date">
                        <button type="submit" class="btn btn-sm btn-primary ms-auto">
                            <i class="bi bi-plus me-1"></i>Add
                        </button>
                    </div>
                </form>
            </div>

            {{-- Todos Section --}}
            @php $todos = $project->projectNotes->where('is_todo', true); @endphp
            @if($todos->count() > 0)
            <div class="mb-4">
                <div class="fw-600 mb-2" style="font-size:0.78rem; color:#374151; text-transform:uppercase; letter-spacing:.05em;">
                    <i class="bi bi-check-square me-1 text-warning"></i>Todos
                </div>
                <div id="todos-list">
                @foreach($todos as $note)
                <div class="todo-item d-flex align-items-start gap-2 p-2 border rounded mb-2 {{ $note->is_done ? 'opacity-50' : '' }}"
                     id="note-{{ $note->id }}" style="background:#fff;">
                    <div class="form-check mt-1 mb-0">
                        <input class="form-check-input" type="checkbox"
                               {{ $note->is_done ? 'checked' : '' }}
                               onchange="toggleTodo({{ $note->id }}, this)">
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div style="font-size:0.82rem; {{ $note->is_done ? 'text-decoration:line-through; color:#9ca3af;' : '' }}">
                            {{ $note->content }}
                        </div>
                        <div class="d-flex gap-3 mt-1" style="font-size:0.68rem; color:#9ca3af;">
                            <span><i class="bi bi-person me-1"></i>{{ $note->user?->full_name }}</span>
                            @if($note->due_date)
                            <span style="color:{{ $note->isOverdue() ? '#ef4444' : ($note->isDueSoon() ? '#f59e0b' : '#9ca3af') }};">
                                <i class="bi bi-calendar3 me-1"></i>Due {{ $note->due_date->format('M j') }}
                                @if($note->isOverdue()) <span>(overdue)</span> @endif
                            </span>
                            @endif
                        </div>
                    </div>
                    @if($note->user_id === auth()->id() || auth()->user()->isManager())
                    <button class="btn btn-sm" style="padding:0 4px; color:#9ca3af;"
                            onclick="deleteNote({{ $note->id }})">
                        <i class="bi bi-trash" style="font-size:0.7rem;"></i>
                    </button>
                    @endif
                </div>
                @endforeach
                </div>
            </div>
            @endif

            {{-- General Notes Section --}}
            @php $generalNotes = $project->projectNotes->where('is_todo', false); @endphp
            <div>
                <div class="fw-600 mb-2" style="font-size:0.78rem; color:#374151; text-transform:uppercase; letter-spacing:.05em;">
                    <i class="bi bi-journal-text me-1 text-primary"></i>PM Notes
                </div>
                <div id="notes-list">
                @forelse($generalNotes as $note)
                <div class="p-2 border rounded mb-2" id="note-{{ $note->id }}" style="background:#fff;">
                    <div style="font-size:0.82rem; white-space:pre-wrap;">{{ $note->content }}</div>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <div style="font-size:0.68rem; color:#9ca3af;">
                            <i class="bi bi-person me-1"></i>{{ $note->user?->full_name }}
                            &mdash; {{ $note->created_at?->format('M j, Y') }}
                        </div>
                        @if($note->user_id === auth()->id() || auth()->user()->isManager())
                        <button class="btn btn-sm" style="padding:0 4px; color:#9ca3af;"
                                onclick="deleteNote({{ $note->id }})">
                            <i class="bi bi-trash" style="font-size:0.7rem;"></i>
                        </button>
                        @endif
                    </div>
                </div>
                @empty
                <div class="text-center py-3" style="font-size:0.8rem; color:#9ca3af;">
                    No PM notes yet. Add the first one above.
                </div>
                @endforelse
                </div>
            </div>

        </div>
        </div>{{-- /notes-pane --}}

        </div>{{-- /tab-content --}}

<script>
const NOTES_CSRF = document.querySelector('meta[name="csrf-token"]').content;
const PROJECT_ID = {{ $project->id }};

function submitNote(e) {
    e.preventDefault();
    const content = document.getElementById('note-content').value.trim();
    const isTodo  = document.getElementById('note-is-todo').checked;
    const dueDate = document.getElementById('note-due-date').value;
    if (! content) return;

    fetch(`/projects/${PROJECT_ID}/notes`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': NOTES_CSRF },
        body: JSON.stringify({ content, is_todo: isTodo, due_date: dueDate || null }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
    });
}

function toggleTodo(noteId, checkbox) {
    fetch(`/projects/${PROJECT_ID}/notes/${noteId}/toggle`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': NOTES_CSRF },
    })
    .then(r => r.json())
    .then(data => {
        const el = document.getElementById(`note-${noteId}`);
        if (data.is_done) { el.classList.add('opacity-50'); }
        else              { el.classList.remove('opacity-50'); }
    });
}

function deleteNote(noteId) {
    if (! confirm('Delete this note?')) return;
    fetch(`/projects/${PROJECT_ID}/notes/${noteId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': NOTES_CSRF },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) document.getElementById(`note-${noteId}`)?.remove();
    });
}
</script>

    </div>

    {{-- Right: Side panel --}}
    <div class="col-lg-4">
        <div class="kore-card mb-4">
            <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">
                Quick Stats
            </div>
            @php
                $totalTasks    = $project->deliverables->flatMap(fn($d) => $d->milestones)->flatMap(fn($m) => $m->tasks);
                $completeTasks = $totalTasks->where('status', 'complete')->count();
                $pct           = $totalTasks->count() ? round($completeTasks / $totalTasks->count() * 100) : 0;
            @endphp
            <div class="d-flex justify-content-between mb-1" style="font-size:0.78rem;">
                <span>Task Completion</span>
                <span class="fw-600">{{ $pct }}%</span>
            </div>
            <div class="progress mb-3" style="height:6px;">
                <div class="progress-bar bg-primary" style="width:{{ $pct }}%"></div>
            </div>
            <div class="row g-2 text-center">
                <div class="col-4">
                    <div style="font-size:1.2rem; font-weight:700; color:#374151;">{{ $project->deliverables->count() }}</div>
                    <div style="font-size:0.7rem; color:#9ca3af;">Deliverables</div>
                </div>
                <div class="col-4">
                    <div style="font-size:1.2rem; font-weight:700; color:#374151;">{{ $totalTasks->count() }}</div>
                    <div style="font-size:0.7rem; color:#9ca3af;">Tasks</div>
                </div>
                <div class="col-4">
                    <div style="font-size:1.2rem; font-weight:700; color:#22c55e;">{{ $completeTasks }}</div>
                    <div style="font-size:0.7rem; color:#9ca3af;">Done</div>
                </div>
            </div>
        </div>

        <div class="kore-card">
            <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">
                Actions
            </div>
            <div class="d-grid gap-2">
                <a href="{{ route('projects.deliverables', $project) }}" class="btn btn-sm btn-outline-secondary text-start">
                    <i class="bi bi-list-task me-2"></i> Manage Deliverables
                </a>
                <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-outline-secondary text-start">
                    <i class="bi bi-pencil me-2"></i> Edit Project
                </a>
                <form action="{{ route('projects.destroy', $project) }}" method="POST"
                    onsubmit="return confirm('Delete this project? This cannot be undone.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger text-start w-100">
                        <i class="bi bi-trash me-2"></i> Delete Project
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

@endsection
