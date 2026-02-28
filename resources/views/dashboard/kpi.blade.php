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
        <div class="stat-card">
            <div class="stat-icon text-primary"><i class="bi bi-people"></i></div>
            <div class="stat-value">{{ $totalUsers }}</div>
            <div class="stat-label">Active Employees</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon text-success"><i class="bi bi-trophy"></i></div>
            <div class="stat-value">{{ $winRate }}%</div>
            <div class="stat-label">Proposal Win Rate</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon text-warning"><i class="bi bi-check2-all"></i></div>
            <div class="stat-value">{{ $taskStats->get('completed', 0) }}</div>
            <div class="stat-label">Tasks Completed</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon text-danger"><i class="bi bi-exclamation-circle"></i></div>
            <div class="stat-value">{{ $taskStats->get('overdue', 0) }}</div>
            <div class="stat-label">Overdue Tasks</div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Proposal Win Rate --}}
    <div class="col-lg-4">
        <div class="kore-card text-center">
            <div class="kore-card-header">
                <h5><i class="bi bi-trophy me-2"></i>Proposal Win Rate</h5>
            </div>
            <canvas id="winRateChart" height="200"></canvas>
            <div class="mt-3" style="font-size:0.8rem; color:#6b7280;">
                {{ $wonProposals }} won out of {{ $totalProposals }} total proposals
            </div>
        </div>
    </div>

    {{-- Task Status Breakdown --}}
    <div class="col-lg-4">
        <div class="kore-card">
            <div class="kore-card-header">
                <h5><i class="bi bi-check2-square me-2"></i>Task Status</h5>
            </div>
            @foreach([['Active', 'active', 'active'], ['Completed', 'completed', 'approved'], ['Overdue', 'overdue', 'rejected']] as [$label, $key, $cls])
            @php $count = $taskStats->get($key, 0); @endphp
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span class="badge badge-{{ $cls }}">{{ $label }}</span>
                <span style="font-size:0.9rem; font-weight:600;">{{ $count }}</span>
            </div>
            @endforeach
            <canvas id="taskChart" height="140" class="mt-3"></canvas>
        </div>
    </div>

    {{-- Budget vs Actuals --}}
    <div class="col-lg-4">
        <div class="kore-card">
            <div class="kore-card-header">
                <h5><i class="bi bi-bar-chart-steps me-2"></i>Budget vs Actuals</h5>
            </div>
            @forelse($budgetData as $proj)
            @php
                $actualCost = $proj->actual_hours * 100; // placeholder rate
                $pct = $proj->total_budget > 0 ? min(100, ($actualCost / $proj->total_budget) * 100) : 0;
                $barColor = $pct >= 90 ? '#ef4444' : ($pct >= 70 ? '#f59e0b' : '#4c8bf5');
            @endphp
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-truncate" style="font-size:0.75rem; max-width:150px;">{{ $proj->title }}</span>
                    <span style="font-size:0.72rem; color:#6b7280;">{{ round($pct) }}%</span>
                </div>
                <div class="progress" style="height:6px; border-radius:3px;">
                    <div class="progress-bar" style="width:{{ $pct }}%; background:{{ $barColor }};"></div>
                </div>
            </div>
            @empty
            <div class="text-center text-muted py-3" style="font-size:0.8rem;">No active projects.</div>
            @endforelse
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
// Win Rate Doughnut
new Chart(document.getElementById('winRateChart'), {
    type: 'doughnut',
    data: {
        labels: ['Won', 'Other'],
        datasets: [{
            data: [{{ $wonProposals }}, {{ max(0, $totalProposals - $wonProposals) }}],
            backgroundColor: ['#22c55e','#f3f4f6'],
            borderWidth: 2
        }]
    },
    options: {
        cutout: '70%',
        plugins: {
            legend: { display: false },
            tooltip: { enabled: true }
        }
    }
});

// Task Status Pie
const taskData = {
    active:    {{ $taskStats->get('active', 0) }},
    completed: {{ $taskStats->get('completed', 0) }},
    overdue:   {{ $taskStats->get('overdue', 0) }},
};
new Chart(document.getElementById('taskChart'), {
    type: 'doughnut',
    data: {
        labels: ['Active', 'Completed', 'Overdue'],
        datasets: [{
            data: [taskData.active, taskData.completed, taskData.overdue],
            backgroundColor: ['#4c8bf5','#22c55e','#ef4444'],
            borderWidth: 2
        }]
    },
    options: {
        cutout: '55%',
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } }
    }
});
</script>
@endpush
