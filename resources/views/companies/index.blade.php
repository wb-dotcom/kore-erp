@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">Companies</h4>
        <div style="font-size:0.75rem; color:#6b7280;">{{ $companies->total() }} total</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('contacts.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-person-lines-fill me-1"></i> Contacts
        </a>
        <a href="{{ route('companies.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Company
        </a>
    </div>
</div>

{{-- Filters --}}
<div class="kore-card mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-sm-4">
            <label class="form-label" style="font-size:0.75rem; font-weight:600;">Search</label>
            <input type="text" name="search" class="form-control form-control-sm"
                placeholder="Company name..." value="{{ request('search') }}">
        </div>
        <div class="col-sm-2">
            <label class="form-label" style="font-size:0.75rem; font-weight:600;">Sector</label>
            <select name="sector_id" class="form-select form-select-sm">
                <option value="">All Sectors</option>
                @foreach($sectors as $s)
                <option value="{{ $s->id }}" {{ request('sector_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-2">
            <label class="form-label" style="font-size:0.75rem; font-weight:600;">Region</label>
            <select name="region_id" class="form-select form-select-sm">
                <option value="">All Regions</option>
                @foreach($regions as $r)
                <option value="{{ $r->id }}" {{ request('region_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-auto d-flex align-items-center gap-2">
            <div class="form-check mb-0">
                <input type="checkbox" name="active_only" id="activeOnly" class="form-check-input"
                    value="1" {{ request('active_only') ? 'checked' : '' }}>
                <label class="form-check-label" for="activeOnly" style="font-size:0.78rem;">Active only</label>
            </div>
        </div>
        <div class="col-sm-auto">
            <button type="submit" class="btn btn-sm btn-primary">Filter</button>
            <a href="{{ route('companies.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="kore-card p-0">
    <table class="table kore-table mb-0">
        <thead>
            <tr>
                <th>Company</th>
                <th>Sector</th>
                <th>Region</th>
                <th>Phone</th>
                <th class="text-center">Contacts</th>
                <th class="text-center">Projects</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($companies as $company)
            <tr>
                <td>
                    <a href="{{ route('companies.show', $company) }}" class="text-decoration-none fw-600"
                        style="color:#4c8bf5; font-size:0.82rem;">
                        {{ $company->name }}
                    </a>
                    @if($company->website)
                    <a href="{{ $company->website }}" target="_blank" class="ms-1" style="color:#9ca3af; font-size:0.7rem;">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                    @endif
                </td>
                <td style="font-size:0.78rem; color:#6b7280;">{{ $company->sector?->name ?? '—' }}</td>
                <td style="font-size:0.78rem; color:#6b7280;">{{ $company->region?->name ?? '—' }}</td>
                <td style="font-size:0.78rem; color:#6b7280;">{{ $company->phone ?? '—' }}</td>
                <td class="text-center" style="font-size:0.8rem;">{{ $company->contacts_count }}</td>
                <td class="text-center" style="font-size:0.8rem;">{{ $company->projects_count }}</td>
                <td>
                    @if($company->is_active)
                    <span class="badge badge-active">Active</span>
                    @else
                    <span class="badge badge-draft">Inactive</span>
                    @endif
                </td>
                <td class="text-end">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" style="font-size:0.8rem;">
                            <li><a class="dropdown-item" href="{{ route('companies.show', $company) }}">
                                <i class="bi bi-eye me-2"></i>View
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('companies.edit', $company) }}">
                                <i class="bi bi-pencil me-2"></i>Edit
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('companies.destroy', $company) }}" method="POST"
                                    onsubmit="return confirm('Delete {{ $company->name }}?')">
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
                <td colspan="8" class="text-center py-5" style="color:#9ca3af;">
                    <i class="bi bi-building fs-2 d-block mb-2"></i>
                    No companies found.
                    <a href="{{ route('companies.create') }}" class="d-block mt-2 text-primary" style="font-size:0.8rem;">
                        Add your first company
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($companies->hasPages())
<div class="d-flex justify-content-between align-items-center mt-3">
    <div style="font-size:0.75rem; color:#6b7280;">
        Showing {{ $companies->firstItem() }}–{{ $companies->lastItem() }} of {{ $companies->total() }}
    </div>
    {{ $companies->links() }}
</div>
@endif

@endsection
