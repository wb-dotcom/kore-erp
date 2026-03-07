@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">Employee Schedule</h4>
        <div style="font-size:0.75rem; color:#6b7280;">Task assignments by team member</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('schedule.project') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-diagram-3 me-1"></i> By Project
        </a>
        <a href="{{ route('schedule.resources') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-bar-chart me-1"></i> Resources
        </a>
    </div>
</div>

@foreach($users as $user)
@php
    $assignments = $user->taskAssignments->filter(fn($a) => $a->task);
    $open        = $assignments->filter(fn($a) => $a->task->status !== 'complete');
@endphp
@if($assignments->count())
<div class="kore-card mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="d-flex align-items-center gap-2">
            <div style="width:32px; height:32px; border-radius:50%; background:#4c8bf5; color:#fff;
                display:flex; align-items:center; justify-content:center; font-size:0.75rem; font-weight:700;">
                {{ strtoupper(substr($user->first_name,0,1).substr($user->last_name,0,1)) }}
            </div>
            <div>
                <div class="fw-600" style="font-size:0.85rem;">{{ $user->full_name }}</div>
                <div style="font-size:0.72rem; color:#9ca3af;">{{ $user->department ?? $user->role?->name }}</div>
            </div>
        </div>
        <div style="font-size:0.75rem; color:#6b7280;">
            <strong>{{ $open->count() }}</strong> open /
            <strong>{{ $assignments->count() }}</strong> total tasks
        </div>
    </div>

    <div class="row g-1">
        @foreach($open->take(8) as $assignment)
        @php
            $task    = $assignment->task;
            $project = $task->milestone?->deliverable?->project;
            $icon    = match($task->status) {
                'in_progress' => 'bi-play-circle-fill text-primary',
                'complete'    => 'bi-check-circle-fill text-success',
                default       => 'bi-circle text-secondary',
            };
        @endphp
        <div class="col-sm-6">
            <div class="d-flex align-items-start gap-2 p-2 rounded" style="background:#f9fafb;">
                <i class="bi {{ $icon }} mt-1" style="font-size:0.75rem; flex-shrink:0;"></i>
                <div style="min-width:0;">
                    <div style="font-size:0.78rem; font-weight:500; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        {{ $task->name }}
                    </div>
                    @if($project)
                    <div style="font-size:0.7rem; color:#9ca3af; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        {{ $project->title }}
                    </div>
                    @endif
                    @if($task->end_date)
                    <div style="font-size:0.7rem; color:{{ $task->end_date->isPast() && $task->status !== 'complete' ? '#ef4444' : '#9ca3af' }};">
                        Due {{ $task->end_date->format('M d') }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
        @if($open->count() > 8)
        <div class="col-12">
            <div style="font-size:0.75rem; color:#9ca3af; padding:4px 8px;">
                + {{ $open->count() - 8 }} more tasks
            </div>
        </div>
        @endif
    </div>
</div>
@endif
@endforeach

@if($users->isEmpty() || $users->every(fn($u) => $u->taskAssignments->isEmpty()))
<div class="kore-card text-center py-5" style="color:#9ca3af;">
    <i class="bi bi-people fs-2 d-block mb-2"></i>
    No task assignments found. Assign tasks to team members in the project deliverables.
</div>
@endif

@endsection
