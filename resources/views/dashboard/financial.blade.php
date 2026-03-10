@extends('layouts.app')

@section('content')

{{-- Dashboard Tab Navigation --}}
<nav class="dashboard-tabs">
    <ul class="nav nav-pills gap-1">
        <li class="nav-item">
            <a class="nav-link" href="{{ route('dashboard') }}">
                <i class="bi bi-person me-1"></i> My Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('dashboard.business') }}">
                <i class="bi bi-briefcase me-1"></i> Business
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('dashboard.financial') }}">
                <i class="bi bi-currency-dollar me-1"></i> Financial
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('dashboard.kpi') }}">
                <i class="bi bi-graph-up me-1"></i> KPIs
            </a>
        </li>
    </ul>
</nav>

{{-- Financial Stat Row --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon text-success"><i class="bi bi-check-circle"></i></div>
            <div class="stat-value">${{ number_format($totalPaid, 0) }}</div>
            <div class="stat-label">Total Paid</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon text-primary"><i class="bi bi-send"></i></div>
            <div class="stat-value">${{ number_format($totalBilled, 0) }}</div>
            <div class="stat-label">Outstanding (Sent)</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon text-danger"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="stat-value">${{ number_format($totalOverdue, 0) }}</div>
            <div class="stat-label">Overdue</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon text-secondary"><i class="bi bi-file-earmark"></i></div>
            <div class="stat-value">${{ number_format($totalDraft, 0) }}</div>
            <div class="stat-label">Draft Invoices</div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Monthly Revenue Chart --}}
    <div class="col-lg-7">
        <div class="kore-card">
            <div class="kore-card-header">
                <h5><i class="bi bi-graph-up-arrow me-2"></i>Monthly Revenue (Last 6 Months)</h5>
            </div>
            <canvas id="revenueChart" height="220"></canvas>
        </div>
    </div>

    {{-- Invoice Status Breakdown --}}
    <div class="col-lg-5">
        <div class="kore-card">
            <div class="kore-card-header">
                <h5><i class="bi bi-pie-chart me-2"></i>Invoice Breakdown</h5>
            </div>
            @php
                $total = $totalPaid + $totalBilled + $totalOverdue + $totalDraft;
            @endphp
            @foreach([['Paid', $totalPaid, 'approved'], ['Sent', $totalBilled, 'active'], ['Overdue', $totalOverdue, 'rejected'], ['Draft', $totalDraft, 'draft']] as [$label, $amount, $cls])
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge badge-{{ $cls }}">{{ $label }}</span>
                </div>
                <div class="text-end">
                    <div style="font-size:0.85rem; font-weight:600;">${{ number_format($amount, 0) }}</div>
                    <div style="font-size:0.7rem; color:#9ca3af;">
                        {{ $total > 0 ? number_format(($amount / $total) * 100, 1) : 0 }}%
                    </div>
                </div>
            </div>
            @endforeach
            <div class="mt-3">
                <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-receipt me-1"></i> View All Invoices
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Recent Invoices --}}
<div class="kore-card mt-4">
    <div class="kore-card-header">
        <h5><i class="bi bi-receipt me-2"></i>Recent Invoices</h5>
        <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <table class="table kore-table mb-0">
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Client</th>
                <th>Amount</th>
                <th>Due Date</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($recentInvoices as $inv)
            <tr>
                <td>
                    <a href="{{ route('invoices.show', $inv->id) }}" class="text-decoration-none fw-600" style="font-size:0.8rem;">
                        {{ $inv->invoice_number ?? ('INV-' . str_pad($inv->id, 4, '0', STR_PAD_LEFT)) }}
                    </a>
                </td>
                <td>{{ $inv->company_name }}</td>
                <td>${{ number_format($inv->total, 2) }}</td>
                <td>{{ $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->format('M d, Y') : '—' }}</td>
                <td>
                    @php
                        $cls = match($inv->status) {
                            'paid'    => 'approved',
                            'sent'    => 'active',
                            'overdue' => 'rejected',
                            default   => 'draft',
                        };
                    @endphp
                    <span class="badge badge-{{ $cls }}">{{ ucfirst($inv->status) }}</span>
                </td>
                <td>
                    <a href="{{ route('invoices.show', $inv->id) }}" class="btn btn-xs btn-outline-secondary" style="font-size:0.72rem; padding:2px 8px;">View</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-3">No invoices yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
const monthlyData = @json($monthlyRevenue);
const labels = Object.keys(monthlyData);
const values = Object.values(monthlyData);

new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Revenue ($)',
            data: values,
            backgroundColor: '#4c8bf5',
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: val => '$' + val.toLocaleString()
                }
            }
        }
    }
});
</script>
@endpush
