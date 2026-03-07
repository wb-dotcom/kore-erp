@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('approvals.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">Timesheet Approvals</h4>
        <div style="font-size:0.75rem; color:#6b7280;">{{ $timesheets->total() }} pending</div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

<div class="kore-card p-0">
    <table class="table kore-table mb-0">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Period</th>
                <th>Due Date</th>
                <th class="text-center">Hours</th>
                <th>Submitted</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($timesheets as $ts)
            <tr>
                <td>
                    <div class="fw-600" style="font-size:0.82rem;">{{ $ts->user?->full_name ?? '—' }}</div>
                    <div style="font-size:0.72rem; color:#9ca3af;">{{ $ts->user?->department ?? '' }}</div>
                </td>
                <td style="font-size:0.8rem;">{{ $ts->period?->label ?? '—' }}</td>
                <td style="font-size:0.78rem; color:#6b7280;">{{ $ts->period?->due_date?->format('M d, Y') ?? '—' }}</td>
                <td class="text-center fw-600" style="font-size:0.82rem;">{{ number_format($ts->total_hours, 2) }}</td>
                <td style="font-size:0.78rem; color:#6b7280;">{{ $ts->submitted_at?->format('M d, Y g:i A') ?? '—' }}</td>
                <td class="text-end">
                    <div class="d-flex gap-1 justify-content-end">
                        <a href="{{ route('timesheet.index', ['period_id' => $ts->period_id]) }}"
                            class="btn btn-sm btn-light" target="_blank" title="View timesheet">
                            <i class="bi bi-eye"></i>
                        </a>
                        <form action="{{ route('approvals.approve', ['type' => 'timesheet', 'id' => $ts->id]) }}"
                            method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success"
                                onclick="return confirm('Approve this timesheet?')">
                                <i class="bi bi-check-lg"></i> Approve
                            </button>
                        </form>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                            data-bs-toggle="modal" data-bs-target="#rejectModal"
                            data-type="timesheet" data-id="{{ $ts->id }}"
                            data-name="{{ $ts->user?->full_name }}">
                            <i class="bi bi-x-lg"></i> Reject
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center py-5" style="color:#9ca3af;">
                    <i class="bi bi-check2-circle fs-2 d-block mb-2 text-success"></i>
                    No timesheets pending approval.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($timesheets->hasPages())
<div class="mt-3">{{ $timesheets->links() }}</div>
@endif

@include('approvals._reject-modal')

@endsection
