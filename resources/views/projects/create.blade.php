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

<form action="{{ route('projects.store') }}" method="POST" id="projectCreateForm">
    @csrf
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="kore-card mb-4">
                <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">
                    Project Details
                </div>
                <div class="row g-3">
                    <div class="col-sm-2">
                        <label class="form-label">Year</label>
                        <div class="form-control form-control-sm bg-light text-muted" id="proj_year_display"
                            style="cursor:default;">{{ $year }}</div>
                        <div class="form-text" style="font-size:0.65rem; color:#9ca3af;">System assigned</div>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">Project #</label>
                        <div class="form-control form-control-sm bg-light text-muted"
                            style="cursor:default;">{{ $nextNumber }}</div>
                        <div class="form-text" style="font-size:0.65rem; color:#9ca3af;">Auto-assigned</div>
                    </div>
                    <div class="col-sm-8">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="proj_title" class="form-control form-control-sm"
                            value="{{ old('title') }}" placeholder="Project name" required>
                        <div class="form-text" style="font-size:0.65rem; color:#9ca3af;" id="titleHint" style="display:none;">
                            Pre-filled from proposal — you can edit this.
                        </div>
                    </div>

                    {{-- Proposal Linking --}}
                    <div class="col-12">
                        <label class="form-label">Linked Proposal <span class="text-muted" style="font-weight:400;">(optional — links to billing)</span></label>
                        <select name="proposal_id" id="proposalSelect" class="form-select form-select-sm" onchange="onProposalChange()">
                            <option value="">— No proposal (non-billable internal project) —</option>
                            @foreach($proposals as $p)
                            <option value="{{ $p->id }}"
                                data-company="{{ $p->company_id }}"
                                data-year="{{ $p->year }}"
                                data-billing="{{ $p->billing_type }}"
                                data-title="{{ $p->title }}"
                                data-fee="{{ $p->total_fee ?? $p->contract_value ?? '' }}"
                                {{ (old('proposal_id', request('proposal_id')) == $p->id) ? 'selected' : '' }}>
                                {{ $p->ref }} — {{ $p->title }}
                                @if($p->company) ({{ $p->company->name }}) @endif
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text" style="font-size:0.68rem; color:#6b7280;">
                            <i class="bi bi-info-circle me-1"></i>Only approved proposals without a project are listed.
                        </div>
                    </div>

                    {{-- Billing Notice --}}
                    <div class="col-12" id="billingNotice">
                        <div class="kore-alert kore-alert-info" style="font-size:0.8rem;" id="nonBillableNotice">
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Non-Billable Project</strong> — No proposal linked. Client will default to
                            <strong>{{ $k5Company?->name ?? 'K5 Company' }}</strong> and project type to Non-Billable.
                        </div>
                        <div class="kore-alert" style="background:#f0fdf4; border:1px solid #bbf7d0; font-size:0.8rem; display:none;" id="billableNotice">
                            <i class="bi bi-check-circle me-1 text-success"></i>
                            <strong>Billable Project</strong> — Billing type and client will be inherited from the linked proposal.
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <label class="form-label">Client</label>
                        <select name="company_id" id="companySelect" class="form-select form-select-sm">
                            <option value="">— Select client —</option>
                            @foreach($companies as $c)
                            <option value="{{ $c->id }}"
                                {{ old('company_id', $k5Company?->id) == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-sm-3">
                        <label class="form-label">Project Type</label>
                        <select name="project_type_id" id="projectTypeSelect" class="form-select form-select-sm">
                            <option value="">— Auto —</option>
                            @foreach($projectTypes as $t)
                            <option value="{{ $t->id }}" {{ old('project_type_id') == $t->id ? 'selected' : '' }}>
                                {{ $t->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3">
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
                        <textarea name="notes" class="form-control form-control-sm" rows="3"
                            placeholder="Internal notes...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="kore-card mb-4" style="font-size:0.8rem; color:#6b7280;">
                <div class="fw-600 mb-2" style="color:#374151;">How Billing Works</div>
                <p class="mb-2">
                    <strong>With a proposal:</strong> Project inherits billing type, client, and year from the linked proposal. All time becomes billable.
                </p>
                <p class="mb-0">
                    <strong>Without a proposal:</strong> Project is automatically non-billable. Client defaults to K5 Company for internal tracking.
                </p>
                <hr class="my-2">
                <p class="mb-0">After creating, go to the project's <strong>WBS tab</strong> to add deliverables, milestones, and tasks with budget hours and rates.</p>
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

@push('scripts')
<script>
const K5_COMPANY_ID = {{ $k5Company?->id ?? 'null' }};

function onProposalChange() {
    const sel   = document.getElementById('proposalSelect');
    const opt   = sel.options[sel.selectedIndex];
    const pid   = sel.value;

    const nonBillableNotice = document.getElementById('nonBillableNotice');
    const billableNotice    = document.getElementById('billableNotice');
    const companySelect     = document.getElementById('companySelect');
    const yearDisplay       = document.getElementById('proj_year_display');
    const titleInput        = document.getElementById('proj_title');
    const titleHint         = document.getElementById('titleHint');
    const budgetInput       = document.querySelector('[name="total_budget"]');

    if (pid) {
        // Proposal selected → billable
        nonBillableNotice.style.display = 'none';
        billableNotice.style.display    = '';

        // Auto-fill company from proposal
        const companyId = opt.dataset.company;
        if (companyId && companySelect) {
            companySelect.value = companyId;
        }

        // Update year display from proposal
        const yr = opt.dataset.year;
        if (yr && yearDisplay) yearDisplay.textContent = yr;

        // Pre-populate title from proposal (only if currently empty or was proposal-filled)
        const proposalTitle = opt.dataset.title || '';
        if (titleInput && proposalTitle) {
            titleInput.value = proposalTitle;
            if (titleHint) titleHint.style.display = '';
        }

        // Pre-fill total budget from proposal fee
        const fee = opt.dataset.fee;
        if (budgetInput && fee) {
            budgetInput.value = parseFloat(fee).toFixed(2);
        }
    } else {
        // No proposal → non-billable
        nonBillableNotice.style.display = '';
        billableNotice.style.display    = 'none';

        // Reset year display
        if (yearDisplay) yearDisplay.textContent = '{{ $year }}';

        // Clear title hint
        if (titleHint) titleHint.style.display = 'none';

        // Default to K5 Company
        if (K5_COMPANY_ID && companySelect) {
            companySelect.value = K5_COMPANY_ID;
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    onProposalChange();
});
</script>
@endpush

@endsection
