@extends('layouts.app')

@push('styles')
<style>
/* ── Business Dashboard ────────────────────────────────── */
.table-row-link { cursor: pointer; }
.table-row-link:hover td { background: #f5f8ff !important; }

.chart-header-controls {
    display: flex; align-items: center; gap: 8px;
}
.chart-type-btn {
    width: 30px; height: 30px; border-radius: 8px;
    border: 1.5px solid #e5e7eb; background: #fff;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all 0.15s; font-size: 0.8rem;
    color: #9ca3af;
}
.chart-type-btn:hover, .chart-type-btn.active {
    background: #4c8bf5; border-color: #4c8bf5; color: #fff;
}

.status-legend-item {
    display: flex; align-items: center; gap: 6px;
    padding: 6px 10px; border-radius: 8px;
    cursor: pointer; transition: background 0.12s;
    font-size: 0.75rem; color: #6b7280;
}
.status-legend-item:hover { background: #f5f6fa; }
.status-legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
</style>
@endpush

@section('content')

{{-- Welcome --}}
<div style="margin-bottom:26px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h2 style="font-size:22px;font-weight:800;color:var(--c-t1);letter-spacing:-0.5px;margin:0 0 2px;">Business Overview</h2>
            <p style="font-size:13.5px;color:var(--c-t3);margin:0;">Projects, proposals and pipeline — {{ now()->format('F Y') }}</p>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('proposals.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus me-1"></i> New Proposal</a>
            <a href="{{ route('projects.create') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-folder-plus me-1"></i> New Project</a>
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
            <a class="nav-link active" href="{{ route('dashboard.business') }}">
                <i class="bi bi-briefcase me-1"></i> Business
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('dashboard.financial') }}">
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

{{-- Stat Row --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card accent-blue h-100">
            <div class="stat-card-top">
                <div class="stat-icon-wrap" style="background:rgba(76,139,245,0.1);">
                    <i class="bi bi-folder2-open" style="color:#4c8bf5;"></i>
                </div>
            </div>
            <div class="stat-value" style="color:#4c8bf5;">{{ $activeProjects }}</div>
            <div class="stat-label">Active Projects</div>
            <div class="stat-sublabel">Currently in progress</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card accent-green h-100">
            <div class="stat-card-top">
                <div class="stat-icon-wrap" style="background:rgba(34,197,94,0.1);">
                    <i class="bi bi-file-earmark-check" style="color:#22c55e;"></i>
                </div>
            </div>
            <div class="stat-value" style="color:#22c55e;">{{ $proposalStats->get('Approved', 0) }}</div>
            <div class="stat-label">Approved Proposals</div>
            <div class="stat-sublabel">Won engagements</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card accent-amber h-100">
            <div class="stat-card-top">
                <div class="stat-icon-wrap" style="background:rgba(245,158,11,0.1);">
                    <i class="bi bi-send" style="color:#f59e0b;"></i>
                </div>
            </div>
            <div class="stat-value" style="color:#f59e0b;">{{ $proposalStats->get('Submitted', 0) }}</div>
            <div class="stat-label">Proposals Submitted</div>
            <div class="stat-sublabel">Awaiting decision</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card h-100">
            <div class="stat-card-top">
                <div class="stat-icon-wrap" style="background:rgba(107,114,128,0.1);">
                    <i class="bi bi-file-earmark" style="color:#6b7280;"></i>
                </div>
            </div>
            <div class="stat-value" style="color:#6b7280;">{{ $proposalStats->get('Draft', 0) }}</div>
            <div class="stat-label">Proposals in Draft</div>
            <div class="stat-sublabel">Work in progress</div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="filter-bar">
    <span style="font-size:0.75rem;color:#9ca3af;font-weight:500;margin-right:4px;">Filter by Status:</span>
    <button class="filter-pill active-all" id="filterAll" onclick="filterTable('all')">All</button>
    <button class="filter-pill" id="filterApproved" onclick="filterTable('approved')"><i class="bi bi-circle-fill me-1" style="font-size:0.5rem;color:#22c55e;"></i>Approved</button>
    <button class="filter-pill" id="filterSubmitted" onclick="filterTable('submitted')"><i class="bi bi-circle-fill me-1" style="font-size:0.5rem;color:#4c8bf5;"></i>Submitted</button>
    <button class="filter-pill" id="filterDraft" onclick="filterTable('draft')"><i class="bi bi-circle-fill me-1" style="font-size:0.5rem;color:#9ca3af;"></i>Draft</button>
    <div style="margin-left:auto;">
        <input type="text" class="filter-select" id="proposalSearch" placeholder="&#xF52A; Search proposals..." style="width:200px;" oninput="searchTable()">
    </div>
</div>

<div class="row g-4">
    {{-- Recent Proposals --}}
    <div class="col-lg-6">
        <div class="kore-card">
            <div class="kore-card-header">
                <h5><i class="bi bi-file-earmark-text me-2" style="color:#4c8bf5;"></i>Recent Proposals</h5>
                <a href="{{ route('proposals.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <table class="table kore-table mb-0" id="proposalsTable">
                <thead>
                    <tr>
                        <th>Ref</th>
                        <th>Title</th>
                        <th>Client</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentProposals as $proposal)
                    @php
                        $statusName = $proposal->status?->name ?? '—';
                        $statusClass = match($statusName) {
                            'Approved'  => 'approved',
                            'Submitted' => 'active',
                            'Rejected'  => 'rejected',
                            default     => 'draft',
                        };
                    @endphp
                    <tr class="table-row-link" data-status="{{ strtolower($statusName) }}" onclick="window.location='{{ route('proposals.show', $proposal) }}'">
                        <td>
                            <span class="fw-600" style="font-size:0.78rem;color:#4c8bf5;">{{ $proposal->ref }}</span>
                        </td>
                        <td class="text-truncate" style="max-width:140px;font-weight:500;">{{ $proposal->title }}</td>
                        <td style="color:#9ca3af;font-size:0.75rem;">{{ $proposal->company?->name ?? '—' }}</td>
                        <td>
                            <span class="badge badge-{{ $statusClass }}">{{ $statusName }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No proposals yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent Projects --}}
    <div class="col-lg-6">
        <div class="kore-card">
            <div class="kore-card-header">
                <h5><i class="bi bi-folder2-open me-2" style="color:#22c55e;"></i>Recent Projects</h5>
                <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <table class="table kore-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Client</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentProjects as $project)
                    @php
                        $pStatusName = $project->status?->name ?? '—';
                        $pStatusClass = match($pStatusName) {
                            'Active'    => 'active',
                            'Completed' => 'completed',
                            'On Hold'   => 'pending',
                            default     => 'draft',
                        };
                    @endphp
                    <tr class="table-row-link" onclick="window.location='{{ route('projects.show', $project) }}'">
                        <td>
                            <span class="fw-600" style="font-size:0.78rem;color:#4c8bf5;">{{ $project->project_number }}</span>
                        </td>
                        <td class="text-truncate" style="max-width:140px;font-weight:500;">{{ $project->title }}</td>
                        <td style="color:#9ca3af;font-size:0.75rem;">{{ $project->company?->name ?? '—' }}</td>
                        <td>
                            <span class="badge badge-{{ $pStatusClass }}">{{ $pStatusName }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No projects yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Charts Row --}}
<div class="row g-4 mt-0">
    <div class="col-lg-5">
        <div class="kore-card">
            <div class="kore-card-header">
                <h5><i class="bi bi-pie-chart me-2" style="color:#8b5cf6;"></i>Projects by Status</h5>
                <div class="chart-header-controls">
                    <button class="chart-type-btn active" id="btnDoughnut" title="Doughnut" onclick="switchChart('doughnut')">
                        <i class="bi bi-circle"></i>
                    </button>
                    <button class="chart-type-btn" id="btnPie" title="Pie" onclick="switchChart('pie')">
                        <i class="bi bi-pie-chart"></i>
                    </button>
                </div>
            </div>
            <div style="position:relative;max-width:260px;margin:0 auto;">
                <canvas id="projectStatusChart" height="220"></canvas>
            </div>
            <div id="projectLegend" class="chart-legend-custom mt-2"></div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="kore-card">
            <div class="kore-card-header">
                <h5><i class="bi bi-bar-chart me-2" style="color:#06b6d4;"></i>Proposals by Status</h5>
                <div class="chart-header-controls">
                    <button class="chart-type-btn active" id="btnBar" title="Bar" onclick="switchProposalChart('bar')">
                        <i class="bi bi-bar-chart"></i>
                    </button>
                    <button class="chart-type-btn" id="btnBarH" title="Horizontal" onclick="switchProposalChart('barH')">
                        <i class="bi bi-bar-chart-steps"></i>
                    </button>
                </div>
            </div>
            <canvas id="proposalStatusChart" height="210"></canvas>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
const COLORS = ['#4c8bf5','#22c55e','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#64748b'];
const csrfToken = document.querySelector('meta[name=csrf-token]').content;

// ── Project Status Chart ──────────────────────────────────────────
let projectChart, projectData;
fetch('/api/dashboard/chart-data?type=project_status', { headers: { 'X-CSRF-TOKEN': csrfToken } })
.then(r => r.json())
.then(data => {
    projectData = data;
    projectChart = buildProjectChart('doughnut');
    buildProjectLegend(data);
});

function buildProjectChart(type) {
    if (projectChart) projectChart.destroy();
    return new Chart(document.getElementById('projectStatusChart'), {
        type: type,
        data: {
            labels: projectData.map(d => d.label),
            datasets: [{
                data: projectData.map(d => d.value),
                backgroundColor: COLORS,
                borderWidth: 3,
                borderColor: '#fff',
                hoverOffset: 6,
            }]
        },
        options: {
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.label}: ${ctx.raw} project${ctx.raw !== 1 ? 's' : ''}`
                    }
                }
            },
            cutout: type === 'doughnut' ? '62%' : 0,
            animation: { duration: 500 }
        }
    });
}

function buildProjectLegend(data) {
    const legend = document.getElementById('projectLegend');
    legend.innerHTML = data.map((d, i) =>
        `<div class="chart-legend-item">
            <div class="chart-legend-dot" style="background:${COLORS[i]};"></div>
            <span>${d.label} <strong>${d.value}</strong></span>
        </div>`
    ).join('');
}

function switchChart(type) {
    projectChart = buildProjectChart(type);
    document.getElementById('btnDoughnut').classList.toggle('active', type === 'doughnut');
    document.getElementById('btnPie').classList.toggle('active', type === 'pie');
}

// ── Proposal Status Chart ─────────────────────────────────────────
let proposalChart, proposalData;
fetch('/api/dashboard/chart-data?type=proposal_status', { headers: { 'X-CSRF-TOKEN': csrfToken } })
.then(r => r.json())
.then(data => {
    proposalData = data;
    proposalChart = buildProposalChart('bar');
});

function buildProposalChart(type) {
    if (proposalChart) proposalChart.destroy();
    const isHorizontal = type === 'barH';
    return new Chart(document.getElementById('proposalStatusChart'), {
        type: 'bar',
        data: {
            labels: proposalData.map(d => d.label),
            datasets: [{
                label: 'Proposals',
                data: proposalData.map(d => d.value),
                backgroundColor: COLORS.map(c => c + 'cc'),
                borderColor: COLORS,
                borderWidth: 1.5,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            indexAxis: isHorizontal ? 'y' : 'x',
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, grid: { display: !isHorizontal }, ticks: { stepSize: 1 } },
                y: { beginAtZero: true, grid: { display: isHorizontal }, ticks: { stepSize: 1 } },
            },
            animation: { duration: 400 }
        }
    });
}

function switchProposalChart(type) {
    proposalChart = buildProposalChart(type);
    document.getElementById('btnBar').classList.toggle('active', type === 'bar');
    document.getElementById('btnBarH').classList.toggle('active', type === 'barH');
}

// ── Client-side filtering ─────────────────────────────────────────
let activeFilter = 'all';

function filterTable(status) {
    activeFilter = status;
    applyFilters();
    // Update pill styles
    document.querySelectorAll('.filter-pill').forEach(p => {
        p.classList.remove('active', 'active-all');
    });
    const map = { all: 'filterAll', approved: 'filterApproved', submitted: 'filterSubmitted', draft: 'filterDraft' };
    const el = document.getElementById(map[status]);
    if (el) el.classList.add(status === 'all' ? 'active-all' : 'active');
}

function searchTable() { applyFilters(); }

function applyFilters() {
    const q = (document.getElementById('proposalSearch')?.value || '').toLowerCase();
    document.querySelectorAll('#proposalsTable tbody tr').forEach(row => {
        const rowStatus  = (row.dataset.status || '').toLowerCase();
        const rowText    = row.textContent.toLowerCase();
        const statusMatch = activeFilter === 'all' || rowStatus.includes(activeFilter);
        const searchMatch = !q || rowText.includes(q);
        row.style.display = statusMatch && searchMatch ? '' : 'none';
    });
}
</script>
@endpush
