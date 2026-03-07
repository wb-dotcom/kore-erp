@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0 fw-700" style="font-size:1rem;">
        Edit Project — {{ $project->year }}-{{ str_pad($project->project_number, 3, '0', STR_PAD_LEFT) }}
    </h4>
</div>

@if($errors->any())
<div class="alert alert-danger py-2" style="font-size:0.8rem;">
    <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form action="{{ route('projects.update', $project) }}" method="POST">
    @csrf @method('PUT')
    <div class="kore-card mb-4">
        <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">
            Project Details
        </div>
        <div class="row g-3">
            <div class="col-sm-2">
                <label class="form-label">Year <span class="text-danger">*</span></label>
                <input type="number" name="year" class="form-control form-control-sm"
                    value="{{ old('year', $project->year) }}" required>
            </div>
            <div class="col-sm-2">
                <label class="form-label">Project # <span class="text-danger">*</span></label>
                <input type="number" name="project_number" class="form-control form-control-sm"
                    value="{{ old('project_number', $project->project_number) }}" required>
            </div>
            <div class="col-sm-8">
                <label class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control form-control-sm"
                    value="{{ old('title', $project->title) }}" required>
            </div>
            <div class="col-sm-6">
                <label class="form-label">Client</label>
                <select name="company_id" class="form-select form-select-sm">
                    <option value="">— Select client —</option>
                    @foreach($companies as $c)
                    <option value="{{ $c->id }}" {{ old('company_id', $project->company_id) == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6">
                <label class="form-label">Linked Proposal</label>
                <select name="proposal_id" class="form-select form-select-sm">
                    <option value="">— None —</option>
                    @foreach($proposals as $p)
                    <option value="{{ $p->id }}" {{ old('proposal_id', $project->proposal_id) == $p->id ? 'selected' : '' }}>
                        {{ $p->year }}-{{ $p->proposal_number }} — {{ $p->title }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-4">
                <label class="form-label">Project Type</label>
                <select name="project_type_id" class="form-select form-select-sm">
                    <option value="">— Select —</option>
                    @foreach($projectTypes as $t)
                    <option value="{{ $t->id }}" {{ old('project_type_id', $project->project_type_id) == $t->id ? 'selected' : '' }}>
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
                    <option value="{{ $m->id }}" {{ old('project_manager_id', $project->project_manager_id) == $m->id ? 'selected' : '' }}>
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
                    <option value="{{ $s->id }}" {{ old('status_id', $project->status_id) == $s->id ? 'selected' : '' }}>
                        {{ $s->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-4">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control form-control-sm"
                    value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}">
            </div>
            <div class="col-sm-4">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control form-control-sm"
                    value="{{ old('end_date', $project->end_date?->format('Y-m-d')) }}">
            </div>
            <div class="col-sm-4">
                <label class="form-label">Total Budget ($)</label>
                <input type="number" step="0.01" name="total_budget" class="form-control form-control-sm"
                    value="{{ old('total_budget', $project->total_budget) }}">
            </div>
            <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control form-control-sm" rows="4">{{ old('notes', $project->notes) }}</textarea>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-check-lg me-1"></i> Save Changes
        </button>
        <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
    </div>
</form>

@endsection
