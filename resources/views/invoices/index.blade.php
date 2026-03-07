@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">Invoices</h4>
        <div style="font-size:0.75rem; color:#6b7280;">{{ $invoices->total() }} total</div>
    </div>
    <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> New Invoice
    </a>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

{{-- Summary Strip --}}
<div class="row g-3 mb-4">
    <div class="col-sm-3">
        <div class="kore-card text-center py-3">
            <div style="font-size:1.1rem; font-weight:700; color:#6b7280;">${{ number_format($totals['draft'], 0) }}</div>
            <div style="font-size:0.72rem; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em;">Draft</div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="kore-card text-center py-3">
            <div style="font-size:1.1rem; font-weight:700; color:#4c8bf5;">${{ number_format($totals['sent'], 0) }}</div>
            <div style="font-size:0.72rem; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em;">Sent / Outstanding</div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="kore-card text-center py-3">
            <div style="font-size:1.1rem; font-weight:700; color:#ef4444;">${{ number_format($totals['overdue'], 0) }}</div>
            <div style="font-size:0.72rem; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em;">Overdue</div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="kore-card text-center py-3">
            <div style="font-size:1.1rem; font-weight:700; color:#22c55e;">${{ number_format($totals['paid'], 0) }}</div>
            <div style="font-size:0.72rem; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em;">Paid (YTD)</div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="kore-card mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-sm-4">
            <label class="form-label" style="font-size:0.75rem; font-weight:600;">Search</label>
            <input type="text" name="search" class="form-control form-control-sm"
                placeholder="Invoice #, client, project..." value="{{ request('search') }}">
        </div>
        <div class="col-sm-2">
            <label class="form-label" style="font-size:0.75rem; font-weight:600;">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="draft"   {{ request('status') === 'draft'   ? 'selected':'' }}>Draft</option>
                <option value="sent"    {{ request('status') === 'sent'    ? 'selected':'' }}>Sent</option>
                <option value="overdue" {{ request('status') === 'overdue' ? 'selected':'' }}>Overdue</option>
                <option value="paid"    {{ request('status') === 'paid'    ? 'selected':'' }}>Paid</option>
            </select>
        </div>
        <div class="col-sm-auto">
            <button type="submit" class="btn btn-sm btn-primary">Filter</button>
            <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="kore-card p-0">
    <table class="table kore-table mb-0">
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Client</th>
                <th>Project</th>
                <th>Date</th>
                <th>Due</th>
                <th class="text-end">Total</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $inv)
            <tr>
                <td>
                    <a href="{{ route('invoices.show', $inv) }}" class="text-decoration-none fw-600"
                        style="color:#4c8bf5; font-size:0.82rem;">
                        {{ $inv->invoice_number }}
                    </a>
                </td>
                <td style="font-size:0.8rem; color:#374151;">{{ $inv->company?->name ?? '—' }}</td>
                <td style="font-size:0.78rem; color:#6b7280; max-width:160px;">
                    <div class="text-truncate">{{ $inv->project?->title ?? '—' }}</div>
                </td>
                <td style="font-size:0.78rem; color:#6b7280;">{{ $inv->invoice_date->format('M d, Y') }}</td>
                <td style="font-size:0.78rem; color:{{ $inv->isOverdue() ? '#ef4444' : '#6b7280' }};">
                    {{ $inv->due_date?->format('M d, Y') ?? '—' }}
                </td>
                <td class="text-end fw-600" style="font-size:0.85rem;">${{ number_format($inv->total, 2) }}</td>
                <td>
                    @php $cls = match($inv->status) {
                        'sent'    => 'active',
                        'paid'    => 'approved',
                        'overdue' => 'rejected',
                        default   => 'draft',
                    }; @endphp
                    <span class="badge badge-{{ $cls }}">{{ ucfirst($inv->status) }}</span>
                </td>
                <td class="text-end">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" style="font-size:0.8rem;">
                            <li><a class="dropdown-item" href="{{ route('invoices.show', $inv) }}">
                                <i class="bi bi-eye me-2"></i>View
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('invoices.pdf', $inv) }}">
                                <i class="bi bi-file-pdf me-2"></i>Download PDF
                            </a></li>
                            @if($inv->status !== 'paid')
                            <li><a class="dropdown-item" href="{{ route('invoices.edit', $inv) }}">
                                <i class="bi bi-pencil me-2"></i>Edit
                            </a></li>
                            <li>
                                <form action="{{ route('invoices.mark-paid', $inv) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-success"
                                        onclick="return confirm('Mark as paid?')">
                                        <i class="bi bi-check-circle me-2"></i>Mark Paid
                                    </button>
                                </form>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('invoices.destroy', $inv) }}" method="POST"
                                    onsubmit="return confirm('Delete {{ $inv->invoice_number }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-trash me-2"></i>Delete
                                    </button>
                                </form>
                            </li>
                            @endif
                        </ul>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center py-5" style="color:#9ca3af;">
                    <i class="bi bi-receipt fs-2 d-block mb-2"></i>
                    No invoices yet.
                    <a href="{{ route('invoices.create') }}" class="d-block mt-2 text-primary" style="font-size:0.8rem;">
                        Create your first invoice
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($invoices->hasPages())
<div class="d-flex justify-content-between align-items-center mt-3">
    <div style="font-size:0.75rem; color:#6b7280;">
        Showing {{ $invoices->firstItem() }}–{{ $invoices->lastItem() }} of {{ $invoices->total() }}
    </div>
    {{ $invoices->links() }}
</div>
@endif

@endsection
