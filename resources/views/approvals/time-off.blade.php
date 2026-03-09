@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('approvals.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">Time-Off Approvals</h4>
        <div style="font-size:0.75rem; color:#6b7280;">{{ $requests->total() }} pending</div>
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
                <th>Type</th>
                <th>Dates</th>
                <th class="text-center">Hours</th>
                <th>Reason</th>
                <th>Submitted</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($requests as $req)
            <tr>
                <td>
                    <div class="fw-600" style="font-size:0.82rem;">{{ $req->user?->full_name ?? '—' }}</div>
                </td>
                <td>
                    @php
                        $typeLabel = match($req->request_type) {
                            'pto'         => 'PTO',
                            'unpaid'      => 'Unpaid',
                            'remote_work' => 'Remote Work',
                            default       => ucfirst($req->request_type),
                        };
                    @endphp
                    <span class="badge badge-draft">{{ $typeLabel }}</span>
                </td>
                <td style="font-size:0.8rem;">
                    {{ $req->start_date->format('M d') }}
                    @if(!$req->start_date->eq($req->end_date))
                        – {{ $req->end_date->format('M d, Y') }}
                    @else
                        , {{ $req->start_date->format('Y') }}
                    @endif
                </td>
                <td class="text-center" style="font-size:0.8rem;">{{ $req->hours ? number_format($req->hours, 1) : '—' }}</td>
                <td style="font-size:0.78rem; color:#6b7280; max-width:200px;">
                    <div class="text-truncate">{{ $req->reason ?? '—' }}</div>
                </td>
                <td style="font-size:0.78rem; color:#6b7280;">{{ $req->created_at->format('M d, Y') }}</td>
                <td class="text-end">
                    <div class="d-flex gap-1 justify-content-end">
                        <form action="{{ route('approvals.approve', ['type' => 'time-off', 'id' => $req->id]) }}"
                            method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success"
                                onclick="return confirm('Approve this request?')">
                                <i class="bi bi-check-lg"></i> Approve
                            </button>
                        </form>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                            data-bs-toggle="modal" data-bs-target="#rejectModal"
                            data-type="time-off" data-id="{{ $req->id }}"
                            data-name="{{ $req->user?->full_name }}">
                            <i class="bi bi-x-lg"></i> Reject
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center py-5" style="color:#9ca3af;">
                    <i class="bi bi-check2-circle fs-2 d-block mb-2 text-success"></i>
                    No time-off requests pending.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($requests->hasPages())
<div class="mt-3">{{ $requests->links() }}</div>
@endif

@include('approvals._reject-modal')

@endsection
