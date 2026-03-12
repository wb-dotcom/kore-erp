@extends('layouts.app')

@push('styles')
<style>
/* ── Employee Dashboard ──────────────────────────────────── */
.welcome-bar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 26px; flex-wrap: wrap; gap: 12px;
}
.welcome-bar .welcome-text h2 {
    font-size: 22px; font-weight: 800; color: var(--c-t1);
    letter-spacing: -0.5px; margin: 0 0 2px;
}
.welcome-bar .welcome-text p {
    font-size: 13.5px; color: var(--c-t3); margin: 0;
}
.welcome-bar .welcome-actions { display: flex; gap: 8px; align-items: center; }

.task-row {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 0; border-bottom: 1px solid #f2f4f8;
    transition: padding 0.12s;
}
.task-row:last-child { border-bottom: none; }
.task-row:hover { padding-left: 4px; }
.task-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }

.info-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 9px 0; border-bottom: 1px solid #f2f4f8;
    font-size: 13.5px;
}
.info-row:last-child { border-bottom: none; }
.info-label { color: var(--c-t3); font-weight: 500; font-size: 13px; }
.info-value { font-weight: 600; color: var(--c-t1); }
</style>
@endpush

@section('content')

{{-- Welcome Banner --}}
<div class="welcome-bar">
    <div class="welcome-text">
        <h2>Welcome back, {{ auth()->user()->first_name }}! 👋</h2>
        <p>Here's what's on your plate today — {{ now()->format('l, F j, Y') }}</p>
    </div>
    <div class="welcome-actions">
        <a href="{{ route('timesheet.index') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-clock me-1"></i> Log Time
        </a>
        <a href="{{ route('tasks.mine') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-check2-square me-1"></i> My Tasks
        </a>
    </div>
</div>

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

    {{-- Hours --}}
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card accent-blue h-100">
            <div class="stat-card-top">
                <div class="stat-icon-wrap" style="background:rgba(76,139,245,0.1);">
                    <i class="bi bi-clock-history" style="color:#4c8bf5;"></i>
                </div>
                <span class="trend-flat">This week</span>
            </div>
            <div class="stat-value" style="color:#4c8bf5;">{{ number_format($hoursThisWeek, 1) }}</div>
            <div class="stat-label">Hours Logged</div>
            @php $pct = min(100, ($hoursThisWeek / 40) * 100); @endphp
            <div class="mt-3">
                <div class="progress" style="height:5px;">
                    <div class="progress-bar" style="width:{{ $pct }}%;background:#4c8bf5;"></div>
                </div>
                <div style="font-size:11px;color:var(--c-t4);margin-top:5px;">{{ round($pct) }}% of 40h weekly target</div>
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
            @if($currentTimesheet)
                @php
                    $tsColor = match($currentTimesheet->status) {
                        'approved'  => '#22c55e',
                        'submitted' => '#4c8bf5',
                        default     => '#f59e0b',
                    };
                @endphp
                <div class="stat-value" style="color:{{ $tsColor }};font-size:22px;letter-spacing:-0.5px;">{{ ucfirst($currentTimesheet->status) }}</div>
            @else
                <div class="stat-value" style="color:var(--c-t4);font-size:22px;">None</div>
            @endif
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
                <h5><i class="bi bi-check2-square me-2" style="color:#4c8bf5;"></i>Active Tasks</h5>
                <a href="{{ route('tasks.mine') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>

            @forelse($myTasks as $assignment)
            @php
                $task      = $assignment->task;
                $project   = $task?->milestone?->deliverable?->project;
                $isOverdue = $task?->end_date && $task->end_date->isPast();
                $dotColor  = $isOverdue ? '#ef4444' : '#22c55e';
            @endphp
            <div class="task-row">
                <div class="task-dot" style="background:{{ $dotColor }};"></div>
                <div class="flex-grow-1" style="min-width:0;">
                    <div class="fw-600 text-truncate" style="font-size:13.5px;color:var(--c-t1);">{{ $task?->name }}</div>
                    <div style="font-size:12px;color:var(--c-t4);">
                        {{ $project?->title ?? '—' }}
                        @if($task?->end_date)
                            &nbsp;·&nbsp;<span style="color:{{ $isOverdue ? '#ef4444' : 'var(--c-t4)' }};">
                                {{ $isOverdue ? 'Overdue · ' : 'Due ' }}{{ $task->end_date->format('M d') }}
                            </span>
                        @endif
                    </div>
                </div>
                <span class="badge badge-{{ $isOverdue ? 'overdue' : 'active' }}">{{ $isOverdue ? 'Overdue' : 'Active' }}</span>
            </div>
            @empty
            <div class="empty-state">
                <i class="bi bi-check-circle"></i>
                <p>No active tasks — you're all caught up!</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Right column --}}
    <div class="col-lg-5">

        {{-- Current Timesheet --}}
        <div class="kore-card mb-4">
            <div class="kore-card-header">
                <h5><i class="bi bi-clock me-2" style="color:#8b5cf6;"></i>Current Timesheet</h5>
                <a href="{{ route('timesheet.index') }}" class="btn btn-sm btn-outline-primary">Open</a>
            </div>
            @if($currentTimesheet && $currentTimesheet->period)
                <div class="info-row">
                    <span class="info-label">Period</span>
                    <span class="info-value">{{ $currentTimesheet->period->label }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Hours</span>
                    <span class="info-value" style="font-size:16px;">{{ number_format($currentTimesheet->total_hours, 1) }}h</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status</span>
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

        {{-- Time-Off Requests --}}
        <div class="kore-card">
            <div class="kore-card-header">
                <h5><i class="bi bi-calendar-check me-2" style="color:#f59e0b;"></i>Time-Off Requests</h5>
                <a href="{{ route('approvals.time-off') }}" class="btn btn-sm btn-outline-secondary">History</a>
            </div>
            @forelse($pendingTimeOff as $req)
            <div class="info-row">
                <div>
                    <div style="font-size:13.5px;font-weight:500;color:var(--c-t1);">{{ ucfirst(str_replace('_', ' ', $req->request_type)) }}</div>
                    <div style="font-size:12px;color:var(--c-t4);">
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
