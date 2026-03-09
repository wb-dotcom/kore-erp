<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1d23; line-height: 1.5; }
    .page { padding: 40px; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; }
    .company-name { font-size: 20px; font-weight: 700; color: #1a1d23; }
    .invoice-label { font-size: 16px; font-weight: 700; color: #4c8bf5; text-align: right; }
    .invoice-number { font-size: 12px; color: #374151; text-align: right; }
    .addresses { display: flex; justify-content: space-between; margin-bottom: 24px; }
    .address-block { width: 48%; }
    .label { font-size: 9px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
    .name { font-size: 12px; font-weight: 700; }
    .meta { font-size: 11px; color: #6b7280; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    thead tr { background: #f9fafb; }
    th { padding: 8px 10px; font-weight: 600; color: #374151; font-size: 10px; text-transform: uppercase; border-bottom: 2px solid #e5e7eb; }
    td { padding: 8px 10px; border-bottom: 1px solid #f3f4f6; font-size: 11px; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .totals-table { width: 220px; float: right; }
    .totals-table td { padding: 4px 8px; border: none; }
    .total-row td { font-weight: 700; font-size: 13px; border-top: 2px solid #e5e7eb; padding-top: 8px; }
    .grand-total { color: #4c8bf5; }
    .status-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; }
    .status-paid { background: #dcfce7; color: #166534; }
    .status-sent { background: #dbeafe; color: #1e40af; }
    .status-overdue { background: #fee2e2; color: #991b1b; }
    .status-draft { background: #f3f4f6; color: #374151; }
    .notes { margin-top: 24px; font-size: 10px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 12px; }
    .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 12px; }
    .clearfix::after { content: ''; display: table; clear: both; }
</style>
</head>
<body>
<div class="page">

    <div class="header">
        <div>
            <div class="company-name">{{ config('app.name', 'Kore ERP') }}</div>
        </div>
        <div>
            <div class="invoice-label">INVOICE</div>
            <div class="invoice-number">{{ $invoice->invoice_number }}</div>
            <div style="margin-top:4px;">
                @php $cls = match($invoice->status){
                    'sent'=>'sent','paid'=>'paid','overdue'=>'overdue',default=>'draft'
                }; @endphp
                <span class="status-badge status-{{ $cls }}">{{ strtoupper($invoice->status) }}</span>
            </div>
        </div>
    </div>

    <div class="addresses">
        <div class="address-block">
            <div class="label">Bill To</div>
            <div class="name">{{ $invoice->company?->name }}</div>
            @if($invoice->company?->address_line1)
            <div class="meta">{{ $invoice->company->address_line1 }}</div>
            @if($invoice->company->address_line2)
            <div class="meta">{{ $invoice->company->address_line2 }}</div>
            @endif
            <div class="meta">{{ implode(', ', array_filter([$invoice->company->city, $invoice->company->state, $invoice->company->zip])) }}</div>
            @endif
        </div>
        <div class="address-block" style="text-align:right;">
            <div class="label">Invoice Details</div>
            <div class="meta">Date: {{ $invoice->invoice_date->format('M d, Y') }}</div>
            <div class="meta">Due: {{ $invoice->due_date?->format('M d, Y') ?? 'On receipt' }}</div>
            <div class="meta">Project: {{ $invoice->project?->title }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-center" style="width:70px;">Qty</th>
                <th class="text-right" style="width:90px;">Unit Price</th>
                <th class="text-right" style="width:90px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right" style="font-weight:600;">${{ number_format($item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="clearfix">
        <table class="totals-table">
            <tr>
                <td style="color:#6b7280;">Subtotal</td>
                <td class="text-right">${{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
            @if($invoice->tax_rate > 0)
            <tr>
                <td style="color:#6b7280;">Tax ({{ number_format($invoice->tax_rate, 2) }}%)</td>
                <td class="text-right">${{ number_format($invoice->tax_amount, 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>Total Due</td>
                <td class="text-right grand-total">${{ number_format($invoice->total, 2) }}</td>
            </tr>
        </table>
    </div>

    @if($invoice->notes)
    <div class="notes"><strong>Notes:</strong> {{ $invoice->notes }}</div>
    @endif

    <div class="footer">
        Generated by {{ config('app.name', 'Kore ERP') }} · {{ now()->format('M d, Y') }}
    </div>

</div>
</body>
</html>
