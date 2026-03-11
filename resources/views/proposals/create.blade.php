@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('proposals.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">New Proposal</h4>
        <div style="font-size:0.72rem; color:#6b7280;">Auto-reference: P{{ $year }}-{{ str_pad($nextNumber, 3, '0', STR_PAD_LEFT) }}</div>
    </div>
</div>

<form action="{{ route('proposals.store') }}" method="POST">
@csrf

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
                        value="{{ old('year', $year) }}" min="2000" max="2099" required>
                    @error('year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-3">
                    <label class="form-label required">Proposal #</label>
                    <input type="number" name="proposal_number" class="form-control form-control-sm @error('proposal_number') is-invalid @enderror"
                        value="{{ old('proposal_number', $nextNumber) }}" min="1" required>
                    @error('proposal_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-6">
                    <label class="form-label">PO Number</label>
                    <input type="text" name="po_number" class="form-control form-control-sm"
                        value="{{ old('po_number') }}" placeholder="Client PO #">
                </div>

                <div class="col-12">
                    <label class="form-label required">Title</label>
                    <input type="text" name="title" class="form-control form-control-sm @error('title') is-invalid @enderror"
                        value="{{ old('title') }}" placeholder="Proposal title..." required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-sm-6">
                    <label class="form-label">Client Company</label>
                    <select name="company_id" id="companySelect" class="form-select form-select-sm">
                        <option value="">— Select Company —</option>
                        @foreach($companies as $c)
                        <option value="{{ $c->id }}" {{ old('company_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-sm-6">
                    <label class="form-label">Contact Person</label>
                    <select name="contact_id" id="contactSelect" class="form-select form-select-sm">
                        <option value="">— Select Contact —</option>
                        @foreach($contacts as $ct)
                        <option value="{{ $ct->id }}" data-company="{{ $ct->company_id }}"
                            {{ old('contact_id') == $ct->id ? 'selected' : '' }}>
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
                        <option value="{{ $s->id }}" {{ old('status_id') == $s->id ? 'selected' : '' }}>
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
                        <option value="{{ $pt->id }}" {{ old('project_type_id') == $pt->id ? 'selected' : '' }}>
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
                        <option value="{{ $s->id }}" {{ old('sector_id') == $s->id ? 'selected' : '' }}>
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
                        <option value="{{ $w->id }}" {{ old('work_type_id') == $w->id ? 'selected' : '' }}>
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
                        <option value="{{ $m->id }}" {{ old('account_manager_id') == $m->id ? 'selected' : '' }}>
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
                        <option value="{{ $prog->id }}" {{ old('program_id') == $prog->id ? 'selected' : '' }}>
                            {{ $prog->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-sm-4">
                    <label class="form-label">Vendor Code</label>
                    <input type="text" name="vendor_code" class="form-control form-control-sm"
                        value="{{ old('vendor_code') }}" placeholder="e.g. BPHGA">
                </div>

                <div class="col-sm-4">
                    <label class="form-label">Submitted Date</label>
                    <input type="date" name="submitted_date" class="form-control form-control-sm"
                        value="{{ old('submitted_date') }}">
                </div>

                <div class="col-sm-4">
                    <label class="form-label">Expiry Date</label>
                    <input type="date" name="expiry_date" class="form-control form-control-sm"
                        value="{{ old('expiry_date') }}">
                </div>

                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control form-control-sm" rows="4"
                        placeholder="Scope, objectives, deliverables...">{{ old('description') }}</textarea>
                </div>

                <div class="col-12">
                    <label class="form-label">Internal Notes</label>
                    <textarea name="notes" class="form-control form-control-sm" rows="3"
                        placeholder="Internal notes only...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Billing Terms --}}
        <div class="kore-card mt-4">
            <div class="kore-card-header"><h5><i class="bi bi-receipt me-2"></i>Billing &amp; Financials</h5></div>
            <div class="row g-3">
                <div class="col-sm-4">
                    <label class="form-label">Contract Value</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">$</span>
                        <input type="number" name="contract_value" class="form-control" step="0.01" min="0"
                            value="{{ old('contract_value') }}" placeholder="0.00">
                    </div>
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Expenses Reserve</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">$</span>
                        <input type="number" name="expenses_reserve" class="form-control" step="0.01" min="0"
                            value="{{ old('expenses_reserve', 0) }}" placeholder="0.00">
                    </div>
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Payment Terms (days)</label>
                    <input type="number" name="payment_terms_days" class="form-control form-control-sm"
                        value="{{ old('payment_terms_days', 30) }}" min="0" max="365" placeholder="30">
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Billing Type</label>
                    <select name="billing_type" class="form-select form-select-sm">
                        <option value="">— Select —</option>
                        <option value="fixed" {{ old('billing_type') === 'fixed' ? 'selected' : '' }}>Fixed Fee</option>
                        <option value="time_and_material" {{ old('billing_type') === 'time_and_material' ? 'selected' : '' }}>Time &amp; Material</option>
                        <option value="per_deliverable" {{ old('billing_type') === 'per_deliverable' ? 'selected' : '' }}>Per Deliverable</option>
                        <option value="retainer" {{ old('billing_type') === 'retainer' ? 'selected' : '' }}>Retainer</option>
                        <option value="hybrid" {{ old('billing_type') === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                    </select>
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Billing Cycle</label>
                    <select name="billing_cycle" class="form-select form-select-sm">
                        <option value="">— Select —</option>
                        <option value="biweekly" {{ old('billing_cycle') === 'biweekly' ? 'selected' : '' }}>Bi-Weekly (15 days)</option>
                        <option value="monthly" {{ old('billing_cycle') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="quarterly" {{ old('billing_cycle') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                        <option value="on_completion" {{ old('billing_cycle') === 'on_completion' ? 'selected' : '' }}>On Completion</option>
                        <option value="custom" {{ old('billing_cycle') === 'custom' ? 'selected' : '' }}>Custom</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Proposal Content --}}
        <div class="kore-card mt-4">
            <div class="kore-card-header"><h5><i class="bi bi-file-text me-2"></i>Proposal Content</h5></div>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Executive Summary</label>
                    <textarea name="executive_summary" class="form-control form-control-sm" rows="4"
                        placeholder="Brief overview of the engagement, objectives, and value proposition...">{{ old('executive_summary') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Scope of Work</label>
                    <textarea name="scope_of_work" class="form-control form-control-sm" rows="6"
                        placeholder="Detailed scope of services, deliverables, and inclusions/exclusions...">{{ old('scope_of_work') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Terms &amp; Conditions</label>
                    <textarea name="terms_and_conditions" class="form-control form-control-sm" rows="4"
                        placeholder="Contractual terms, liability, IP ownership, payment conditions...">{{ old('terms_and_conditions') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">
        <div class="kore-card">
            <div class="kore-card-header"><h5>Actions</h5></div>
            <button type="submit" class="btn btn-primary w-100 mb-2">
                <i class="bi bi-check-lg me-1"></i> Create Proposal
            </button>
            <a href="{{ route('proposals.index') }}" class="btn btn-outline-secondary w-100">Cancel</a>
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
