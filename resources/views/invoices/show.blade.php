@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h4 class="mb-0 fw-700" style="font-size:1rem;">{{ $invoice->invoice_number }}</h4>
            <div style="font-size:0.75rem; color:#6b7280;">
                {{ $invoice->company?->name }} · {{ $invoice->invoice_date->format('M d, Y') }}
            </div>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
            <i class="bi bi-file-pdf me-1"></i> PDF
        </a>
        @if($invoice->status !== 'paid')
        <form action="{{ route('invoices.send', $invoice) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-send me-1"></i> Mark Sent
            </button>
        </form>
        <form action="{{ route('invoices.mark-paid', $invoice) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-success"
                onclick="return confirm('Mark as paid?')">
                <i class="bi bi-check-circle me-1"></i> Mark Paid
            </button>
        </form>
        <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        @endif
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
        {{-- Invoice body --}}
        <div class="kore-card">
            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <div style="font-size:1.4rem; font-weight:700; color:#1a1d23;">
                        {{ config('app.name', 'Kore ERP') }}
                    </div>
                </div>
                <div class="text-end">
                    <div style="font-size:1.2rem; font-weight:700; color:#4c8bf5;">INVOICE</div>
                    <div style="font-size:0.85rem; color:#374151;">{{ $invoice->invoice_number }}</div>
                    @php $cls = match($invoice->status){
                        'sent'=>'active','paid'=>'approved','overdue'=>'rejected',default=>'draft'
                    }; @endphp
                    <span class="badge badge-{{ $cls }} mt-1">{{ ucfirst($invoice->status) }}</span>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-sm-6">
                    <div style="font-size:0.72rem; color:#9ca3af; font-weight:600; text-transform:uppercase; margin-bottom:4px;">Bill To</div>
                    <div class="fw-600" style="font-size:0.88rem;">{{ $invoice->company?->name }}</div>
                    @if($invoice->company?->address_line1)
                    <div style="font-size:0.8rem; color:#6b7280; line-height:1.5;">
                        {{ $invoice->company->address_line1 }}<br>
                        @if($invoice->company->address_line2){{ $invoice->company->address_line2 }}<br>@endif
                        {{ implode(', ', array_filter([$invoice->company->city, $invoice->company->state, $invoice->company->zip])) }}
                    </div>
                    @endif
                </div>
                <div class="col-sm-6 text-sm-end">
                    <div style="font-size:0.8rem; color:#6b7280; line-height:2;">
                        <strong>Date:</strong> {{ $invoice->invoice_date->format('M d, Y') }}<br>
                        <strong>Due:</strong> {{ $invoice->due_date?->format('M d, Y') ?? 'On receipt' }}<br>
                        <strong>Project:</strong> {{ $invoice->project?->title }}
                    </div>
                </div>
            </div>

            {{-- Items --}}
            <table class="table mb-3" style="font-size:0.82rem;">
                <thead style="background:#f9fafb;">
                    <tr>
                        <th style="font-weight:600; color:#374151;">Description</th>
                        <th class="text-center" style="font-weight:600; color:#374151; width:80px;">Qty</th>
                        <th class="text-end" style="font-weight:600; color:#374151; width:100px;">Unit Price</th>
                        <th class="text-end" style="font-weight:600; color:#374151; width:100px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                        <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-end fw-600">${{ number_format($item->line_total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Totals --}}
            <div class="d-flex justify-content-end">
                <div style="width:220px; font-size:0.82rem;">
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
                    @if($invoice->status === 'paid')
                    <div class="d-flex justify-content-between py-1" style="color:#22c55e;">
                        <span>Paid</span>
                        <span>${{ number_format($invoice->paid_amount, 2) }}</span>
                    </div>
                    @endif
                </div>
            </div>

            @if($invoice->notes)
            <hr style="border-color:#f3f4f6;">
            <div style="font-size:0.78rem; color:#6b7280;">
                <strong>Notes:</strong> {{ $invoice->notes }}
            </div>
            @endif
        </div>
    </div>

    <div class="col-lg-4">
        <div class="kore-card mb-4">
            <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">Actions</div>
            <div class="d-grid gap-2">
                <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-sm btn-outline-secondary text-start" target="_blank">
                    <i class="bi bi-file-pdf me-2"></i> Download PDF
                </a>
                @if($invoice->status !== 'paid')
                <form action="{{ route('invoices.send', $invoice) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-primary text-start w-100">
                        <i class="bi bi-send me-2"></i> Mark as Sent
                    </button>
                </form>
                <form action="{{ route('invoices.mark-paid', $invoice) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success text-start w-100"
                        onclick="return confirm('Mark as paid?')">
                        <i class="bi bi-check-circle me-2"></i> Mark as Paid
                    </button>
                </form>
                <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-sm btn-outline-secondary text-start">
                    <i class="bi bi-pencil me-2"></i> Edit Invoice
                </a>
                <form action="{{ route('invoices.destroy', $invoice) }}" method="POST"
                    onsubmit="return confirm('Delete this invoice?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger text-start w-100">
                        <i class="bi bi-trash me-2"></i> Delete
                    </button>
                </form>
                @endif
            </div>
        </div>

        <div class="kore-card">
            <div class="fw-600 mb-2" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">Details</div>
            <div style="font-size:0.8rem; line-height:2; color:#6b7280;">
                <div><strong>Created by:</strong> {{ $invoice->createdBy?->full_name ?? '—' }}</div>
                <div><strong>Created:</strong> {{ $invoice->created_at->format('M d, Y') }}</div>
                @if($invoice->sent_at)
                <div><strong>Sent:</strong> {{ $invoice->sent_at->format('M d, Y') }}</div>
                @endif
                @if($invoice->paid_at)
                <div><strong>Paid:</strong> {{ $invoice->paid_at->format('M d, Y') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
