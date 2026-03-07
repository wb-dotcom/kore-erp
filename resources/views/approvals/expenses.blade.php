@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('approvals.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">Expense Approvals</h4>
        <div style="font-size:0.75rem; color:#6b7280;">{{ $expenses->total() }} pending</div>
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
                <th>Date</th>
                <th>Category</th>
                <th>Project</th>
                <th>Description</th>
                <th class="text-end">Amount</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $exp)
            <tr>
                <td>
                    <div class="fw-600" style="font-size:0.82rem;">{{ $exp->user?->full_name ?? '—' }}</div>
                </td>
                <td style="font-size:0.8rem;">{{ $exp->expense_date->format('M d, Y') }}</td>
                <td style="font-size:0.78rem; color:#6b7280;">{{ $exp->category ?? '—' }}</td>
                <td style="font-size:0.78rem; color:#6b7280;">{{ $exp->project?->title ?? '—' }}</td>
                <td style="font-size:0.78rem; color:#6b7280; max-width:180px;">
                    <div class="text-truncate">{{ $exp->description ?? '—' }}</div>
                </td>
                <td class="text-end fw-600" style="font-size:0.85rem;">
                    ${{ number_format($exp->amount, 2) }}
                </td>
                <td class="text-end">
                    <div class="d-flex gap-1 justify-content-end">
                        <form action="{{ route('approvals.approve', ['type' => 'expense', 'id' => $exp->id]) }}"
                            method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success"
                                onclick="return confirm('Approve this expense?')">
                                <i class="bi bi-check-lg"></i> Approve
                            </button>
                        </form>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                            data-bs-toggle="modal" data-bs-target="#rejectModal"
                            data-type="expense" data-id="{{ $exp->id }}"
                            data-name="{{ $exp->user?->full_name }}">
                            <i class="bi bi-x-lg"></i> Reject
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center py-5" style="color:#9ca3af;">
                    <i class="bi bi-check2-circle fs-2 d-block mb-2 text-success"></i>
                    No expenses pending approval.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($expenses->hasPages())
<div class="mt-3">{{ $expenses->links() }}</div>
@endif

@include('approvals._reject-modal')

@endsection
