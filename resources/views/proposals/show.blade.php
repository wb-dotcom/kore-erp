@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('proposals.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div class="flex-grow-1">
        <h4 class="mb-0 fw-700" style="font-size:1rem;">{{ $proposal->ref }} &mdash; {{ $proposal->title }}</h4>
        <div style="font-size:0.72rem; color:#6b7280;">
            Created {{ $proposal->created_at?->format('M d, Y') }}
            @if($proposal->createdBy) by {{ $proposal->createdBy->full_name }} @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('proposals.edit', $proposal) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        @if(!$proposal->project)
        <a href="{{ route('projects.create') }}?proposal_id={{ $proposal->id }}" class="btn btn-sm btn-success">
            <i class="bi bi-folder-plus me-1"></i> Convert to Project
        </a>
        @endif
    </div>
</div>

<div class="row g-4">

    {{-- Left: Main Details --}}
    <div class="col-lg-8">

        {{-- Details Card --}}
        <div class="kore-card mb-4">
            <div class="kore-card-header">
                <h5><i class="bi bi-info-circle me-2"></i>Proposal Details</h5>
                @php
                    $cls = match($proposal->status?->name) {
                        'Approved'  => 'approved',
                        'Submitted' => 'active',
                        'Rejected'  => 'rejected',
                        default     => 'draft',
                    };
                @endphp
                <span class="badge badge-{{ $cls }} fs-6">{{ $proposal->status?->name ?? '—' }}</span>
            </div>

            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Client</div>
                    <div style="font-size:0.85rem;">{{ $proposal->company?->name ?? '—' }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">PO Number</div>
                    <div style="font-size:0.85rem;">{{ $proposal->po_number ?? '—' }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Sector</div>
                    <div style="font-size:0.85rem;">{{ $proposal->sector?->name ?? '—' }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Work Type</div>
                    <div style="font-size:0.85rem;">{{ $proposal->workType?->name ?? '—' }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Account Manager</div>
                    <div style="font-size:0.85rem;">{{ $proposal->accountManager?->full_name ?? '—' }}</div>
                </div>
                <div class="col-sm-3">
                    <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Submitted</div>
                    <div style="font-size:0.85rem;">{{ $proposal->submitted_date?->format('M d, Y') ?? '—' }}</div>
                </div>
                <div class="col-sm-3">
                    <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Approved</div>
                    <div style="font-size:0.85rem;">{{ $proposal->approved_date?->format('M d, Y') ?? '—' }}</div>
                </div>
            </div>

            @if($proposal->description)
            <hr>
            <div class="text-muted mb-1" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Description</div>
            <div style="font-size:0.85rem; white-space:pre-line;">{{ $proposal->description }}</div>
            @endif

            @if($proposal->notes)
            <hr>
            <div class="text-muted mb-1" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Internal Notes</div>
            <div class="kore-alert kore-alert-info mb-0" style="font-size:0.8rem;">
                {{ $proposal->notes }}
            </div>
            @endif
        </div>

    </div>

    {{-- Right: Sidebar --}}
    <div class="col-lg-4">

        {{-- Status / Actions --}}
        <div class="kore-card mb-3">
            <div class="kore-card-header"><h5>Actions</h5></div>
            <a href="{{ route('proposals.edit', $proposal) }}" class="btn btn-outline-primary btn-sm w-100 mb-2">
                <i class="bi bi-pencil me-1"></i> Edit Proposal
            </a>
            @if(!$proposal->project)
            <a href="{{ route('projects.create') }}?proposal_id={{ $proposal->id }}" class="btn btn-success btn-sm w-100 mb-2">
                <i class="bi bi-folder-plus me-1"></i> Convert to Project
            </a>
            @endif
            <form action="{{ route('proposals.destroy', $proposal) }}" method="POST"
                onsubmit="return confirm('Delete this proposal permanently?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                    <i class="bi bi-trash me-1"></i> Delete
                </button>
            </form>
        </div>

        {{-- Associated Project --}}
        @if($proposal->project)
        <div class="kore-card mb-3">
            <div class="kore-card-header"><h5><i class="bi bi-folder2-open me-1"></i>Associated Project</h5></div>
            <a href="{{ route('projects.show', $proposal->project) }}" class="text-decoration-none d-flex align-items-center gap-2">
                <i class="bi bi-folder2-open text-primary"></i>
                <span style="font-size:0.82rem;">{{ $proposal->project->project_number }} — {{ $proposal->project->title }}</span>
            </a>
        </div>
        @endif

        {{-- Meta --}}
        <div class="kore-card">
            <div class="kore-card-header"><h5>Info</h5></div>
            @foreach([
                ['Reference',     $proposal->ref],
                ['Created by',    $proposal->createdBy?->full_name ?? '—'],
                ['Created',       $proposal->created_at?->format('M d, Y H:i')],
                ['Last updated',  $proposal->updated_at?->format('M d, Y H:i')],
            ] as [$label, $value])
            <div class="d-flex justify-content-between py-1 border-bottom">
                <span style="font-size:0.75rem; color:#6b7280;">{{ $label }}</span>
                <span style="font-size:0.75rem; font-weight:500;">{{ $value }}</span>
            </div>
            @endforeach
        </div>

    </div>
</div>

@endsection
