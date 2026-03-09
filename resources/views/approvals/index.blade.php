@extends('layouts.app')

@section('content')

<div class="mb-4">
    <h4 class="mb-0 fw-700" style="font-size:1rem;">Approval Center</h4>
    <div style="font-size:0.75rem; color:#6b7280;">Review and action pending requests</div>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

<div class="row g-3">
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('approvals.timesheets') }}" class="text-decoration-none">
            <div class="kore-card text-center py-4" style="transition:.15s; border:2px solid {{ $pendingTimesheets ? '#4c8bf5' : '#f3f4f6' }};">
                <div style="font-size:2rem; font-weight:700; color:{{ $pendingTimesheets ? '#4c8bf5' : '#d1d5db' }};">
                    {{ $pendingTimesheets }}
                </div>
                <div class="fw-600 mt-1" style="font-size:0.85rem; color:#374151;">Timesheets</div>
                <div style="font-size:0.75rem; color:#9ca3af;">pending approval</div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('approvals.time-off') }}" class="text-decoration-none">
            <div class="kore-card text-center py-4" style="transition:.15s; border:2px solid {{ $pendingTimeOff ? '#f59e0b' : '#f3f4f6' }};">
                <div style="font-size:2rem; font-weight:700; color:{{ $pendingTimeOff ? '#f59e0b' : '#d1d5db' }};">
                    {{ $pendingTimeOff }}
                </div>
                <div class="fw-600 mt-1" style="font-size:0.85rem; color:#374151;">Time-Off</div>
                <div style="font-size:0.75rem; color:#9ca3af;">pending approval</div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('approvals.remote-work') }}" class="text-decoration-none">
            <div class="kore-card text-center py-4" style="transition:.15s; border:2px solid {{ $pendingRemoteWork ? '#8b5cf6' : '#f3f4f6' }};">
                <div style="font-size:2rem; font-weight:700; color:{{ $pendingRemoteWork ? '#8b5cf6' : '#d1d5db' }};">
                    {{ $pendingRemoteWork }}
                </div>
                <div class="fw-600 mt-1" style="font-size:0.85rem; color:#374151;">Remote Work</div>
                <div style="font-size:0.75rem; color:#9ca3af;">pending approval</div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('approvals.expenses') }}" class="text-decoration-none">
            <div class="kore-card text-center py-4" style="transition:.15s; border:2px solid {{ $pendingExpenses ? '#22c55e' : '#f3f4f6' }};">
                <div style="font-size:2rem; font-weight:700; color:{{ $pendingExpenses ? '#22c55e' : '#d1d5db' }};">
                    {{ $pendingExpenses }}
                </div>
                <div class="fw-600 mt-1" style="font-size:0.85rem; color:#374151;">Expenses</div>
                <div style="font-size:0.75rem; color:#9ca3af;">pending approval</div>
            </div>
        </a>
    </div>
</div>

@if(!$pendingTimesheets && !$pendingTimeOff && !$pendingRemoteWork && !$pendingExpenses)
<div class="kore-card text-center py-5 mt-4" style="color:#9ca3af;">
    <i class="bi bi-check2-circle fs-2 d-block mb-2 text-success"></i>
    <div style="font-size:0.9rem; font-weight:600; color:#374151;">All caught up!</div>
    <div style="font-size:0.8rem;">No pending approvals at this time.</div>
</div>
@endif

@endsection
