@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('contacts.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h4 class="mb-0 fw-700" style="font-size:1rem;">{{ $contact->full_name }}</h4>
            <div style="font-size:0.75rem; color:#6b7280;">
                {{ $contact->title ?? 'Contact' }}
                @if($contact->company)
                    · <a href="{{ route('companies.show', $contact->company) }}"
                        class="text-decoration-none" style="color:#4c8bf5;">{{ $contact->company->name }}</a>
                @endif
            </div>
        </div>
    </div>
    <a href="{{ route('contacts.edit', $contact) }}" class="btn btn-sm btn-primary">
        <i class="bi bi-pencil me-1"></i> Edit
    </a>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

<div class="row g-4">
    <div class="col-lg-4">

        {{-- Contact Card --}}
        <div class="kore-card mb-4">
            <div class="text-center mb-3">
                <div style="width:64px; height:64px; border-radius:50%; background:#4c8bf5; color:#fff;
                    display:flex; align-items:center; justify-content:center; font-size:1.4rem; font-weight:700;
                    margin:0 auto 10px;">
                    {{ strtoupper(substr($contact->first_name,0,1).substr($contact->last_name,0,1)) }}
                </div>
                <div class="fw-600" style="font-size:1rem;">{{ $contact->full_name }}</div>
                <div style="font-size:0.8rem; color:#6b7280;">{{ $contact->title ?? '' }}</div>
                @if(!$contact->is_active)
                <span class="badge badge-draft mt-1">Inactive</span>
                @endif
            </div>

            <hr style="border-color:#f3f4f6;">

            @if($contact->email)
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-envelope" style="color:#6b7280; font-size:0.85rem; width:16px;"></i>
                <a href="mailto:{{ $contact->email }}" style="font-size:0.82rem; color:#4c8bf5; text-decoration:none;">
                    {{ $contact->email }}
                </a>
            </div>
            @endif
            @if($contact->business_phone)
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-telephone" style="color:#6b7280; font-size:0.85rem; width:16px;"></i>
                <span style="font-size:0.82rem; color:#374151;">{{ $contact->business_phone }}</span>
            </div>
            @endif
            @if($contact->mobile_phone)
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-phone" style="color:#6b7280; font-size:0.85rem; width:16px;"></i>
                <span style="font-size:0.82rem; color:#374151;">{{ $contact->mobile_phone }}</span>
            </div>
            @endif
            @if($contact->contactType)
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-tag" style="color:#6b7280; font-size:0.85rem; width:16px;"></i>
                <span style="font-size:0.82rem; color:#374151;">{{ $contact->contactType->name }}</span>
            </div>
            @endif

            @if($contact->notes)
            <hr style="border-color:#f3f4f6;">
            <div style="font-size:0.78rem; color:#6b7280; white-space:pre-wrap;">{{ $contact->notes }}</div>
            @endif
        </div>

        {{-- Actions --}}
        <div class="kore-card">
            <div class="d-grid gap-2">
                <a href="{{ route('contacts.edit', $contact) }}" class="btn btn-sm btn-outline-secondary text-start">
                    <i class="bi bi-pencil me-2"></i> Edit Contact
                </a>
                <form action="{{ route('contacts.destroy', $contact) }}" method="POST"
                    onsubmit="return confirm('Delete {{ $contact->full_name }}?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger text-start w-100">
                        <i class="bi bi-trash me-2"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Activity: Projects & Proposals --}}
    <div class="col-lg-8">

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

        @if(!$projects->count() && !$proposals->count())
        <div class="kore-card text-center py-5" style="color:#9ca3af; font-size:0.85rem;">
            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
            No projects or proposals linked to this contact's company yet.
        </div>
        @endif

    </div>
</div>

@endsection
