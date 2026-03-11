<div class="billing-period-row header">
    <div>Period Start</div>
    <div>Period End</div>
    <div>Fees</div>
    <div>Expenses</div>
    <div>Total</div>
    <div>Status</div>
    <div>Invoice #</div>
    <div></div>
</div>

@foreach($periods as $period)
<div class="billing-period-row">
    <div>{{ $period->period_start->format('Y-m-d') }}</div>
    <div>{{ $period->period_end->format('Y-m-d') }}</div>
    <div>${{ number_format($period->fees_amount, 2) }}</div>
    <div>${{ number_format($period->expenses_amount, 2) }}</div>
    <div><strong>${{ number_format($period->total_amount, 2) }}</strong></div>
    <div>
        <span class="badge bg-{{ $period->status_color }}">
            {{ $period->is_locked ? '🔒 ' : '' }}{{ $period->status_label }}
        </span>
    </div>
    <div style="font-size:0.75rem; color:#6b7280;">{{ $period->invoice_number ?? '—' }}</div>
    <div>
        @if(!$period->is_locked)
            <button class="btn btn-link btn-sm p-0 text-danger"
                    onclick="deletePeriod({{ $period->id }})"
                    title="Delete">
                <i class="bi bi-trash" style="font-size:0.75rem;"></i>
            </button>
        @endif
    </div>
</div>
@endforeach
