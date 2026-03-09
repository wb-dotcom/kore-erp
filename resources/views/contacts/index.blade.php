@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">Contacts</h4>
        <div style="font-size:0.75rem; color:#6b7280;">{{ $contacts->total() }} total</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('companies.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-building me-1"></i> Companies
        </a>
        <a href="{{ route('contacts.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Contact
        </a>
    </div>
</div>

{{-- Filters --}}
<div class="kore-card mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-sm-3">
            <label class="form-label" style="font-size:0.75rem; font-weight:600;">Search</label>
            <input type="text" name="search" class="form-control form-control-sm"
                placeholder="Name, email, company..." value="{{ request('search') }}">
        </div>
        <div class="col-sm-2">
            <label class="form-label" style="font-size:0.75rem; font-weight:600;">Type</label>
            <select name="type_id" class="form-select form-select-sm">
                <option value="">All Types</option>
                @foreach($contactTypes as $t)
                <option value="{{ $t->id }}" {{ request('type_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-3">
            <label class="form-label" style="font-size:0.75rem; font-weight:600;">Company</label>
            <select name="company_id" class="form-select form-select-sm">
                <option value="">All Companies</option>
                @foreach($companies as $c)
                <option value="{{ $c->id }}" {{ request('company_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
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
            <a href="{{ route('contacts.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="kore-card p-0">
    <table class="table kore-table mb-0">
        <thead>
            <tr>
                <th>Name</th>
                <th>Title</th>
                <th>Company</th>
                <th>Type</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($contacts as $contact)
            <tr>
                <td>
                    <a href="{{ route('contacts.show', $contact) }}" class="text-decoration-none fw-600"
                        style="color:#4c8bf5; font-size:0.82rem;">
                        {{ $contact->full_name }}
                    </a>
                </td>
                <td style="font-size:0.78rem; color:#6b7280;">{{ $contact->title ?? '—' }}</td>
                <td style="font-size:0.8rem; color:#374151;">
                    @if($contact->company)
                    <a href="{{ route('companies.show', $contact->company) }}"
                        class="text-decoration-none" style="color:#374151;">
                        {{ $contact->company->name }}
                    </a>
                    @else —
                    @endif
                </td>
                <td style="font-size:0.78rem; color:#6b7280;">{{ $contact->contactType?->name ?? '—' }}</td>
                <td style="font-size:0.78rem;">
                    @if($contact->email)
                    <a href="mailto:{{ $contact->email }}" style="color:#4c8bf5; text-decoration:none;">{{ $contact->email }}</a>
                    @else —
                    @endif
                </td>
                <td style="font-size:0.78rem; color:#6b7280;">{{ $contact->business_phone ?? $contact->mobile_phone ?? '—' }}</td>
                <td>
                    @if($contact->is_active)
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
                            <li><a class="dropdown-item" href="{{ route('contacts.show', $contact) }}">
                                <i class="bi bi-eye me-2"></i>View
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('contacts.edit', $contact) }}">
                                <i class="bi bi-pencil me-2"></i>Edit
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('contacts.destroy', $contact) }}" method="POST"
                                    onsubmit="return confirm('Delete {{ $contact->full_name }}?')">
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
                    <i class="bi bi-person-lines-fill fs-2 d-block mb-2"></i>
                    No contacts found.
                    <a href="{{ route('contacts.create') }}" class="d-block mt-2 text-primary" style="font-size:0.8rem;">
                        Add your first contact
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($contacts->hasPages())
<div class="d-flex justify-content-between align-items-center mt-3">
    <div style="font-size:0.75rem; color:#6b7280;">
        Showing {{ $contacts->firstItem() }}–{{ $contacts->lastItem() }} of {{ $contacts->total() }}
    </div>
    {{ $contacts->links() }}
</div>
@endif

@endsection
