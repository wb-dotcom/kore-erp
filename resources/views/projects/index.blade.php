@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">Projects</h4>
        <div style="font-size:0.75rem; color:#6b7280;">{{ $projects->total() }} total</div>
    </div>
    <a href="{{ route('projects.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> New Project
    </a>
</div>

{{-- Filter Bar --}}
<div class="kore-card mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-sm-3">
            <label class="form-label" style="font-size:0.75rem; font-weight:600;">Search</label>
            <input type="text" name="search" class="form-control form-control-sm"
                placeholder="Title or client..." value="{{ request('search') }}">
        </div>
        <div class="col-sm-2">
            <label class="form-label" style="font-size:0.75rem; font-weight:600;">Status</label>
            <select name="status_id" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                @foreach($statuses as $s)
                <option value="{{ $s->id }}" {{ request('status_id') == $s->id ? 'selected' : '' }}>
                    {{ $s->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-2">
            <label class="form-label" style="font-size:0.75rem; font-weight:600;">Manager</label>
            <select name="manager_id" class="form-select form-select-sm">
                <option value="">All Managers</option>
                @foreach($managers as $m)
                <option value="{{ $m->id }}" {{ request('manager_id') == $m->id ? 'selected' : '' }}>
                    {{ $m->full_name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-1">
            <label class="form-label" style="font-size:0.75rem; font-weight:600;">Year</label>
            <select name="year" class="form-select form-select-sm">
                <option value="">All</option>
                @foreach($years as $y)
                <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-auto">
            <button type="submit" class="btn btn-sm btn-primary">Filter</button>
            <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
        </div>
    </form>
</div>

{{-- Projects Table --}}
<div class="kore-card p-0">
    <table class="table kore-table mb-0">
        <thead>
            <tr>
                <th>Project #</th>
                <th>Title</th>
                <th>Client</th>
                <th>Manager</th>
                <th>Start</th>
                <th>End</th>
                <th>Budget</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($projects as $project)
            <tr>
                <td>
                    <a href="{{ route('projects.show', $project) }}" class="text-decoration-none fw-600"
                        style="color:#4c8bf5; font-size:0.8rem;">
                        {{ $project->year }}-{{ str_pad($project->project_number, 3, '0', STR_PAD_LEFT) }}
                    </a>
                </td>
                <td style="max-width:220px;">
                    <div class="text-truncate" style="font-size:0.82rem;">{{ $project->title }}</div>
                </td>
                <td style="font-size:0.8rem; color:#374151;">{{ $project->company?->name ?? '—' }}</td>
                <td style="font-size:0.78rem; color:#6b7280;">{{ $project->projectManager?->full_name ?? '—' }}</td>
                <td style="font-size:0.78rem; color:#6b7280;">
                    {{ $project->start_date?->format('M d, Y') ?? '—' }}
                </td>
                <td style="font-size:0.78rem; color:#6b7280;">
                    {{ $project->end_date?->format('M d, Y') ?? '—' }}
                </td>
                <td style="font-size:0.78rem; color:#374151;">
                    {{ $project->total_budget ? '$'.number_format($project->total_budget, 0) : '—' }}
                </td>
                <td>
                    @php
                        $cls = match($project->status?->name) {
                            'Active'     => 'active',
                            'Complete'   => 'approved',
                            'On Hold'    => 'draft',
                            'Cancelled'  => 'rejected',
                            default      => 'draft',
                        };
                    @endphp
                    <span class="badge badge-{{ $cls }}">{{ $project->status?->name ?? '—' }}</span>
                </td>
                <td class="text-end">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" style="font-size:0.8rem;">
                            <li><a class="dropdown-item" href="{{ route('projects.show', $project) }}">
                                <i class="bi bi-eye me-2"></i>View
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('projects.edit', $project) }}">
                                <i class="bi bi-pencil me-2"></i>Edit
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('projects.deliverables', $project) }}">
                                <i class="bi bi-list-task me-2"></i>Deliverables
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('projects.destroy', $project) }}" method="POST"
                                    onsubmit="return confirm('Delete this project?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-trash me-2"></i>Delete
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center py-5" style="color:#9ca3af;">
                    <i class="bi bi-folder2-open fs-2 d-block mb-2"></i>
                    No projects found.
                    <a href="{{ route('projects.create') }}" class="d-block mt-2 text-primary" style="font-size:0.8rem;">
                        Create your first project
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($projects->hasPages())
<div class="d-flex justify-content-between align-items-center mt-3">
    <div style="font-size:0.75rem; color:#6b7280;">
        Showing {{ $projects->firstItem() }}–{{ $projects->lastItem() }} of {{ $projects->total() }}
    </div>
    {{ $projects->links() }}
</div>
@endif

@endsection
