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
        <div class="stat-card">
            <div class="stat-icon text-primary"><i class="bi bi-folder2-open"></i></div>
            <div class="stat-value">{{ $activeProjects }}</div>
            <div class="stat-label">Active Projects</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon text-success"><i class="bi bi-file-earmark-check"></i></div>
            <div class="stat-value">{{ $proposalStats->get('Approved', 0) }}</div>
            <div class="stat-label">Approved Proposals</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon text-warning"><i class="bi bi-file-earmark-text"></i></div>
            <div class="stat-value">{{ $proposalStats->get('Submitted', 0) }}</div>
            <div class="stat-label">Proposals Submitted</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon text-secondary"><i class="bi bi-file-earmark"></i></div>
            <div class="stat-value">{{ $proposalStats->get('Draft', 0) }}</div>
            <div class="stat-label">Proposals in Draft</div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Recent Proposals --}}
    <div class="col-lg-6">
        <div class="kore-card">
            <div class="kore-card-header">
                <h5><i class="bi bi-file-earmark-text me-2"></i>Recent Proposals</h5>
                <a href="{{ route('proposals.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <table class="table kore-table mb-0">
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
                    <tr>
                        <td>
                            <a href="{{ route('proposals.show', $proposal) }}" class="text-decoration-none fw-600" style="font-size:0.78rem;">
                                {{ $proposal->ref }}
                            </a>
                        </td>
                        <td class="text-truncate" style="max-width:140px;">{{ $proposal->title }}</td>
                        <td style="color:#6b7280;">{{ $proposal->company?->name ?? '—' }}</td>
                        <td>
                            @php
                                $statusClass = match($proposal->status?->name) {
                                    'Approved'  => 'approved',
                                    'Submitted' => 'active',
                                    'Rejected'  => 'rejected',
                                    default     => 'draft',
                                };
                            @endphp
                            <span class="badge badge-{{ $statusClass }}">{{ $proposal->status?->name ?? '—' }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">No proposals yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent Projects --}}
    <div class="col-lg-6">
        <div class="kore-card">
            <div class="kore-card-header">
                <h5><i class="bi bi-folder2-open me-2"></i>Recent Projects</h5>
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
                    <tr>
                        <td>
                            <a href="{{ route('projects.show', $project) }}" class="text-decoration-none fw-600" style="font-size:0.78rem;">
                                {{ $project->project_number }}
                            </a>
                        </td>
                        <td class="text-truncate" style="max-width:140px;">{{ $project->title }}</td>
                        <td style="color:#6b7280;">{{ $project->company?->name ?? '—' }}</td>
                        <td>
                            @php
                                $statusClass = match($project->status?->name) {
                                    'Active'    => 'active',
                                    'Completed' => 'completed',
                                    'On Hold'   => 'pending',
                                    default     => 'draft',
                                };
                            @endphp
                            <span class="badge badge-{{ $statusClass }}">{{ $project->status?->name ?? '—' }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">No projects yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Project Status Chart --}}
<div class="row g-4 mt-0">
    <div class="col-lg-5">
        <div class="kore-card">
            <div class="kore-card-header">
                <h5><i class="bi bi-pie-chart me-2"></i>Projects by Status</h5>
            </div>
            <canvas id="projectStatusChart" height="200"></canvas>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="kore-card">
            <div class="kore-card-header">
                <h5><i class="bi bi-bar-chart me-2"></i>Proposals by Status</h5>
            </div>
            <canvas id="proposalStatusChart" height="200"></canvas>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
const COLORS = ['#4c8bf5','#22c55e','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#64748b'];

// Project Status Chart
fetch('/api/dashboard/chart-data?type=project_status', {
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
})
.then(r => r.json())
.then(data => {
    new Chart(document.getElementById('projectStatusChart'), {
        type: 'doughnut',
        data: {
            labels: data.map(d => d.label),
            datasets: [{ data: data.map(d => d.value), backgroundColor: COLORS, borderWidth: 2 }]
        },
        options: { plugins: { legend: { position: 'bottom' } }, cutout: '60%' }
    });
});

// Proposal Status Chart
fetch('/api/dashboard/chart-data?type=proposal_status', {
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
})
.then(r => r.json())
.then(data => {
    new Chart(document.getElementById('proposalStatusChart'), {
        type: 'bar',
        data: {
            labels: data.map(d => d.label),
            datasets: [{
                label: 'Proposals',
                data: data.map(d => d.value),
                backgroundColor: COLORS,
                borderRadius: 6
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
});
</script>
@endpush
