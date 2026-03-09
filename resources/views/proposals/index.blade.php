@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">Proposals</h4>
        <div style="font-size:0.75rem; color:#6b7280;">{{ $proposals->total() }} total</div>
    </div>
    <a href="{{ route('proposals.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> New Proposal
    </a>
</div>

{{-- Filter Bar --}}
<div class="kore-card mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-sm-4">
            <label class="form-label" style="font-size:0.75rem; font-weight:600;">Search</label>
            <input type="text" name="search" class="form-control form-control-sm"
                placeholder="Title or client..." value="{{ request('search') }}">
        </div>
        <div class="col-sm-2">
            <label class="form-label" style="font-size:0.75rem; font-weight:600;">Status</label>
            <select name="status_id" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                @foreach($statuses as $s)
                <option value="{{ $s->id }}" {{ request('status_id') == $s->id ? 'selected' : '' }}>
                    {{ $s->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-2">
            <label class="form-label" style="font-size:0.75rem; font-weight:600;">Year</label>
            <select name="year" class="form-select form-select-sm">
                <option value="">All Years</option>
                @foreach($years as $y)
                <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-auto">
            <button type="submit" class="btn btn-sm btn-primary">Filter</button>
            <a href="{{ route('proposals.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
        </div>
    </form>
</div>

{{-- Proposals Table --}}
<div class="kore-card p-0">
    <table class="table kore-table mb-0">
        <thead>
            <tr>
                <th>Ref</th>
                <th>Title</th>
                <th>Client</th>
                <th>Work Type</th>
                <th>Account Manager</th>
                <th>Submitted</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($proposals as $proposal)
            <tr>
                <td>
                    <a href="{{ route('proposals.show', $proposal) }}" class="text-decoration-none fw-600" style="color:#4c8bf5; font-size:0.8rem;">
                        {{ $proposal->ref }}
                    </a>
                </td>
                <td style="max-width:200px;">
                    <div class="text-truncate" style="font-size:0.82rem;">{{ $proposal->title }}</div>
                </td>
                <td style="font-size:0.8rem; color:#374151;">{{ $proposal->company?->name ?? '—' }}</td>
                <td style="font-size:0.78rem; color:#6b7280;">{{ $proposal->workType?->name ?? '—' }}</td>
                <td style="font-size:0.78rem; color:#6b7280;">{{ $proposal->accountManager?->full_name ?? '—' }}</td>
                <td style="font-size:0.78rem; color:#6b7280;">
                    {{ $proposal->submitted_date?->format('M d, Y') ?? '—' }}
                </td>
                <td>
                    @php
                        $cls = match($proposal->status?->name) {
                            'Approved'  => 'approved',
                            'Submitted' => 'active',
                            'Rejected'  => 'rejected',
                            default     => 'draft',
                        };
                    @endphp
                    <span class="badge badge-{{ $cls }}">{{ $proposal->status?->name ?? '—' }}</span>
                </td>
                <td class="text-end">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" style="font-size:0.8rem;">
                            <li><a class="dropdown-item" href="{{ route('proposals.show', $proposal) }}">
                                <i class="bi bi-eye me-2"></i>View
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('proposals.edit', $proposal) }}">
                                <i class="bi bi-pencil me-2"></i>Edit
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('proposals.destroy', $proposal) }}" method="POST"
                                    onsubmit="return confirm('Delete this proposal?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-trash me-2"></i>Delete
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center py-5" style="color:#9ca3af;">
                    <i class="bi bi-file-earmark-text fs-2 d-block mb-2"></i>
                    No proposals found.
                    <a href="{{ route('proposals.create') }}" class="d-block mt-2 text-primary" style="font-size:0.8rem;">Create your first proposal</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
@if($proposals->hasPages())
<div class="d-flex justify-content-between align-items-center mt-3">
    <div style="font-size:0.75rem; color:#6b7280;">
        Showing {{ $proposals->firstItem() }}–{{ $proposals->lastItem() }} of {{ $proposals->total() }}
    </div>
    {{ $proposals->links() }}
</div>
@endif

@endsection
