@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0 fw-700" style="font-size:1rem;">New Project</h4>
</div>

@if($errors->any())
<div class="alert alert-danger py-2" style="font-size:0.8rem;">
    <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form action="{{ route('projects.store') }}" method="POST">
    @csrf
    <div class="row g-4">

        {{-- Left column --}}
        <div class="col-lg-8">
            <div class="kore-card mb-4">
                <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">
                    Project Details
                </div>

                <div class="row g-3">
                    <div class="col-sm-2">
                        <label class="form-label">Year <span class="text-danger">*</span></label>
                        <input type="number" name="year" class="form-control form-control-sm"
                            value="{{ old('year', $year) }}" required>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">Project # <span class="text-danger">*</span></label>
                        <input type="number" name="project_number" class="form-control form-control-sm"
                            value="{{ old('project_number', $nextNumber) }}" required>
                    </div>
                    <div class="col-sm-8">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control form-control-sm"
                            value="{{ old('title') }}" placeholder="Project name" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Client</label>
                        <select name="company_id" class="form-select form-select-sm">
                            <option value="">— Select client —</option>
                            @foreach($companies as $c)
                            <option value="{{ $c->id }}" {{ old('company_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Approved Proposal <span class="text-danger">*</span></label>
                        <select name="proposal_id" class="form-select form-select-sm" required>
                            <option value="">— Select Approved Proposal —</option>
                            @foreach($proposals as $p)
                            <option value="{{ $p->id }}" {{ (old('proposal_id', request('proposal_id')) == $p->id) ? 'selected' : '' }}>
                                {{ $p->year }}-{{ $p->proposal_number }} — {{ $p->title }}
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text" style="font-size:0.68rem; color:#6b7280;">
                            <i class="bi bi-info-circle me-1"></i>Only approved proposals without an existing project are listed.
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Project Type</label>
                        <select name="project_type_id" class="form-select form-select-sm">
                            <option value="">— Select —</option>
                            @foreach($projectTypes as $t)
                            <option value="{{ $t->id }}" {{ old('project_type_id') == $t->id ? 'selected' : '' }}>
                                {{ $t->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Project Manager</label>
                        <select name="project_manager_id" class="form-select form-select-sm">
                            <option value="">— Select —</option>
                            @foreach($managers as $m)
                            <option value="{{ $m->id }}" {{ old('project_manager_id') == $m->id ? 'selected' : '' }}>
                                {{ $m->full_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status_id" class="form-select form-select-sm" required>
                            <option value="">— Select —</option>
                            @foreach($statuses as $s)
                            <option value="{{ $s->id }}" {{ old('status_id') == $s->id ? 'selected' : '' }}>
                                {{ $s->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control form-control-sm"
                            value="{{ old('start_date') }}">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control form-control-sm"
                            value="{{ old('end_date') }}">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Total Budget ($)</label>
                        <input type="number" step="0.01" name="total_budget" class="form-control form-control-sm"
                            value="{{ old('total_budget') }}" placeholder="0.00">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="4"
                            placeholder="Internal notes...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right column --}}
        <div class="col-lg-4">
            <div class="kore-card mb-4" style="font-size:0.8rem; color:#6b7280;">
                <div class="fw-600 mb-2" style="color:#374151;">About Projects</div>
                <p class="mb-2">Every project must be linked to an <strong>approved proposal</strong>. The proposal is the brain of the system — it defines rates, deliverables, and billing terms that drive timesheets and invoices.</p>
                <p class="mb-0">After creating the project, use the <strong>Deliverables</strong> tab to set up the work breakdown structure.</p>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-check-lg me-1"></i> Create Project
        </button>
        <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
    </div>
</form>

@endsection
