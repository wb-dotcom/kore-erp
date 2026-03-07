@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">Timesheet History</h4>
        <div style="font-size:0.75rem; color:#6b7280;">{{ auth()->user()->full_name }}</div>
    </div>
    <a href="{{ route('timesheet.index') }}" class="btn btn-sm btn-primary">
        <i class="bi bi-calendar-week me-1"></i> Current Timesheet
    </a>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

<div class="kore-card p-0">
    <table class="table kore-table mb-0">
        <thead>
            <tr>
                <th>Period</th>
                <th>Due Date</th>
                <th class="text-center">Total Hours</th>
                <th>Status</th>
                <th>Submitted</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($timesheets as $ts)
            <tr>
                <td>
                    <a href="{{ route('timesheet.index', ['period_id' => $ts->period_id]) }}"
                        class="text-decoration-none fw-600" style="color:#4c8bf5; font-size:0.82rem;">
                        {{ $ts->period?->label ?? '—' }}
                    </a>
                </td>
                <td style="font-size:0.78rem; color:#6b7280;">
                    {{ $ts->period?->due_date?->format('M d, Y') ?? '—' }}
                </td>
                <td class="text-center" style="font-size:0.82rem; font-weight:600;">
                    {{ number_format($ts->total_hours, 2) }}
                </td>
                <td>
                    @php
                        $cls = match($ts->status) {
                            'submitted' => 'active',
                            'approved'  => 'approved',
                            'rejected'  => 'rejected',
                            default     => 'draft',
                        };
                    @endphp
                    <span class="badge badge-{{ $cls }}">{{ ucfirst($ts->status) }}</span>
                </td>
                <td style="font-size:0.78rem; color:#6b7280;">
                    {{ $ts->submitted_at?->format('M d, Y g:i A') ?? '—' }}
                </td>
                <td class="text-end">
                    <a href="{{ route('timesheet.index', ['period_id' => $ts->period_id]) }}"
                        class="btn btn-sm btn-light" style="font-size:0.75rem;">
                        <i class="bi bi-eye me-1"></i> View
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center py-5" style="color:#9ca3af;">
                    <i class="bi bi-clock-history fs-2 d-block mb-2"></i>
                    No timesheet history yet.
                    <a href="{{ route('timesheet.index') }}" class="d-block mt-2 text-primary" style="font-size:0.8rem;">
                        Go to current timesheet
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($timesheets->hasPages())
<div class="d-flex justify-content-between align-items-center mt-3">
    <div style="font-size:0.75rem; color:#6b7280;">
        Showing {{ $timesheets->firstItem() }}–{{ $timesheets->lastItem() }} of {{ $timesheets->total() }}
    </div>
    {{ $timesheets->links() }}
</div>
@endif

@endsection
