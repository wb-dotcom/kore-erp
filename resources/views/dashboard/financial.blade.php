@extends('layouts.app')

@push('styles')
<style>
/* ── Financial Dashboard ───────────────────────────────── */
.invoice-breakdown-row {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 0; border-bottom: 1px solid #f3f4f6;
}
.invoice-breakdown-row:last-child { border-bottom: none; }
.inv-color-dot {
    width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
}
.inv-bar-wrap { flex: 1; }
.inv-bar-bg {
    height: 6px; background: #f3f4f6; border-radius: 6px; overflow: hidden;
}
.inv-bar-fill { height: 100%; border-radius: 6px; transition: width 0.8s cubic-bezier(0.4,0,0.2,1); }

/* Revenue chart period selector */
.period-selector { display: flex; gap: 4px; }
.period-btn {
    padding: 5px 12px; border-radius: 8px; border: 1.5px solid #e5e7eb;
    background: #fff; font-size: 0.72rem; font-weight: 500; color: #6b7280;
    cursor: pointer; transition: all 0.15s;
}
.period-btn.active { background: #4c8bf5; border-color: #4c8bf5; color: #fff; }

/* Invoice table search */
.inv-search-wrap { position: relative; }
.inv-search-wrap input {
    border: 1.5px solid #e5e7eb; border-radius: 9px;
    padding: 6px 12px 6px 32px; font-size: 0.78rem; outline: none;
    transition: border-color 0.2s;
}
.inv-search-wrap input:focus { border-color: #4c8bf5; }
.inv-search-wrap i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 0.8rem; }
</style>
@endpush

@section('content')

{{-- Welcome --}}
<div style="margin-bottom:26px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h2 style="font-size:22px;font-weight:800;color:var(--c-t1);letter-spacing:-0.5px;margin:0 0 2px;">Financial Overview</h2>
            <p style="font-size:13.5px;color:var(--c-t3);margin:0;">Revenue, invoices and financial performance — {{ now()->format('F Y') }}</p>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus me-1"></i> New Invoice</a>
        </div>
    </div>
</div>

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
        <div class="stat-card accent-green h-100">
            <div class="stat-card-top">
                <div class="stat-icon-wrap" style="background:rgba(34,197,94,0.1);">
                    <i class="bi bi-check-circle" style="color:#22c55e;"></i>
                </div>
                <span style="font-size:0.65rem;padding:3px 8px;border-radius:20px;background:#d1fae5;color:#065f46;font-weight:600;">Paid</span>
            </div>
            <div class="stat-value" style="color:#22c55e;">${{ number_format($totalPaid, 0) }}</div>
            <div class="stat-label">Total Collected</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card accent-blue h-100">
            <div class="stat-card-top">
                <div class="stat-icon-wrap" style="background:rgba(76,139,245,0.1);">
                    <i class="bi bi-send" style="color:#4c8bf5;"></i>
                </div>
                <span style="font-size:0.65rem;padding:3px 8px;border-radius:20px;background:#dbeafe;color:#1e40af;font-weight:600;">Sent</span>
            </div>
            <div class="stat-value" style="color:#4c8bf5;">${{ number_format($totalBilled, 0) }}</div>
            <div class="stat-label">Outstanding</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card accent-red h-100">
            <div class="stat-card-top">
                <div class="stat-icon-wrap" style="background:rgba(239,68,68,0.1);">
                    <i class="bi bi-exclamation-triangle" style="color:#ef4444;"></i>
                </div>
                <span style="font-size:0.65rem;padding:3px 8px;border-radius:20px;background:#fee2e2;color:#991b1b;font-weight:600;">Overdue</span>
            </div>
            <div class="stat-value" style="color:#ef4444;">${{ number_format($totalOverdue, 0) }}</div>
            <div class="stat-label">Overdue Invoices</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card h-100">
            <div class="stat-card-top">
                <div class="stat-icon-wrap" style="background:rgba(107,114,128,0.1);">
                    <i class="bi bi-file-earmark" style="color:#6b7280;"></i>
                </div>
                <span style="font-size:0.65rem;padding:3px 8px;border-radius:20px;background:#f3f4f6;color:#374151;font-weight:600;">Draft</span>
            </div>
            <div class="stat-value" style="color:#6b7280;">${{ number_format($totalDraft, 0) }}</div>
            <div class="stat-label">Draft Invoices</div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Monthly Revenue Chart --}}
    <div class="col-lg-7">
        <div class="kore-card">
            <div class="kore-card-header">
                <h5><i class="bi bi-graph-up-arrow me-2" style="color:#4c8bf5;"></i>Monthly Revenue</h5>
                <div class="period-selector">
                    <button class="period-btn" onclick="setPeriod(3, this)">3M</button>
                    <button class="period-btn active" id="btn6m" onclick="setPeriod(6, this)">6M</button>
                    <button class="period-btn" onclick="setPeriod(12, this)">12M</button>
                </div>
            </div>
            <canvas id="revenueChart" height="200"></canvas>
        </div>
    </div>

    {{-- Invoice Breakdown --}}
    <div class="col-lg-5">
        <div class="kore-card">
            <div class="kore-card-header">
                <h5><i class="bi bi-pie-chart me-2" style="color:#8b5cf6;"></i>Invoice Breakdown</h5>
            </div>

            {{-- Mini doughnut + breakdown list --}}
            <div style="position:relative;max-width:160px;margin:0 auto 16px;">
                <canvas id="invoiceDonut" height="160"></canvas>
                @php $total = $totalPaid + $totalBilled + $totalOverdue + $totalDraft; @endphp
                <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;">
                    <div style="font-size:1.1rem;font-weight:700;color:#1a1d23;">${{ $total > 0 ? number_format($total/1000, 0) . 'k' : '0' }}</div>
                    <div style="font-size:0.65rem;color:#9ca3af;">Total</div>
                </div>
            </div>

            @foreach([
                ['Paid',    $totalPaid,    '#22c55e'],
                ['Sent',    $totalBilled,  '#4c8bf5'],
                ['Overdue', $totalOverdue, '#ef4444'],
                ['Draft',   $totalDraft,   '#9ca3af'],
            ] as [$label, $amount, $color])
            @php $pct = $total > 0 ? ($amount / $total) * 100 : 0; @endphp
            <div class="invoice-breakdown-row">
                <div class="inv-color-dot" style="background:{{ $color }};"></div>
                <span style="font-size:0.75rem;font-weight:500;width:50px;flex-shrink:0;">{{ $label }}</span>
                <div class="inv-bar-wrap">
                    <div class="inv-bar-bg">
                        <div class="inv-bar-fill" style="width:{{ $pct }}%;background:{{ $color }};"></div>
                    </div>
                </div>
                <div style="text-align:right;flex-shrink:0;">
                    <div style="font-size:0.8rem;font-weight:600;">${{ number_format($amount, 0) }}</div>
                    <div style="font-size:0.65rem;color:#9ca3af;">{{ number_format($pct, 1) }}%</div>
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
        <h5><i class="bi bi-receipt me-2" style="color:#06b6d4;"></i>Recent Invoices</h5>
        <div class="d-flex align-items-center gap-2">
            <div class="inv-search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" id="invSearch" placeholder="Search invoices..." oninput="filterInvoices()">
            </div>
            <div class="filter-pill active-all" id="invFilterAll" onclick="filterInvoiceStatus('all')" style="padding:5px 12px;">All</div>
            <div class="filter-pill" id="invFilterPaid" onclick="filterInvoiceStatus('paid')" style="padding:5px 12px;">Paid</div>
            <div class="filter-pill" id="invFilterOverdue" onclick="filterInvoiceStatus('overdue')" style="padding:5px 12px;">Overdue</div>
            <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
    </div>
    <table class="table kore-table mb-0" id="invoiceTable">
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
            @php
                $invCls = match($inv->status) {
                    'paid'    => 'approved',
                    'sent'    => 'active',
                    'overdue' => 'rejected',
                    default   => 'draft',
                };
                $invNum = $inv->invoice_number ?? ('INV-' . str_pad($inv->id, 4, '0', STR_PAD_LEFT));
            @endphp
            <tr class="table-row-link" data-status="{{ $inv->status }}" style="cursor:pointer;" onclick="window.location='{{ route('invoices.show', $inv->id) }}'">
                <td><span class="fw-600" style="color:#4c8bf5;font-size:0.8rem;">{{ $invNum }}</span></td>
                <td style="font-weight:500;">{{ $inv->company_name }}</td>
                <td style="font-weight:600;">${{ number_format($inv->total, 2) }}</td>
                <td style="color:#9ca3af;font-size:0.78rem;">{{ $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->format('M d, Y') : '—' }}</td>
                <td><span class="badge badge-{{ $invCls }}">{{ ucfirst($inv->status) }}</span></td>
                <td><i class="bi bi-chevron-right" style="color:#d1d5db;font-size:0.75rem;"></i></td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No invoices yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
const monthlyData   = @json($monthlyRevenue);
const allLabels     = Object.keys(monthlyData);
const allValues     = Object.values(monthlyData);

// ── Revenue Chart with gradient fill ─────────────────────────────
let revenueChart;

function buildRevenueChart(labels, values) {
    if (revenueChart) revenueChart.destroy();
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 220);
    gradient.addColorStop(0, 'rgba(76,139,245,0.25)');
    gradient.addColorStop(1, 'rgba(76,139,245,0)');

    revenueChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Revenue ($)',
                data: values,
                backgroundColor: values.map((_, i) => i === values.length - 1 ? '#4c8bf5' : 'rgba(76,139,245,0.6)'),
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ' $' + ctx.raw.toLocaleString() } }
            },
            scales: {
                x: { grid: { display: false } },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f3f4f6' },
                    ticks: { callback: val => '$' + val.toLocaleString() }
                }
            },
            animation: { duration: 500 }
        }
    });
}

buildRevenueChart(allLabels, allValues);

function setPeriod(months, btn) {
    document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const slicedLabels = allLabels.slice(-months);
    const slicedValues = allValues.slice(-months);
    buildRevenueChart(slicedLabels, slicedValues);
}

// ── Invoice Doughnut ──────────────────────────────────────────────
new Chart(document.getElementById('invoiceDonut'), {
    type: 'doughnut',
    data: {
        labels: ['Paid', 'Sent', 'Overdue', 'Draft'],
        datasets: [{
            data: [{{ $totalPaid }}, {{ $totalBilled }}, {{ $totalOverdue }}, {{ $totalDraft }}],
            backgroundColor: ['#22c55e','#4c8bf5','#ef4444','#d1d5db'],
            borderWidth: 3, borderColor: '#fff',
        }]
    },
    options: {
        cutout: '68%',
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${ctx.label}: $${ctx.raw.toLocaleString()}` } } },
    }
});

// ── Invoice table filtering ───────────────────────────────────────
let invActiveStatus = 'all';

function filterInvoiceStatus(status) {
    invActiveStatus = status;
    applyInvoiceFilter();
    document.querySelectorAll('[id^=invFilter]').forEach(p => p.classList.remove('active', 'active-all'));
    const map = { all: 'invFilterAll', paid: 'invFilterPaid', overdue: 'invFilterOverdue' };
    const el = document.getElementById(map[status]);
    if (el) el.classList.add(status === 'all' ? 'active-all' : 'active');
}

function filterInvoices() { applyInvoiceFilter(); }

function applyInvoiceFilter() {
    const q = (document.getElementById('invSearch')?.value || '').toLowerCase();
    document.querySelectorAll('#invoiceTable tbody tr').forEach(row => {
        const rowStatus = (row.dataset.status || '').toLowerCase();
        const statusOk  = invActiveStatus === 'all' || rowStatus === invActiveStatus;
        const searchOk  = !q || row.textContent.toLowerCase().includes(q);
        row.style.display = statusOk && searchOk ? '' : 'none';
    });
}
</script>
@endpush
