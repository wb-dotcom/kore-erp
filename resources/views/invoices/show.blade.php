@extends('layouts.app')

@section('content')

@php
$statusCls = match($invoice->status) {
    'sent'    => 'active',
    'paid'    => 'approved',
    'overdue' => 'rejected',
    'partial' => 'warning',
    'void'    => 'secondary',
    default   => 'draft',
};
$canEdit = !in_array($invoice->status, ['paid', 'void']);
@endphp

{{-- ── Page Header ─────────────────────────────────────────────────────────── --}}
<div class="d-flex justify-content-between align-items-start mb-4 gap-3">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <div class="d-flex align-items-center gap-2">
                <h4 class="mb-0 fw-700" style="font-size:1rem;">{{ $invoice->invoice_number }}</h4>
                <span class="badge badge-{{ $statusCls }}">{{ ucfirst($invoice->status) }}</span>
                <span style="font-size:0.72rem; background:#f3f4f6; padding:2px 8px; border-radius:99px; color:#6b7280;">
                    {{ $invoice->invoice_type_label }}
                </span>
            </div>
            <div style="font-size:0.75rem; color:#9ca3af; margin-top:2px;">
                {{ $invoice->company?->name }}
                @if($invoice->proposal)
                    · <a href="{{ route('proposals.show', $invoice->proposal) }}" style="color:#4c8bf5; text-decoration:none;">{{ $invoice->proposal->ref }} — {{ $invoice->proposal->title }}</a>
                @elseif($invoice->project)
                    · <a href="{{ route('projects.show', $invoice->project) }}" style="color:#4c8bf5; text-decoration:none;">{{ $invoice->project->title }}</a>
                @endif
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 flex-wrap justify-content-end">
        <a href="{{ route('invoices.preview', $invoice) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
            <i class="bi bi-eye me-1"></i> Preview
        </a>
        <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
            <i class="bi bi-file-pdf me-1"></i> PDF
        </a>
        @if($canEdit && $invoice->status === 'draft')
        <form action="{{ route('invoices.send', $invoice) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-send me-1"></i> Mark Sent
            </button>
        </form>
        @endif
        @if($canEdit && !in_array($invoice->status, ['draft']))
        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal">
            <i class="bi bi-credit-card me-1"></i> Apply Payment
        </button>
        @endif
        @if($invoice->status !== 'paid' && $invoice->status !== 'void')
        <form action="{{ route('invoices.mark-paid', $invoice) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-success"
                onclick="return confirm('Mark entire invoice as paid?')">
                <i class="bi bi-check-circle me-1"></i> Mark Paid
            </button>
        </form>
        @endif
        @if($canEdit)
        <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        @endif
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger py-2 mb-3" style="font-size:0.8rem;">{{ session('error') }}</div>
@endif

<div class="row g-4">
    {{-- ── Invoice Body ─────────────────────────────────────────────────────── --}}
    <div class="col-lg-8">
        <div class="kore-card">
            {{-- Invoice header --}}
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <div style="font-size:1.3rem; font-weight:800; color:#1a1d23; letter-spacing:-0.5px;">
                        {{ config('app.name', 'Kore ERP') }}
                    </div>
                    @if($invoice->proposal)
                    <div style="font-size:0.75rem; color:#9ca3af; margin-top:2px;">
                        Contract: {{ $invoice->proposal->ref }}
                        @if($invoice->billingSchedulePeriod)
                            · Period {{ \Carbon\Carbon::parse($invoice->billingSchedulePeriod->period_start)->format('M d') }}–{{ \Carbon\Carbon::parse($invoice->billingSchedulePeriod->period_end)->format('M d, Y') }}
                        @endif
                    </div>
                    @endif
                </div>
                <div class="text-end">
                    <div style="font-size:1.2rem; font-weight:700; color:#4c8bf5;">INVOICE</div>
                    <div style="font-size:0.85rem; color:#374151;">{{ $invoice->invoice_number }}</div>
                    <span class="badge badge-{{ $statusCls }} mt-1">{{ ucfirst($invoice->status) }}</span>
                </div>
            </div>

            {{-- Bill To / Details --}}
            <div class="row mb-4">
                <div class="col-sm-6">
                    <div style="font-size:0.68rem; color:#9ca3af; font-weight:600; text-transform:uppercase; margin-bottom:4px;">Bill To</div>
                    <div class="fw-600" style="font-size:0.88rem;">{{ $invoice->company?->name }}</div>
                    @if($invoice->company?->address_line1)
                    <div style="font-size:0.8rem; color:#6b7280; line-height:1.6;">
                        {{ $invoice->company->address_line1 }}<br>
                        @if($invoice->company->address_line2){{ $invoice->company->address_line2 }}<br>@endif
                        {{ implode(', ', array_filter([$invoice->company->city, $invoice->company->state, $invoice->company->zip])) }}
                    </div>
                    @endif
                </div>
                <div class="col-sm-6 text-sm-end">
                    <div style="font-size:0.8rem; color:#6b7280; line-height:2.2;">
                        <strong>Invoice Date:</strong> {{ $invoice->invoice_date->format('M d, Y') }}<br>
                        <strong>Due Date:</strong>
                        <span style="color:{{ $invoice->isOverdue() ? '#ef4444' : 'inherit' }};">
                            {{ $invoice->due_date?->format('M d, Y') ?? 'On receipt' }}
                        </span><br>
                        @if($invoice->project)
                        <strong>Project:</strong> {{ $invoice->project->title }}<br>
                        @endif
                        <strong>Type:</strong> {{ $invoice->invoice_type_label }}
                    </div>
                </div>
            </div>

            {{-- Line Items --}}
            <div class="table-responsive">
                <table class="table mb-3" style="font-size:0.82rem;">
                    <thead style="background:#f9fafb;">
                        <tr>
                            <th style="font-weight:600; color:#374151;">Description</th>
                            <th class="text-center" style="font-weight:600; color:#374151; width:80px;">Qty / Hrs</th>
                            <th class="text-end"   style="font-weight:600; color:#374151; width:110px;">Unit Price</th>
                            <th class="text-end"   style="font-weight:600; color:#374151; width:110px;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $item)
                        <tr>
                            <td>
                                <div>{{ $item->description }}</div>
                                @if($item->role_name && $item->hours)
                                <div style="font-size:0.72rem; color:#9ca3af;">
                                    {{ $item->role_name }} · {{ $item->hours }}h @ ${{ number_format($item->rate, 2) }}/hr
                                </div>
                                @endif
                            </td>
                            <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                            <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end fw-600">${{ number_format($item->line_total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Totals --}}
            <div class="d-flex justify-content-end">
                <div style="width:240px; font-size:0.82rem;">
                    <div class="d-flex justify-content-between py-1 border-top">
                        <span style="color:#6b7280;">Subtotal</span>
                        <span>${{ number_format($invoice->subtotal, 2) }}</span>
                    </div>
                    @if($invoice->tax_rate > 0)
                    <div class="d-flex justify-content-between py-1">
                        <span style="color:#6b7280;">Tax ({{ number_format($invoice->tax_rate, 2) }}%)</span>
                        <span>${{ number_format($invoice->tax_amount, 2) }}</span>
                    </div>
                    @endif
                    <div class="d-flex justify-content-between py-2 border-top fw-700" style="font-size:1rem;">
                        <span>Total</span>
                        <span style="color:#4c8bf5;">${{ number_format($invoice->total, 2) }}</span>
                    </div>
                    @if($invoice->paid_amount > 0)
                    <div class="d-flex justify-content-between py-1" style="color:#22c55e;">
                        <span>Paid</span>
                        <span>–${{ number_format($invoice->paid_amount, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-top fw-700" style="font-size:0.95rem;">
                        <span>Balance Due</span>
                        <span style="color:{{ $invoice->balance_due > 0 ? '#ef4444' : '#22c55e' }};">
                            ${{ number_format($invoice->balance_due, 2) }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>

            @if($invoice->notes)
            <hr style="border-color:#f3f4f6; margin-top:1.5rem;">
            <div style="font-size:0.78rem; color:#6b7280;">
                <strong>Notes:</strong> {{ $invoice->notes }}
            </div>
            @endif
        </div>

        {{-- ── Payment History ─────────────────────────────────────────────── --}}
        @if($invoice->payments->count() > 0)
        <div class="kore-card mt-4">
            <div class="kore-card-header mb-3">
                <h5><i class="bi bi-credit-card me-2"></i>Payment History</h5>
            </div>
            <table class="table kore-table mb-0" style="font-size:0.8rem;">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Recorded By</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->payments as $pmt)
                    <tr>
                        <td>{{ $pmt->payment_date->format('M d, Y') }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $pmt->payment_method)) }}</td>
                        <td style="color:#6b7280;">{{ $pmt->reference ?? '—' }}</td>
                        <td style="color:#6b7280;">{{ $pmt->recordedBy?->name ?? '—' }}</td>
                        <td class="text-end fw-600" style="color:#22c55e;">${{ number_format($pmt->amount, 2) }}</td>
                    </tr>
                    @if($pmt->notes)
                    <tr>
                        <td colspan="5" style="font-size:0.72rem; color:#9ca3af; padding-top:0;">{{ $pmt->notes }}</td>
                    </tr>
                    @endif
                    @endforeach
                    <tr style="background:#f9fafb; font-weight:700;">
                        <td colspan="4">Total Paid</td>
                        <td class="text-end" style="color:#22c55e;">${{ number_format($invoice->paid_amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ── Sidebar ──────────────────────────────────────────────────────────── --}}
    <div class="col-lg-4">

        {{-- Balance Card --}}
        @if($invoice->status !== 'void')
        <div class="kore-card mb-4" style="background: linear-gradient(135deg, #4c8bf508, #22c55e08);">
            <div style="font-size:0.72rem; font-weight:600; text-transform:uppercase; color:#9ca3af; letter-spacing:.05em; margin-bottom:8px;">Balance Due</div>
            <div style="font-size:2rem; font-weight:800; color:{{ $invoice->balance_due > 0 ? '#ef4444' : '#22c55e' }}; line-height:1;">
                ${{ number_format($invoice->balance_due, 2) }}
            </div>
            <div style="font-size:0.75rem; color:#9ca3af; margin-top:4px;">
                of ${{ number_format($invoice->total, 2) }} total
                @if($invoice->due_date)
                    · Due {{ $invoice->due_date->format('M d, Y') }}
                    @if($invoice->isOverdue())
                        <span style="color:#ef4444;">(overdue)</span>
                    @endif
                @endif
            </div>
            @if($invoice->balance_due > 0 && !in_array($invoice->status, ['draft', 'void']))
            <button type="button" class="btn btn-success btn-sm w-100 mt-3" data-bs-toggle="modal" data-bs-target="#paymentModal">
                <i class="bi bi-credit-card me-2"></i> Apply Payment
            </button>
            @endif
        </div>
        @endif

        {{-- Actions --}}
        <div class="kore-card mb-4">
            <div class="fw-600 mb-3" style="font-size:0.72rem; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em;">Actions</div>
            <div class="d-grid gap-2">
                <a href="{{ route('invoices.preview', $invoice) }}" target="_blank" class="btn btn-sm btn-outline-secondary text-start">
                    <i class="bi bi-eye me-2"></i> Preview Invoice
                </a>
                <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="btn btn-sm btn-outline-secondary text-start">
                    <i class="bi bi-file-pdf me-2"></i> Download PDF
                </a>
                @if($invoice->status === 'draft')
                <form action="{{ route('invoices.send', $invoice) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-primary text-start w-100">
                        <i class="bi bi-send me-2"></i> Mark as Sent
                    </button>
                </form>
                @endif
                @if(!in_array($invoice->status, ['paid', 'void', 'draft']))
                <button type="button" class="btn btn-sm btn-success text-start" data-bs-toggle="modal" data-bs-target="#paymentModal">
                    <i class="bi bi-credit-card me-2"></i> Apply Payment
                </button>
                @endif
                @if(!in_array($invoice->status, ['paid', 'void']))
                <form action="{{ route('invoices.mark-paid', $invoice) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-success text-start w-100"
                        onclick="return confirm('Mark entire invoice as paid in full?')">
                        <i class="bi bi-check-circle me-2"></i> Mark as Paid in Full
                    </button>
                </form>
                @endif
                @if($canEdit)
                <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-sm btn-outline-secondary text-start">
                    <i class="bi bi-pencil me-2"></i> Edit Invoice
                </a>
                @endif
                @if(!in_array($invoice->status, ['paid', 'void']))
                <form action="{{ route('invoices.void', $invoice) }}" method="POST"
                    onsubmit="return confirm('Void this invoice? This cannot be undone.')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-warning text-start w-100">
                        <i class="bi bi-slash-circle me-2"></i> Void Invoice
                    </button>
                </form>
                @endif
                @if(!in_array($invoice->status, ['paid']))
                <form action="{{ route('invoices.destroy', $invoice) }}" method="POST"
                    onsubmit="return confirm('Permanently delete {{ $invoice->invoice_number }}?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger text-start w-100">
                        <i class="bi bi-trash me-2"></i> Delete
                    </button>
                </form>
                @endif
            </div>
        </div>

        {{-- Details --}}
        <div class="kore-card mb-4">
            <div class="fw-600 mb-2" style="font-size:0.72rem; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em;">Invoice Details</div>
            <div style="font-size:0.8rem; line-height:2.2; color:#6b7280;">
                <div><strong>Created by:</strong> {{ $invoice->createdBy?->name ?? '—' }}</div>
                <div><strong>Created:</strong> {{ $invoice->created_at->format('M d, Y') }}</div>
                @if($invoice->sent_at)
                <div><strong>Sent:</strong> {{ $invoice->sent_at->format('M d, Y') }}</div>
                @endif
                @if($invoice->paid_at)
                <div><strong>Fully Paid:</strong> {{ $invoice->paid_at->format('M d, Y') }}</div>
                @endif
                <div><strong>Type:</strong> {{ $invoice->invoice_type_label }}</div>
            </div>
        </div>

        {{-- Linked Contract --}}
        @if($invoice->proposal)
        <div class="kore-card">
            <div class="fw-600 mb-2" style="font-size:0.72rem; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em;">Linked Contract</div>
            <div style="font-size:0.82rem;">
                <a href="{{ route('proposals.show', $invoice->proposal) }}" class="fw-600 text-decoration-none" style="color:#4c8bf5;">
                    {{ $invoice->proposal->ref }}
                </a>
                <div style="color:#6b7280; margin-top:2px;">{{ $invoice->proposal->title }}</div>
                <div style="font-size:0.72rem; color:#9ca3af; margin-top:4px;">
                    {{ ucwords(str_replace('_', ' ', $invoice->proposal->billing_type)) }}
                    · ${{ number_format($invoice->proposal->contract_value ?? 0, 0) }} contract
                </div>
                <a href="{{ route('proposals.show', $invoice->proposal) }}#tab-billing" class="btn btn-sm btn-outline-secondary w-100 mt-2" style="font-size:0.75rem;">
                    <i class="bi bi-calendar3 me-1"></i> View Billing Schedule
                </a>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- ── Apply Payment Modal ──────────────────────────────────────────────────── --}}
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-size:0.9rem;">
                    <i class="bi bi-credit-card me-2"></i>Apply Payment — {{ $invoice->invoice_number }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('invoices.payment', $invoice) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label" style="font-size:0.78rem; font-weight:600;">Amount</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">$</span>
                                <input type="number" name="amount" class="form-control" step="0.01" min="0.01"
                                    max="{{ $invoice->balance_due }}"
                                    value="{{ number_format($invoice->balance_due, 2, '.', '') }}" required>
                            </div>
                            <div class="form-text" style="font-size:0.7rem;">Balance due: ${{ number_format($invoice->balance_due, 2) }}</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" style="font-size:0.78rem; font-weight:600;">Payment Date</label>
                            <input type="date" name="payment_date" class="form-control form-control-sm"
                                value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" style="font-size:0.78rem; font-weight:600;">Method</label>
                            <select name="payment_method" class="form-select form-select-sm" required>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="wire">Wire Transfer</option>
                                <option value="ach">ACH</option>
                                <option value="check">Check</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" style="font-size:0.78rem; font-weight:600;">Reference / Check #</label>
                            <input type="text" name="reference" class="form-control form-control-sm" placeholder="Optional">
                        </div>
                        <div class="col-12">
                            <label class="form-label" style="font-size:0.78rem; font-weight:600;">Notes</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Optional notes"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="bi bi-check-circle me-1"></i> Apply Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
