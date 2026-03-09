@extends('layouts.app')

@section('content')

@php $ref = $project->year.'-'.str_pad($project->project_number, 3, '0', STR_PAD_LEFT); @endphp

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">Deliverables — {{ $project->title }}</h4>
        <div style="font-size:0.75rem; color:#6b7280;">Project {{ $ref }}</div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

<div class="row g-4">

    {{-- Work Breakdown --}}
    <div class="col-lg-8">
        @forelse($project->deliverables as $deliverable)
        <div class="kore-card mb-3">

            {{-- Deliverable Header --}}
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="fw-600" style="font-size:0.9rem;">
                        <i class="bi bi-folder2-open me-1" style="color:#4c8bf5;"></i>
                        {{ $deliverable->name }}
                    </div>
                    @if($deliverable->description)
                    <div style="font-size:0.78rem; color:#6b7280; margin-top:2px;">{{ $deliverable->description }}</div>
                    @endif
                </div>
            </div>

            {{-- Milestones --}}
            @foreach($deliverable->milestones as $milestone)
            <div class="ms-2 mb-3 pb-2" style="border-left:2px solid #e5e7eb; padding-left:14px;">
                <div class="fw-500 mb-2" style="font-size:0.82rem; color:#374151;">
                    <i class="bi bi-flag me-1" style="color:#f59e0b;"></i>{{ $milestone->name }}
                </div>

                {{-- Tasks --}}
                @foreach($milestone->tasks as $task)
                <div class="d-flex align-items-center gap-2 mb-1 ms-2">
                    @php
                        $icons = ['complete'=>'bi-check-circle-fill text-success','in_progress'=>'bi-play-circle-fill text-primary','pending'=>'bi-circle text-secondary'];
                        $icon  = $icons[$task->status] ?? 'bi-circle text-secondary';
                    @endphp
                    <i class="bi {{ $icon }}" style="font-size:0.8rem;"></i>
                    <span style="font-size:0.8rem;">{{ $task->name }}</span>
                    @if($task->end_date)
                    <span style="font-size:0.72rem; color:#9ca3af;">· Due {{ $task->end_date->format('M d') }}</span>
                    @endif
                    {{-- Quick status update --}}
                    <form action="{{ route('projects.tasks.update', $task) }}" method="POST" class="ms-auto d-flex align-items-center gap-1">
                        @csrf @method('PUT')
                        <input type="hidden" name="name" value="{{ $task->name }}">
                        <select name="status" class="form-select form-select-sm py-0"
                            style="font-size:0.7rem; width:auto; height:24px;"
                            onchange="this.form.submit()">
                            <option value="pending"     {{ $task->status=='pending'     ? 'selected':'' }}>Pending</option>
                            <option value="in_progress" {{ $task->status=='in_progress' ? 'selected':'' }}>In Progress</option>
                            <option value="complete"    {{ $task->status=='complete'    ? 'selected':'' }}>Complete</option>
                        </select>
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
                        <div class="d-flex gap-2">
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="Task name" required>
                            <input type="date" name="end_date" class="form-control form-control-sm" style="max-width:140px;">
                            <button type="submit" class="btn btn-sm btn-primary">Add</button>
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
                    <div class="d-flex gap-2">
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="Milestone name" required>
                        <button type="submit" class="btn btn-sm btn-outline-primary">Add</button>
                    </div>
                </form>
            </details>

        </div>
        @empty
        <div class="kore-card text-center py-5" style="color:#9ca3af;">
            <i class="bi bi-folder2-open fs-2 d-block mb-2"></i>
            No deliverables yet. Add your first deliverable to start the work breakdown.
        </div>
        @endforelse
    </div>

    {{-- Add Deliverable Sidebar --}}
    <div class="col-lg-4">
        <div class="kore-card">
            <div class="fw-600 mb-3" style="font-size:0.85rem;">Add Deliverable</div>
            <form action="{{ route('projects.deliverables.store', $project) }}" method="POST">
                @csrf
                <div class="mb-2">
                    <label class="form-label" style="font-size:0.78rem;">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Phase 1 — Design" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="font-size:0.78rem;">Description</label>
                    <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Optional details..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-plus-lg me-1"></i> Add Deliverable
                </button>
            </form>
        </div>

        <div class="kore-card mt-3" style="font-size:0.78rem; color:#6b7280;">
            <div class="fw-600 mb-2" style="color:#374151;">Work Breakdown Structure</div>
            <p class="mb-1"><strong>Deliverables</strong> are the top-level phases or major work packages.</p>
            <p class="mb-1"><strong>Milestones</strong> group related tasks within a deliverable.</p>
            <p class="mb-0"><strong>Tasks</strong> are the individual work items that can be tracked and assigned.</p>
        </div>
    </div>

</div>

@endsection
