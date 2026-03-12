@extends('layouts.app')

@push('styles')
<style>
/* ── Employee Dashboard ────────────────────────────────── */
.task-item {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 0;
    border-bottom: 1px solid #f3f4f6;
    transition: background 0.12s;
    border-radius: 8px;
}
.task-item:last-child { border-bottom: none; }
.task-item:hover { background: #f9fafb; padding-left: 6px; padding-right: 6px; margin: 0 -6px; }
.task-dot {
    width: 8px; height: 8px; border-radius: 50%;
    flex-shrink: 0; margin-top: 1px;
}
.timesheet-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 9px 0; border-bottom: 1px solid #f3f4f6;
}
.timesheet-row:last-child { border-bottom: none; }

/* Hours arc widget */
.hours-arc-wrap {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; padding: 10px 0;
}
.hours-arc {
    position: relative; width: 110px; height: 110px;
}
.hours-arc svg { transform: rotate(-90deg); }
.hours-arc .arc-bg { fill: none; stroke: #f3f4f6; stroke-width: 10; }
.hours-arc .arc-fill { fill: none; stroke-width: 10; stroke-linecap: round; transition: stroke-dashoffset 1s cubic-bezier(0.4,0,0.2,1); }
.hours-arc .arc-label {
    position: absolute; inset: 0;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
}
.hours-arc .arc-value { font-size: 1.5rem; font-weight: 700; letter-spacing: -0.5px; color: #1a1d23; }
.hours-arc .arc-unit  { font-size: 0.65rem; color: #9ca3af; font-weight: 500; margin-top: -2px; }
</style>
@endpush

@section('content')

{{-- Dashboard Tab Navigation --}}
<nav class="dashboard-tabs">
    <ul class="nav nav-pills gap-1">
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('dashboard') }}">
                <i class="bi bi-person me-1"></i> My Dashboard
            </a>
        </li>
        @if(auth()->user()->isManager())
        <li class="nav-item">
            <a class="nav-link" href="{{ route('dashboard.business') }}">
                <i class="bi bi-briefcase me-1"></i> Business
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('dashboard.financial') }}">
                <i class="bi bi-currency-dollar me-1"></i> Financial
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('dashboard.kpi') }}">
                <i class="bi bi-graph-up me-1"></i> KPIs
            </a>
        </li>
        @endif
    </ul>
</nav>

{{-- Stat Row --}}
<div class="row g-3 mb-4">

    {{-- Hours This Week — Arc Widget --}}
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card accent-blue h-100">
            <div class="stat-card-top">
                <div class="stat-icon-wrap" style="background:rgba(76,139,245,0.1);">
                    <i class="bi bi-clock-history" style="color:#4c8bf5;"></i>
                </div>
                <span class="badge-draft" style="font-size:0.65rem;padding:3px 8px;border-radius:20px;background:#f3f4f6;color:#6b7280;">This Week</span>
            </div>
            <div class="stat-value" style="color:#4c8bf5;">{{ number_format($hoursThisWeek, 1) }}</div>
            <div class="stat-label">Hours Logged</div>
            <div class="mt-2">
                @php $pct = min(100, ($hoursThisWeek / 40) * 100); @endphp
                <div class="progress mt-2" style="height:4px;">
                    <div class="progress-bar" style="width:{{ $pct }}%; background:#4c8bf5;"></div>
                </div>
                <div style="font-size:0.65rem;color:#9ca3af;margin-top:4px;">{{ round($pct) }}% of 40h target</div>
            </div>
        </div>
    </div>

    {{-- Active Tasks --}}
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card accent-green h-100">
            <div class="stat-card-top">
                <div class="stat-icon-wrap" style="background:rgba(34,197,94,0.1);">
                    <i class="bi bi-check2-square" style="color:#22c55e;"></i>
                </div>
            </div>
            <div class="stat-value" style="color:#22c55e;">{{ $myTasks->count() }}</div>
            <div class="stat-label">Active Tasks</div>
            <div class="stat-sublabel">Assigned to you</div>
        </div>
    </div>

    {{-- Pending Time-Off --}}
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card accent-amber h-100">
            <div class="stat-card-top">
                <div class="stat-icon-wrap" style="background:rgba(245,158,11,0.1);">
                    <i class="bi bi-hourglass-split" style="color:#f59e0b;"></i>
                </div>
            </div>
            <div class="stat-value" style="color:#f59e0b;">{{ $pendingTimeOff->count() }}</div>
            <div class="stat-label">Pending Time-Off</div>
            <div class="stat-sublabel">Awaiting approval</div>
        </div>
    </div>

    {{-- Timesheet Status --}}
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card accent-purple h-100">
            <div class="stat-card-top">
                <div class="stat-icon-wrap" style="background:rgba(139,92,246,0.1);">
                    <i class="bi bi-calendar3" style="color:#8b5cf6;"></i>
                </div>
            </div>
            <div class="mt-1">
                @if($currentTimesheet)
                    @php
                        $tsColor = match($currentTimesheet->status) {
                            'approved'  => '#22c55e',
                            'submitted' => '#4c8bf5',
                            default     => '#f59e0b',
                        };
                    @endphp
                    <div style="font-size:1.3rem;font-weight:700;color:{{ $tsColor }};letter-spacing:-0.5px;">
                        {{ ucfirst($currentTimesheet->status) }}
                    </div>
                @else
                    <div style="font-size:1.1rem;font-weight:700;color:#9ca3af;">None</div>
                @endif
            </div>
            <div class="stat-label">Timesheet Status</div>
            <div class="stat-sublabel">Current period</div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- My Active Tasks --}}
    <div class="col-lg-7">
        <div class="kore-card h-100">
            <div class="kore-card-header">
                <h5><i class="bi bi-check2-square me-2" style="color:#4c8bf5;"></i>My Active Tasks</h5>
                <a href="{{ route('tasks.mine') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>

            @forelse($myTasks as $assignment)
            @php
                $task    = $assignment->task;
                $project = $task?->milestone?->deliverable?->project;
                $isOverdue = $task?->end_date && $task->end_date->isPast();
                $dotColor = $isOverdue ? '#ef4444' : '#4c8bf5';
            @endphp
            <div class="task-item">
                <div class="task-dot" style="background:{{ $dotColor }};"></div>
                <div class="flex-grow-1" style="min-width:0;">
                    <a href="#" class="text-decoration-none fw-600 text-truncate d-block" style="font-size:0.82rem;color:#1a1d23;">
                        {{ $task?->name }}
                    </a>
                    <div style="font-size:0.71rem; color:#9ca3af;">
                        {{ $project?->title ?? '—' }}
                        @if($task?->end_date)
                            &nbsp;·&nbsp;
                            <span style="color:{{ $isOverdue ? '#ef4444' : '#9ca3af' }};">
                                {{ $isOverdue ? 'Overdue · ' : 'Due ' }}{{ $task->end_date->format('M d') }}
                            </span>
                        @endif
                    </div>
                </div>
                @if($isOverdue)
                    <span class="badge badge-overdue">Overdue</span>
                @else
                    <span class="badge badge-active">Active</span>
                @endif
            </div>
            @empty
            <div class="empty-state">
                <i class="bi bi-check-circle"></i>
                <p>No active tasks assigned to you.</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Right Column --}}
    <div class="col-lg-5">

        {{-- Current Timesheet --}}
        <div class="kore-card mb-4">
            <div class="kore-card-header">
                <h5><i class="bi bi-clock me-2" style="color:#8b5cf6;"></i>Current Timesheet</h5>
                <a href="{{ route('timesheet.index') }}" class="btn btn-sm btn-outline-primary">Open</a>
            </div>
            @if($currentTimesheet && $currentTimesheet->period)
                <div class="timesheet-row">
                    <span style="font-size:0.78rem;color:#9ca3af;font-weight:500;">Period</span>
                    <span style="font-size:0.8rem;font-weight:600;">{{ $currentTimesheet->period->label }}</span>
                </div>
                <div class="timesheet-row">
                    <span style="font-size:0.78rem;color:#9ca3af;font-weight:500;">Total Hours</span>
                    <span style="font-size:1rem;font-weight:700;color:#1a1d23;">{{ number_format($currentTimesheet->total_hours, 1) }}h</span>
                </div>
                <div class="timesheet-row">
                    <span style="font-size:0.78rem;color:#9ca3af;font-weight:500;">Status</span>
                    @php
                        $tsCls = match($currentTimesheet->status) {
                            'approved'  => 'approved',
                            'submitted' => 'active',
                            default     => 'pending',
                        };
                    @endphp
                    <span class="badge badge-{{ $tsCls }}">{{ ucfirst($currentTimesheet->status) }}</span>
                </div>
                @if($currentTimesheet->isDraft())
                <div class="mt-3">
                    <a href="{{ route('timesheet.index') }}" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-pencil me-1"></i> Continue Timesheet
                    </a>
                </div>
                @endif
            @else
                <div class="empty-state" style="padding:20px 0;">
                    <i class="bi bi-calendar-x" style="font-size:1.8rem;"></i>
                    <p>No active timesheet period.</p>
                </div>
            @endif
        </div>

        {{-- Pending Time-Off --}}
        <div class="kore-card">
            <div class="kore-card-header">
                <h5><i class="bi bi-calendar-check me-2" style="color:#f59e0b;"></i>Time-Off Requests</h5>
                <a href="{{ route('approvals.time-off') }}" class="btn btn-sm btn-outline-secondary">History</a>
            </div>
            @forelse($pendingTimeOff as $req)
            <div class="timesheet-row">
                <div>
                    <div style="font-size:0.8rem;font-weight:500;">{{ ucfirst(str_replace('_', ' ', $req->request_type)) }}</div>
                    <div style="font-size:0.71rem;color:#9ca3af;">
                        <i class="bi bi-calendar2 me-1"></i>{{ $req->start_date->format('M d') }} – {{ $req->end_date->format('M d, Y') }}
                    </div>
                </div>
                <span class="badge badge-pending">Pending</span>
            </div>
            @empty
            <div class="empty-state" style="padding:20px 0;">
                <i class="bi bi-calendar-heart" style="font-size:1.8rem;"></i>
                <p>No pending time-off requests.</p>
            </div>
            @endforelse
        </div>

    </div>
</div>

@endsection
