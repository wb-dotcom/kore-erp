@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('approvals.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0 fw-700" style="font-size:1rem;">Remote Work Requests</h4>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

<div class="kore-card p-0">
    <table class="table kore-table mb-0">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Dates</th>
                <th>Reason</th>
                <th>Submitted</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($requests as $req)
            <tr>
                <td>
                    <div class="fw-500" style="font-size:0.82rem;">{{ $req->user?->full_name }}</div>
                    <div style="font-size:0.75rem; color:#9ca3af;">{{ $req->user?->department }}</div>
                </td>
                <td style="font-size:0.8rem;">
                    {{ $req->start_date->format('M d, Y') }}
                    @if($req->end_date && $req->end_date != $req->start_date)
                        — {{ $req->end_date->format('M d, Y') }}
                    @endif
                </td>
                <td style="font-size:0.78rem; color:#6b7280; max-width:200px;">
                    {{ Str::limit($req->reason ?? '—', 60) }}
                </td>
                <td style="font-size:0.75rem; color:#9ca3af;">
                    {{ $req->created_at->format('M d, Y') }}
                </td>
                <td class="text-end">
                    <div class="d-flex gap-1 justify-content-end">
                        <form action="{{ route('approvals.approve', ['type' => 'time-off', 'id' => $req->id]) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success" style="font-size:0.75rem;">
                                <i class="bi bi-check-lg"></i> Approve
                            </button>
                        </form>
                        <button type="button" class="btn btn-sm btn-outline-danger" style="font-size:0.75rem;"
                            data-bs-toggle="modal" data-bs-target="#rejectModal"
                            data-type="time-off" data-id="{{ $req->id }}">
                            <i class="bi bi-x-lg"></i> Reject
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center py-5" style="color:#9ca3af; font-size:0.8rem;">
                    No pending remote work requests.
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
