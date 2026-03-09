@extends('layouts.app')

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
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon text-primary"><i class="bi bi-clock-history"></i></div>
            <div class="stat-value">{{ number_format($hoursThisWeek, 1) }}</div>
            <div class="stat-label">Hours This Week</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon text-success"><i class="bi bi-check2-square"></i></div>
            <div class="stat-value">{{ $myTasks->count() }}</div>
            <div class="stat-label">Active Tasks</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon text-warning"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-value">{{ $pendingTimeOff->count() }}</div>
            <div class="stat-label">Pending Time-Off Requests</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon text-info"><i class="bi bi-calendar3"></i></div>
            <div class="stat-value">
                @if($currentTimesheet)
                    <span class="badge badge-{{ $currentTimesheet->status === 'approved' ? 'approved' : ($currentTimesheet->status === 'submitted' ? 'active' : 'pending') }} fs-6">
                        {{ ucfirst($currentTimesheet->status) }}
                    </span>
                @else
                    <span class="badge badge-draft fs-6">None</span>
                @endif
            </div>
            <div class="stat-label">Timesheet Status</div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- My Active Tasks --}}
    <div class="col-lg-7">
        <div class="kore-card">
            <div class="kore-card-header">
                <h5><i class="bi bi-check2-square me-2"></i>My Active Tasks</h5>
                <a href="{{ route('tasks.mine') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>

            @forelse($myTasks as $assignment)
            @php $task = $assignment->task; $project = $task?->milestone?->deliverable?->project; @endphp
            <div class="d-flex align-items-start py-2 border-bottom gap-3">
                <div class="flex-grow-1" style="min-width:0;">
                    <div class="fw-500 text-truncate" style="font-size:0.82rem;">{{ $task?->name }}</div>
                    <div style="font-size:0.72rem; color:#6b7280;">
                        {{ $project?->title ?? '—' }}
                        @if($task?->end_date)
                            &nbsp;·&nbsp; Due {{ $task->end_date->format('M d') }}
                        @endif
                    </div>
                </div>
                <span class="badge badge-active flex-shrink-0">Active</span>
            </div>
            @empty
            <div class="text-center py-4" style="color:#9ca3af; font-size:0.8rem;">
                <i class="bi bi-check-circle fs-2 d-block mb-2"></i>
                No active tasks assigned to you.
            </div>
            @endforelse
        </div>
    </div>

    {{-- Right Column --}}
    <div class="col-lg-5">

        {{-- Current Timesheet --}}
        <div class="kore-card mb-4">
            <div class="kore-card-header">
                <h5><i class="bi bi-clock me-2"></i>Current Timesheet</h5>
                <a href="{{ route('timesheet.index') }}" class="btn btn-sm btn-outline-primary">Open</a>
            </div>
            @if($currentTimesheet && $currentTimesheet->period)
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:0.8rem; color:#6b7280;">Period</span>
                    <span style="font-size:0.8rem; font-weight:600;">{{ $currentTimesheet->period->label }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:0.8rem; color:#6b7280;">Total Hours</span>
                    <span style="font-size:0.8rem; font-weight:600;">{{ number_format($currentTimesheet->total_hours, 1) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size:0.8rem; color:#6b7280;">Status</span>
                    <span class="badge badge-{{ $currentTimesheet->status === 'approved' ? 'approved' : ($currentTimesheet->status === 'submitted' ? 'active' : 'pending') }}">
                        {{ ucfirst($currentTimesheet->status) }}
                    </span>
                </div>
                @if($currentTimesheet->isDraft())
                <div class="mt-3">
                    <a href="{{ route('timesheet.index') }}" class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-pencil me-1"></i> Continue Timesheet
                    </a>
                </div>
                @endif
            @else
                <div class="text-center py-3" style="color:#9ca3af; font-size:0.8rem;">
                    <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                    No active timesheet period.
                </div>
            @endif
        </div>

        {{-- Pending Time-Off --}}
        <div class="kore-card">
            <div class="kore-card-header">
                <h5><i class="bi bi-calendar-check me-2"></i>Time-Off Requests</h5>
                <a href="{{ route('approvals.time-off') }}" class="btn btn-sm btn-outline-secondary">History</a>
            </div>
            @forelse($pendingTimeOff as $req)
            <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                <div>
                    <div style="font-size:0.8rem; font-weight:500;">{{ ucfirst(str_replace('_', ' ', $req->request_type)) }}</div>
                    <div style="font-size:0.72rem; color:#6b7280;">
                        {{ $req->start_date->format('M d') }} – {{ $req->end_date->format('M d, Y') }}
                    </div>
                </div>
                <span class="badge badge-pending">Pending</span>
            </div>
            @empty
            <div class="text-center py-3" style="color:#9ca3af; font-size:0.8rem;">
                No pending time-off requests.
            </div>
            @endforelse
        </div>

    </div>
</div>

@endsection
