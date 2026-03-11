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
                    <label class="form-label">Contact Person</label>
                    <select name="contact_id" class="form-select form-select-sm">
                        <option value="">— Select Contact —</option>
                        @foreach($contacts as $ct)
                        <option value="{{ $ct->id }}" {{ old('contact_id', $proposal->contact_id) == $ct->id ? 'selected' : '' }}>
                            {{ $ct->full_name }}{{ $ct->company ? ' ('.$ct->company->name.')' : '' }}
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
                    <label class="form-label">Project Type</label>
                    <select name="project_type_id" class="form-select form-select-sm">
                        <option value="">— Select Type —</option>
                        @foreach($projectTypes as $pt)
                        <option value="{{ $pt->id }}" {{ old('project_type_id', $proposal->project_type_id) == $pt->id ? 'selected' : '' }}>
                            {{ $pt->name }}
                        </option>
                        @endforeach
                    </select>
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
                    <label class="form-label">Program</label>
                    <select name="program_id" class="form-select form-select-sm">
                        <option value="">— No Program —</option>
                        @foreach($programs as $prog)
                        <option value="{{ $prog->id }}" {{ old('program_id', $proposal->program_id) == $prog->id ? 'selected' : '' }}>
                            {{ $prog->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-sm-4">
                    <label class="form-label">Vendor Code</label>
                    <input type="text" name="vendor_code" class="form-control form-control-sm"
                        value="{{ old('vendor_code', $proposal->vendor_code) }}" placeholder="e.g. BPHGA">
                </div>

                <div class="col-sm-4">
                    <label class="form-label">Submitted Date</label>
                    <input type="date" name="submitted_date" class="form-control form-control-sm"
                        value="{{ old('submitted_date', $proposal->submitted_date?->format('Y-m-d')) }}">
                </div>

                <div class="col-sm-4">
                    <label class="form-label">Expiry Date</label>
                    <input type="date" name="expiry_date" class="form-control form-control-sm"
                        value="{{ old('expiry_date', $proposal->expiry_date?->format('Y-m-d')) }}">
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

        {{-- Billing & Financials --}}
        <div class="kore-card mt-4">
            <div class="kore-card-header"><h5><i class="bi bi-receipt me-2"></i>Billing &amp; Financials</h5></div>
            <div class="row g-3">
                <div class="col-sm-4">
                    <label class="form-label">Contract Value</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">$</span>
                        <input type="number" name="contract_value" class="form-control" step="0.01" min="0"
                            value="{{ old('contract_value', $proposal->contract_value) }}" placeholder="0.00">
                    </div>
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Expenses Reserve</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">$</span>
                        <input type="number" name="expenses_reserve" class="form-control" step="0.01" min="0"
                            value="{{ old('expenses_reserve', $proposal->expenses_reserve ?? 0) }}" placeholder="0.00">
                    </div>
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Payment Terms (days)</label>
                    <input type="number" name="payment_terms_days" class="form-control form-control-sm"
                        value="{{ old('payment_terms_days', $proposal->payment_terms_days ?? 30) }}" min="0" max="365">
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Billing Type</label>
                    <select name="billing_type" class="form-select form-select-sm">
                        <option value="">— Select —</option>
                        @foreach(['fixed' => 'Fixed Fee', 'time_and_material' => 'Time & Material', 'per_deliverable' => 'Per Deliverable', 'retainer' => 'Retainer', 'hybrid' => 'Hybrid'] as $val => $label)
                        <option value="{{ $val }}" {{ old('billing_type', $proposal->billing_type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Billing Cycle</label>
                    <select name="billing_cycle" class="form-select form-select-sm">
                        <option value="">— Select —</option>
                        @foreach(['biweekly' => 'Bi-Weekly (15 days)', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'on_completion' => 'On Completion', 'custom' => 'Custom'] as $val => $label)
                        <option value="{{ $val }}" {{ old('billing_cycle', $proposal->billing_cycle) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Google Doc URL</label>
                    <input type="url" name="google_doc_url" class="form-control form-control-sm"
                        value="{{ old('google_doc_url', $proposal->google_doc_url) }}"
                        placeholder="https://docs.google.com/document/d/...">
                </div>
            </div>
        </div>

        {{-- Proposal Content --}}
        <div class="kore-card mt-4">
            <div class="kore-card-header"><h5><i class="bi bi-file-text me-2"></i>Proposal Content</h5></div>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Executive Summary</label>
                    <textarea name="executive_summary" class="form-control form-control-sm" rows="4">{{ old('executive_summary', $proposal->executive_summary) }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Scope of Work</label>
                    <textarea name="scope_of_work" class="form-control form-control-sm" rows="6">{{ old('scope_of_work', $proposal->scope_of_work) }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Terms &amp; Conditions</label>
                    <textarea name="terms_and_conditions" class="form-control form-control-sm" rows="4">{{ old('terms_and_conditions', $proposal->terms_and_conditions) }}</textarea>
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
