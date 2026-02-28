@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('proposals.show', $proposal) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">Edit Proposal</h4>
        <div style="font-size:0.72rem; color:#6b7280;">{{ $proposal->ref }}</div>
    </div>
</div>

<form action="{{ route('proposals.update', $proposal) }}" method="POST">
@csrf
@method('PUT')

<div class="row g-4">

    {{-- Main Details --}}
    <div class="col-lg-8">
        <div class="kore-card">
            <div class="kore-card-header">
                <h5>Proposal Details</h5>
            </div>

            <div class="row g-3">
                <div class="col-sm-3">
                    <label class="form-label required">Year</label>
                    <input type="number" name="year" class="form-control form-control-sm @error('year') is-invalid @enderror"
                        value="{{ old('year', $proposal->year) }}" min="2000" max="2099" required>
                    @error('year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-3">
                    <label class="form-label required">Proposal #</label>
                    <input type="number" name="proposal_number" class="form-control form-control-sm @error('proposal_number') is-invalid @enderror"
                        value="{{ old('proposal_number', $proposal->proposal_number) }}" min="1" required>
                    @error('proposal_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6">
                    <label class="form-label">PO Number</label>
                    <input type="text" name="po_number" class="form-control form-control-sm"
                        value="{{ old('po_number', $proposal->po_number) }}">
                </div>

                <div class="col-12">
                    <label class="form-label required">Title</label>
                    <input type="text" name="title" class="form-control form-control-sm @error('title') is-invalid @enderror"
                        value="{{ old('title', $proposal->title) }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-sm-6">
                    <label class="form-label">Client Company</label>
                    <select name="company_id" class="form-select form-select-sm">
                        <option value="">— Select Company —</option>
                        @foreach($companies as $c)
                        <option value="{{ $c->id }}" {{ old('company_id', $proposal->company_id) == $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-sm-6">
                    <label class="form-label required">Status</label>
                    <select name="status_id" class="form-select form-select-sm @error('status_id') is-invalid @enderror" required>
                        <option value="">— Select Status —</option>
                        @foreach($statuses as $s)
                        <option value="{{ $s->id }}" {{ old('status_id', $proposal->status_id) == $s->id ? 'selected' : '' }}>
                            {{ $s->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('status_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-sm-6">
                    <label class="form-label">Sector</label>
                    <select name="sector_id" class="form-select form-select-sm">
                        <option value="">— Select Sector —</option>
                        @foreach($sectors as $s)
                        <option value="{{ $s->id }}" {{ old('sector_id', $proposal->sector_id) == $s->id ? 'selected' : '' }}>
                            {{ $s->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-sm-6">
                    <label class="form-label">Work Type</label>
                    <select name="work_type_id" class="form-select form-select-sm">
                        <option value="">— Select Work Type —</option>
                        @foreach($workTypes as $w)
                        <option value="{{ $w->id }}" {{ old('work_type_id', $proposal->work_type_id) == $w->id ? 'selected' : '' }}>
                            {{ $w->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-sm-6">
                    <label class="form-label">Account Manager</label>
                    <select name="account_manager_id" class="form-select form-select-sm">
                        <option value="">— Select Manager —</option>
                        @foreach($managers as $m)
                        <option value="{{ $m->id }}" {{ old('account_manager_id', $proposal->account_manager_id) == $m->id ? 'selected' : '' }}>
                            {{ $m->full_name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-sm-6">
                    <label class="form-label">Submitted Date</label>
                    <input type="date" name="submitted_date" class="form-control form-control-sm"
                        value="{{ old('submitted_date', $proposal->submitted_date?->format('Y-m-d')) }}">
                </div>

                <div class="col-sm-6">
                    <label class="form-label">Approved Date</label>
                    <input type="date" name="approved_date" class="form-control form-control-sm"
                        value="{{ old('approved_date', $proposal->approved_date?->format('Y-m-d')) }}">
                </div>

                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control form-control-sm" rows="4">{{ old('description', $proposal->description) }}</textarea>
                </div>

                <div class="col-12">
                    <label class="form-label">Internal Notes</label>
                    <textarea name="notes" class="form-control form-control-sm" rows="3">{{ old('notes', $proposal->notes) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">
        <div class="kore-card">
            <div class="kore-card-header"><h5>Actions</h5></div>
            <button type="submit" class="btn btn-primary w-100 mb-2">
                <i class="bi bi-check-lg me-1"></i> Save Changes
            </button>
            <a href="{{ route('proposals.show', $proposal) }}" class="btn btn-outline-secondary w-100 mb-2">Cancel</a>
            <hr>
            <form action="{{ route('proposals.destroy', $proposal) }}" method="POST"
                onsubmit="return confirm('Permanently delete this proposal?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline-danger w-100 btn-sm">
                    <i class="bi bi-trash me-1"></i> Delete Proposal
                </button>
            </form>
        </div>

        <div class="kore-card mt-3">
            <div class="kore-card-header"><h5>Info</h5></div>
            <div class="d-flex justify-content-between py-1">
                <span style="font-size:0.75rem; color:#6b7280;">Created by</span>
                <span style="font-size:0.75rem;">{{ $proposal->createdBy?->full_name ?? '—' }}</span>
            </div>
            <div class="d-flex justify-content-between py-1">
                <span style="font-size:0.75rem; color:#6b7280;">Created</span>
                <span style="font-size:0.75rem;">{{ $proposal->created_at?->format('M d, Y') }}</span>
            </div>
            <div class="d-flex justify-content-between py-1">
                <span style="font-size:0.75rem; color:#6b7280;">Last updated</span>
                <span style="font-size:0.75rem;">{{ $proposal->updated_at?->format('M d, Y') }}</span>
            </div>
        </div>
    </div>

</div>
</form>

@push('styles')
<style>
.form-label { font-size: 0.75rem; font-weight: 600; margin-bottom: 4px; color: #374151; }
.form-label.required::after { content: ' *'; color: #ef4444; }
</style>
@endpush

@endsection
