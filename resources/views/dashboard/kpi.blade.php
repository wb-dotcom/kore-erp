@extends('layouts.app')

@push('styles')
<style>
/* ── KPI Dashboard ─────────────────────────────────────── */

/* Gauge card */
.gauge-wrap {
    position: relative;
    width: 140px; height: 140px;
    margin: 0 auto 8px;
}
.gauge-wrap svg { transform: rotate(-90deg); }
.gauge-wrap .gauge-track { fill: none; stroke: #f3f4f6; stroke-width: 14; }
.gauge-wrap .gauge-fill  { fill: none; stroke-width: 14; stroke-linecap: round; transition: stroke-dashoffset 1.2s cubic-bezier(0.4,0,0.2,1); }
.gauge-center {
    position: absolute; inset: 0;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
}
.gauge-value { font-size: 1.8rem; font-weight: 800; letter-spacing: -1px; color: #1a1d23; }
.gauge-unit  { font-size: 0.65rem; color: #9ca3af; font-weight: 500; margin-top: -4px; }

/* Budget progress bars */
.budget-item { margin-bottom: 14px; }
.budget-item:last-child { margin-bottom: 0; }
.budget-bar-bg { height: 8px; background: #f3f4f6; border-radius: 6px; overflow: hidden; }
.budget-bar-fill { height: 100%; border-radius: 6px; transition: width 0.9s cubic-bezier(0.4,0,0.2,1); }

/* Task row */
.task-stat-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 12px; border-radius: 12px; margin-bottom: 6px;
    transition: background 0.12s;
}
.task-stat-row:hover { background: #f9fafb; }

/* KPI info cards */
.kpi-info-row {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 0; border-bottom: 1px solid #f3f4f6;
}
.kpi-info-row:last-child { border-bottom: none; }
</style>
@endpush

@section('content')

{{-- Welcome --}}
<div style="margin-bottom:26px;">
    <div>
        <h2 style="font-size:22px;font-weight:800;color:var(--c-t1);letter-spacing:-0.5px;margin:0 0 2px;">KPI Dashboard</h2>
        <p style="font-size:13.5px;color:var(--c-t3);margin:0;">Organisation performance metrics — {{ now()->format('F Y') }}</p>
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
            <a class="nav-link" href="{{ route('dashboard.financial') }}">
                <i class="bi bi-currency-dollar me-1"></i> Financial
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('dashboard.kpi') }}">
                <i class="bi bi-graph-up me-1"></i> KPIs
            </a>
        </li>
    </ul>
</nav>

{{-- KPI Stat Row --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card accent-blue h-100">
            <div class="stat-card-top">
                <div class="stat-icon-wrap" style="background:rgba(76,139,245,0.1);">
                    <i class="bi bi-people" style="color:#4c8bf5;"></i>
                </div>
            </div>
            <div class="stat-value" style="color:#4c8bf5;">{{ $totalUsers }}</div>
            <div class="stat-label">Active Employees</div>
            <div class="stat-sublabel">In your organisation</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card accent-green h-100">
            <div class="stat-card-top">
                <div class="stat-icon-wrap" style="background:rgba(34,197,94,0.1);">
                    <i class="bi bi-trophy" style="color:#22c55e;"></i>
                </div>
            </div>
            <div class="stat-value" style="color:#22c55e;">{{ $winRate }}%</div>
            <div class="stat-label">Proposal Win Rate</div>
            @php $winTrend = $winRate >= 50 ? 'up' : 'down'; @endphp
            <div class="stat-sublabel">
                <span class="trend-badge trend-{{ $winTrend }}">
                    <i class="bi bi-arrow-{{ $winTrend }}-short"></i>{{ $wonProposals }}/{{ $totalProposals }} proposals
                </span>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card accent-amber h-100">
            <div class="stat-card-top">
                <div class="stat-icon-wrap" style="background:rgba(245,158,11,0.1);">
                    <i class="bi bi-check2-all" style="color:#f59e0b;"></i>
                </div>
            </div>
            <div class="stat-value" style="color:#f59e0b;">{{ $taskStats->get('completed', 0) }}</div>
            <div class="stat-label">Tasks Completed</div>
            <div class="stat-sublabel">All time</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card accent-red h-100">
            <div class="stat-card-top">
                <div class="stat-icon-wrap" style="background:rgba(239,68,68,0.1);">
                    <i class="bi bi-exclamation-circle" style="color:#ef4444;"></i>
                </div>
                @if($taskStats->get('overdue', 0) > 0)
                <span style="font-size:0.65rem;padding:3px 8px;border-radius:20px;background:#fee2e2;color:#991b1b;font-weight:600;">Action Needed</span>
                @endif
            </div>
            <div class="stat-value" style="color:#ef4444;">{{ $taskStats->get('overdue', 0) }}</div>
            <div class="stat-label">Overdue Tasks</div>
            <div class="stat-sublabel">Require attention</div>
        </div>
    </div>
</div>

<div class="row g-4">

    {{-- Win Rate Gauge --}}
    <div class="col-lg-4">
        <div class="kore-card text-center h-100">
            <div class="kore-card-header">
                <h5><i class="bi bi-trophy me-2" style="color:#22c55e;"></i>Proposal Win Rate</h5>
            </div>

            <div class="gauge-wrap">
                <svg viewBox="0 0 140 140" width="140" height="140">
                    <circle class="gauge-track" cx="70" cy="70" r="56"></circle>
                    <circle class="gauge-fill" id="winGaugeFill" cx="70" cy="70" r="56"
                        stroke="#22c55e"
                        stroke-dasharray="{{ 2 * 3.14159 * 56 }}"
                        stroke-dashoffset="{{ 2 * 3.14159 * 56 }}"
                        data-pct="{{ $winRate }}">
                    </circle>
                </svg>
                <div class="gauge-center">
                    <div class="gauge-value" style="color:#22c55e;">{{ $winRate }}%</div>
                    <div class="gauge-unit">win rate</div>
                </div>
            </div>

            <div style="font-size:0.8rem;color:#9ca3af;margin-top:4px;">
                <strong style="color:#1a1d23;">{{ $wonProposals }}</strong> won of
                <strong style="color:#1a1d23;">{{ $totalProposals }}</strong> proposals
            </div>

            <div class="row g-2 mt-3">
                <div class="col-6">
                    <div style="background:#f0fdf4;border-radius:12px;padding:12px 8px;">
                        <div style="font-size:1.2rem;font-weight:700;color:#22c55e;">{{ $wonProposals }}</div>
                        <div style="font-size:0.7rem;color:#6b7280;">Won</div>
                    </div>
                </div>
                <div class="col-6">
                    <div style="background:#fef2f2;border-radius:12px;padding:12px 8px;">
                        <div style="font-size:1.2rem;font-weight:700;color:#ef4444;">{{ max(0, $totalProposals - $wonProposals) }}</div>
                        <div style="font-size:0.7rem;color:#6b7280;">Other</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Task Status --}}
    <div class="col-lg-4">
        <div class="kore-card h-100">
            <div class="kore-card-header">
                <h5><i class="bi bi-check2-square me-2" style="color:#4c8bf5;"></i>Task Status</h5>
            </div>

            @php
                $taskTotal = $taskStats->get('active', 0) + $taskStats->get('completed', 0) + $taskStats->get('overdue', 0);
            @endphp

            @foreach([
                ['Active',    'active',    '#4c8bf5', 'active'],
                ['Completed', 'completed', '#22c55e', 'approved'],
                ['Overdue',   'overdue',   '#ef4444', 'rejected'],
            ] as [$label, $key, $color, $badge])
            @php
                $count = $taskStats->get($key, 0);
                $pct   = $taskTotal > 0 ? ($count / $taskTotal) * 100 : 0;
            @endphp
            <div class="task-stat-row">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:10px;height:10px;border-radius:50%;background:{{ $color }};flex-shrink:0;"></div>
                    <span style="font-size:0.82rem;font-weight:500;">{{ $label }}</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div style="width:70px;">
                        <div class="budget-bar-bg">
                            <div class="budget-bar-fill" style="width:{{ $pct }}%;background:{{ $color }};"></div>
                        </div>
                    </div>
                    <span style="font-size:0.9rem;font-weight:700;width:28px;text-align:right;color:#1a1d23;">{{ $count }}</span>
                </div>
            </div>
            @endforeach

            <div style="margin-top:20px;position:relative;max-width:160px;margin:20px auto 0;">
                <canvas id="taskChart" height="160"></canvas>
            </div>
        </div>
    </div>

    {{-- Budget vs Actuals --}}
    <div class="col-lg-4">
        <div class="kore-card h-100">
            <div class="kore-card-header">
                <h5><i class="bi bi-bar-chart-steps me-2" style="color:#f59e0b;"></i>Budget vs Actuals</h5>
                <span style="font-size:0.7rem;color:#9ca3af;">Top projects</span>
            </div>

            @forelse($budgetData as $proj)
            @php
                $actualCost = $proj->actual_hours * 100;
                $pct = $proj->total_budget > 0 ? min(100, ($actualCost / $proj->total_budget) * 100) : 0;
                $barColor = $pct >= 90 ? '#ef4444' : ($pct >= 70 ? '#f59e0b' : '#4c8bf5');
                $bgColor  = $pct >= 90 ? '#fef2f2'  : ($pct >= 70 ? '#fffbeb'  : '#eff6ff');
            @endphp
            <div class="budget-item">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-truncate fw-500" style="font-size:0.76rem;max-width:160px;color:#374151;">{{ $proj->title }}</span>
                    <div class="d-flex align-items-center gap-1">
                        @if($pct >= 90)
                            <i class="bi bi-exclamation-triangle-fill" style="color:#ef4444;font-size:0.7rem;"></i>
                        @endif
                        <span style="font-size:0.72rem;font-weight:600;color:{{ $barColor }};background:{{ $bgColor }};padding:2px 7px;border-radius:20px;">{{ round($pct) }}%</span>
                    </div>
                </div>
                <div class="budget-bar-bg">
                    <div class="budget-bar-fill" style="width:{{ $pct }}%;background:{{ $barColor }};"></div>
                </div>
            </div>
            @empty
            <div class="empty-state">
                <i class="bi bi-bar-chart"></i>
                <p>No active projects.</p>
            </div>
            @endforelse

            {{-- Legend --}}
            <div class="d-flex gap-3 mt-4" style="font-size:0.7rem;color:#9ca3af;flex-wrap:wrap;">
                <div class="d-flex align-items-center gap-1"><div style="width:8px;height:8px;border-radius:2px;background:#4c8bf5;"></div>On Track</div>
                <div class="d-flex align-items-center gap-1"><div style="width:8px;height:8px;border-radius:2px;background:#f59e0b;"></div>At Risk</div>
                <div class="d-flex align-items-center gap-1"><div style="width:8px;height:8px;border-radius:2px;background:#ef4444;"></div>Over Budget</div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
// ── Win Rate Gauge animation ──────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const fill = document.getElementById('winGaugeFill');
    if (fill) {
        const r    = 56;
        const circ = 2 * Math.PI * r;
        const pct  = parseFloat(fill.dataset.pct) / 100;
        fill.style.strokeDasharray  = circ;
        fill.style.strokeDashoffset = circ;
        requestAnimationFrame(() => {
            setTimeout(() => {
                fill.style.strokeDashoffset = circ * (1 - pct);
            }, 100);
        });
    }
});

// ── Task Status Doughnut ──────────────────────────────────────────
new Chart(document.getElementById('taskChart'), {
    type: 'doughnut',
    data: {
        labels: ['Active', 'Completed', 'Overdue'],
        datasets: [{
            data: [
                {{ $taskStats->get('active', 0) }},
                {{ $taskStats->get('completed', 0) }},
                {{ $taskStats->get('overdue', 0) }}
            ],
            backgroundColor: ['#4c8bf5', '#22c55e', '#ef4444'],
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 5,
        }]
    },
    options: {
        cutout: '62%',
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.raw} tasks` } }
        },
        animation: { duration: 800 }
    }
});
</script>
@endpush
