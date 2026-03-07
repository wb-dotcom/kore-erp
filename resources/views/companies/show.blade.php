@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('companies.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h4 class="mb-0 fw-700" style="font-size:1rem;">{{ $company->name }}</h4>
            <div style="font-size:0.75rem; color:#6b7280;">
                {{ $company->sector?->name ?? 'Company' }}
                @if($company->region) · {{ $company->region->name }} @endif
                @if(!$company->is_active) · <span class="text-danger">Inactive</span> @endif
            </div>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('contacts.create') }}?company_id={{ $company->id }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-person-plus me-1"></i> Add Contact
        </a>
        <a href="{{ route('companies.edit', $company) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger py-2 mb-3" style="font-size:0.8rem;">{{ session('error') }}</div>
@endif

<div class="row g-4">

    {{-- Left: Company info --}}
    <div class="col-lg-4">
        <div class="kore-card mb-4">
            <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">
                Company Info
            </div>
            @if($company->phone)
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-telephone" style="color:#6b7280; width:16px;"></i>
                <span style="font-size:0.82rem;">{{ $company->phone }}</span>
            </div>
            @endif
            @if($company->website)
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-globe" style="color:#6b7280; width:16px;"></i>
                <a href="{{ $company->website }}" target="_blank" style="font-size:0.82rem; color:#4c8bf5; text-decoration:none;">
                    {{ $company->website }}
                </a>
            </div>
            @endif
            @if($company->address_line1)
            <div class="d-flex align-items-start gap-2 mb-2">
                <i class="bi bi-geo-alt" style="color:#6b7280; width:16px; margin-top:2px;"></i>
                <div style="font-size:0.82rem; color:#374151;">
                    {{ $company->address_line1 }}<br>
                    @if($company->address_line2){{ $company->address_line2 }}<br>@endif
                    {{ implode(', ', array_filter([$company->city, $company->state, $company->zip])) }}
                    @if($company->country && $company->country !== 'USA')<br>{{ $company->country }}@endif
                </div>
            </div>
            @endif

            <hr style="border-color:#f3f4f6;">

            <div class="row g-2 text-center">
                <div class="col-4">
                    <div style="font-size:1.3rem; font-weight:700; color:#374151;">{{ $contacts->count() }}</div>
                    <div style="font-size:0.7rem; color:#9ca3af;">Contacts</div>
                </div>
                <div class="col-4">
                    <div style="font-size:1.3rem; font-weight:700; color:#374151;">{{ $projects->count() }}</div>
                    <div style="font-size:0.7rem; color:#9ca3af;">Projects</div>
                </div>
                <div class="col-4">
                    <div style="font-size:1.3rem; font-weight:700; color:#374151;">{{ $proposals->count() }}</div>
                    <div style="font-size:0.7rem; color:#9ca3af;">Proposals</div>
                </div>
            </div>
        </div>

        <div class="kore-card">
            <div class="d-grid gap-2">
                <a href="{{ route('companies.edit', $company) }}" class="btn btn-sm btn-outline-secondary text-start">
                    <i class="bi bi-pencil me-2"></i> Edit Company
                </a>
                <form action="{{ route('companies.destroy', $company) }}" method="POST"
                    onsubmit="return confirm('Delete {{ $company->name }}? This cannot be undone.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger text-start w-100">
                        <i class="bi bi-trash me-2"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Right: Contacts + Projects + Proposals --}}
    <div class="col-lg-8">

        {{-- Contacts --}}
        <div class="kore-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="fw-600" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">
                    Contacts <span style="color:#9ca3af;">({{ $contacts->count() }})</span>
                </div>
                <a href="{{ route('contacts.create') }}" class="btn btn-sm btn-outline-primary" style="font-size:0.75rem;">
                    <i class="bi bi-plus-sm me-1"></i> Add
                </a>
            </div>
            @if($contacts->count())
            <table class="table kore-table mb-0">
                <thead>
                    <tr><th>Name</th><th>Title</th><th>Type</th><th>Email</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($contacts as $contact)
                    <tr>
                        <td>
                            <a href="{{ route('contacts.show', $contact) }}" class="text-decoration-none fw-600"
                                style="color:#4c8bf5; font-size:0.82rem;">
                                {{ $contact->full_name }}
                            </a>
                        </td>
                        <td style="font-size:0.78rem; color:#6b7280;">{{ $contact->title ?? '—' }}</td>
                        <td style="font-size:0.78rem; color:#6b7280;">{{ $contact->contactType?->name ?? '—' }}</td>
                        <td style="font-size:0.78rem;">
                            @if($contact->email)
                            <a href="mailto:{{ $contact->email }}" style="color:#4c8bf5; text-decoration:none;">{{ $contact->email }}</a>
                            @else —
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('contacts.edit', $contact) }}" class="btn btn-sm btn-light py-0">
                                <i class="bi bi-pencil" style="font-size:0.7rem;"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="text-center py-3" style="font-size:0.8rem; color:#9ca3af;">
                No contacts. <a href="{{ route('contacts.create') }}" class="text-primary">Add one</a>
            </div>
            @endif
        </div>

        {{-- Projects --}}
        @if($projects->count())
        <div class="kore-card mb-4">
            <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">
                Projects <span style="color:#9ca3af;">({{ $projects->count() }})</span>
            </div>
            <table class="table kore-table mb-0">
                <thead>
                    <tr><th>Ref</th><th>Title</th><th>Manager</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach($projects as $project)
                    <tr>
                        <td>
                            <a href="{{ route('projects.show', $project) }}" class="text-decoration-none fw-600"
                                style="color:#4c8bf5; font-size:0.8rem;">
                                {{ $project->year }}-{{ str_pad($project->project_number, 3, '0', STR_PAD_LEFT) }}
                            </a>
                        </td>
                        <td style="font-size:0.8rem;">{{ $project->title }}</td>
                        <td style="font-size:0.78rem; color:#6b7280;">{{ $project->projectManager?->full_name ?? '—' }}</td>
                        <td>
                            @php $cls = match($project->status?->name){
                                'Active'=>'active','Complete'=>'approved','On Hold'=>'draft','Cancelled'=>'rejected',default=>'draft'
                            }; @endphp
                            <span class="badge badge-{{ $cls }}">{{ $project->status?->name ?? '—' }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Proposals --}}
        @if($proposals->count())
        <div class="kore-card">
            <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">
                Proposals <span style="color:#9ca3af;">({{ $proposals->count() }})</span>
            </div>
            <table class="table kore-table mb-0">
                <thead>
                    <tr><th>Ref</th><th>Title</th><th>Submitted</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach($proposals as $proposal)
                    <tr>
                        <td>
                            <a href="{{ route('proposals.show', $proposal) }}" class="text-decoration-none fw-600"
                                style="color:#4c8bf5; font-size:0.8rem;">
                                {{ $proposal->year }}-{{ $proposal->proposal_number }}
                            </a>
                        </td>
                        <td style="font-size:0.8rem;">{{ $proposal->title }}</td>
                        <td style="font-size:0.78rem; color:#6b7280;">{{ $proposal->submitted_date?->format('M d, Y') ?? '—' }}</td>
                        <td>
                            @php $cls = match($proposal->status?->name){
                                'Approved'=>'approved','Submitted'=>'active','Rejected'=>'rejected',default=>'draft'
                            }; @endphp
                            <span class="badge badge-{{ $cls }}">{{ $proposal->status?->name ?? '—' }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

    </div>
</div>

@endsection
