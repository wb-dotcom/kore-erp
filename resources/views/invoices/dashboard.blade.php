@extends('layouts.app')

@section('content')

{{-- ── Page Header ─────────────────────────────────────────────────────────── --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">Invoice Control Center</h4>
        <div style="font-size:0.72rem; color:#9ca3af;">AR / AP overview — all billing, invoices & payments</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-list-ul me-1"></i> All Invoices
        </a>
        <a href="{{ route('invoices.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Invoice
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

{{-- ── KPI Strip ───────────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    @php
    $kpiCards = [
        ['label'=>'Total Invoiced',   'value'=>$kpis['total_invoiced'],    'color'=>'#4c8bf5', 'icon'=>'bi-receipt',         'sub'=>$kpis['sent_count'].' sent'],
        ['label'=>'Collected',        'value'=>$kpis['total_paid'],        'color'=>'#22c55e', 'icon'=>'bi-check-circle',     'sub'=>$kpis['paid_count'].' paid'],
        ['label'=>'Outstanding',      'value'=>$kpis['total_outstanding'], 'color'=>'#f59e0b', 'icon'=>'bi-clock-history',    'sub'=>$kpis['sent_count'].' invoices'],
        ['label'=>'Overdue',          'value'=>$kpis['total_overdue'],     'color'=>'#ef4444', 'icon'=>'bi-exclamation-triangle', 'sub'=>$kpis['overdue_count'].' invoices'],
    ];
    @endphp
    @foreach($kpiCards as $card)
    <div class="col-sm-6 col-xl-3">
        <div class="kore-card h-100">
            <div class="d-flex align-items-center gap-3">
                <div style="width:44px; height:44px; border-radius:12px; background:{{ $card['color'] }}18;
                            display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i class="bi {{ $card['icon'] }}" style="font-size:1.2rem; color:{{ $card['color'] }};"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div style="font-size:1.25rem; font-weight:800; color:{{ $card['color'] }}; line-height:1.1;">
                        ${{ number_format($card['value'], 0) }}
                    </div>
                    <div style="font-size:0.72rem; font-weight:600; color:#9ca3af; text-transform:uppercase; letter-spacing:.05em;">{{ $card['label'] }}</div>
                    <div style="font-size:0.72rem; color:#9ca3af; margin-top:2px;">{{ $card['sub'] }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-4 mb-4">
    {{-- ── AR Aging ──────────────────────────────────────────────────────────── --}}
    <div class="col-lg-5">
        <div class="kore-card h-100">
            <div class="kore-card-header mb-3">
                <h5><i class="bi bi-bar-chart-steps me-2"></i>AR Aging Report</h5>
            </div>
            @php
            $agingBuckets = [
                ['label' => 'Current (not yet due)',  'value' => $aging['current'], 'color' => '#22c55e', 'bar_color' => '#22c55e'],
                ['label' => '1–30 days overdue',      'value' => $aging['1_30'],    'color' => '#f59e0b', 'bar_color' => '#f59e0b'],
                ['label' => '31–60 days overdue',     'value' => $aging['31_60'],   'color' => '#f97316', 'bar_color' => '#f97316'],
                ['label' => '61–90 days overdue',     'value' => $aging['61_90'],   'color' => '#ef4444', 'bar_color' => '#ef4444'],
                ['label' => '90+ days overdue',       'value' => $aging['90_plus'], 'color' => '#7f1d1d', 'bar_color' => '#7f1d1d'],
            ];
            $agingTotal = array_sum(array_column($agingBuckets, 'value'));
            @endphp
            @foreach($agingBuckets as $bucket)
            @php $pct = $agingTotal > 0 ? round(($bucket['value'] / $agingTotal) * 100) : 0; @endphp
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span style="font-size:0.78rem; color:#6b7280;">{{ $bucket['label'] }}</span>
                    <span style="font-size:0.82rem; font-weight:700; color:{{ $bucket['color'] }};">${{ number_format($bucket['value'], 0) }}</span>
                </div>
                <div style="height:6px; background:#f3f4f6; border-radius:99px; overflow:hidden;">
                    <div style="width:{{ $pct }}%; height:100%; background:{{ $bucket['bar_color'] }}; border-radius:99px; transition:width 0.4s;"></div>
                </div>
            </div>
            @endforeach
            <div class="d-flex justify-content-between pt-2 border-top mt-3" style="font-size:0.8rem;">
                <span style="color:#6b7280; font-weight:600;">Total Outstanding</span>
                <span style="font-weight:800; color:#374151;">${{ number_format($agingTotal, 0) }}</span>
            </div>
        </div>
    </div>

    {{-- ── Revenue Trend ──────────────────────────────────────────────────────── --}}
    <div class="col-lg-7">
        <div class="kore-card h-100">
            <div class="kore-card-header mb-3">
                <h5><i class="bi bi-graph-up-arrow me-2"></i>Revenue Collected (12 months)</h5>
            </div>
            <div style="height:180px; position:relative;">
                <canvas id="revenueTrendChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- ── Status Breakdown ──────────────────────────────────────────────────── --}}
    <div class="col-lg-4">
        <div class="kore-card h-100">
            <div class="kore-card-header mb-3">
                <h5><i class="bi bi-pie-chart me-2"></i>Invoice Status</h5>
            </div>
            <div style="height:160px; position:relative;">
                <canvas id="statusDonut"></canvas>
            </div>
            <div class="d-flex flex-wrap gap-2 justify-content-center mt-3">
                @php
                $statusBadges = [
                    ['draft',   '#6b7280', $kpis['draft_count']],
                    ['sent',    '#4c8bf5', $kpis['sent_count']],
                    ['overdue', '#ef4444', $kpis['overdue_count']],
                    ['paid',    '#22c55e', $kpis['paid_count']],
                ];
                @endphp
                @foreach($statusBadges as [$label, $color, $count])
                <div style="display:flex; align-items:center; gap:5px; font-size:0.75rem; color:#6b7280;">
                    <span style="width:8px; height:8px; border-radius:50%; background:{{ $color }}; flex-shrink:0;"></span>
                    {{ ucfirst($label) }} ({{ $count }})
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Top Clients Outstanding ────────────────────────────────────────────── --}}
    <div class="col-lg-8">
        <div class="kore-card h-100">
            <div class="kore-card-header mb-3">
                <h5><i class="bi bi-people me-2"></i>Top Clients by Outstanding Balance</h5>
            </div>
            @forelse($topClients as $client)
            <div class="d-flex align-items-center gap-3 py-2 border-bottom">
                <div style="width:36px; height:36px; border-radius:50%; background:#ede9fe; display:flex; align-items:center; justify-content:center;
                            font-size:0.75rem; font-weight:700; color:#7c3aed; flex-shrink:0;">
                    {{ strtoupper(substr($client->company?->name ?? '?', 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div style="font-size:0.82rem; font-weight:600; color:#374151;">{{ $client->company?->name ?? 'Unknown' }}</div>
                    <div style="font-size:0.72rem; color:#9ca3af;">{{ $client->count }} invoice{{ $client->count > 1 ? 's' : '' }} outstanding</div>
                </div>
                <div style="font-size:0.9rem; font-weight:700; color:#ef4444;">${{ number_format($client->outstanding, 0) }}</div>
                <a href="{{ route('invoices.index', ['search' => $client->company?->name]) }}"
                   class="btn btn-sm btn-outline-secondary" style="font-size:0.72rem;">
                    View
                </a>
            </div>
            @empty
            <div class="text-center py-4" style="font-size:0.82rem; color:#9ca3af;">
                <i class="bi bi-check-circle fs-4 d-block mb-2 text-success"></i>
                No outstanding balances
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- ── Recent Invoices ──────────────────────────────────────────────────────── --}}
<div class="kore-card">
    <div class="kore-card-header mb-3">
        <h5><i class="bi bi-clock-history me-2"></i>Recent Invoices</h5>
        <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-outline-secondary ms-auto" style="font-size:0.75rem;">View All</a>
    </div>
    <table class="table kore-table mb-0">
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Client</th>
                <th>Contract / Project</th>
                <th>Type</th>
                <th>Date</th>
                <th>Due</th>
                <th class="text-end">Total</th>
                <th class="text-end">Balance</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($recentInvoices as $inv)
            @php
            $statusCls = match($inv->status) {
                'sent'    => 'active',
                'paid'    => 'approved',
                'overdue' => 'rejected',
                'partial' => 'warning',
                'void'    => 'secondary',
                default   => 'draft',
            };
            @endphp
            <tr>
                <td>
                    <a href="{{ route('invoices.show', $inv) }}" class="fw-600 text-decoration-none" style="color:#4c8bf5; font-size:0.82rem;">
                        {{ $inv->invoice_number }}
                    </a>
                </td>
                <td style="font-size:0.8rem;">{{ $inv->company?->name ?? '—' }}</td>
                <td style="font-size:0.78rem; color:#6b7280; max-width:160px;">
                    @if($inv->proposal)
                        <a href="{{ route('proposals.show', $inv->proposal) }}" class="text-decoration-none" style="color:#6b7280;">
                            {{ $inv->proposal->ref }}
                        </a>
                    @elseif($inv->project)
                        <span class="text-truncate d-block">{{ $inv->project->title }}</span>
                    @else
                        —
                    @endif
                </td>
                <td>
                    <span style="font-size:0.72rem; background:#f3f4f6; padding:2px 7px; border-radius:99px; color:#6b7280;">
                        {{ $inv->invoice_type_label }}
                    </span>
                </td>
                <td style="font-size:0.78rem; color:#6b7280;">{{ $inv->invoice_date->format('M d, Y') }}</td>
                <td style="font-size:0.78rem; color:{{ $inv->isOverdue() ? '#ef4444' : '#6b7280' }};">
                    {{ $inv->due_date?->format('M d, Y') ?? '—' }}
                </td>
                <td class="text-end fw-600" style="font-size:0.85rem;">${{ number_format($inv->total, 2) }}</td>
                <td class="text-end" style="font-size:0.85rem; color:{{ $inv->balance_due > 0 ? '#ef4444' : '#22c55e' }}; font-weight:600;">
                    ${{ number_format($inv->balance_due, 2) }}
                </td>
                <td><span class="badge badge-{{ $statusCls }}">{{ ucfirst($inv->status) }}</span></td>
                <td>
                    <a href="{{ route('invoices.show', $inv) }}" class="btn btn-sm btn-outline-secondary" style="font-size:0.72rem;">
                        View
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center py-4" style="color:#9ca3af;">
                    No invoices yet. <a href="{{ route('invoices.create') }}">Create your first invoice</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Revenue Trend ──────────────────────────────────────────────────────
    const trendData = @json($trend);
    const labels    = trendData.map(r => r.month);
    const values    = trendData.map(r => parseFloat(r.total));

    new Chart(document.getElementById('revenueTrendChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Revenue Collected',
                data: values,
                backgroundColor: '#4c8bf522',
                borderColor: '#4c8bf5',
                borderWidth: 2,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                y: { grid: { color: '#f3f4f6' }, ticks: { font: { size: 10 }, callback: v => '$' + (v/1000).toFixed(0) + 'k' } },
            }
        }
    });

    // ── Status Donut ───────────────────────────────────────────────────────
    new Chart(document.getElementById('statusDonut'), {
        type: 'doughnut',
        data: {
            labels: ['Draft', 'Sent/Partial', 'Overdue', 'Paid'],
            datasets: [{
                data: [{{ $kpis['draft_count'] }}, {{ $kpis['sent_count'] }}, {{ $kpis['overdue_count'] }}, {{ $kpis['paid_count'] }}],
                backgroundColor: ['#d1d5db', '#4c8bf5', '#ef4444', '#22c55e'],
                borderWidth: 0, hoverOffset: 4,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            cutout: '70%',
            plugins: { legend: { display: false } }
        }
    });
});
</script>
@endpush
