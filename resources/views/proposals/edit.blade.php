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

<form action="{{ route('proposals.update', $proposal) }}" method="POST" id="proposalForm">
@csrf
@method('PUT')

<div class="row g-4">

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- MAIN COLUMN                                                 --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="col-lg-8">

    {{-- ── SECTION 1: PROPOSAL IDENTITY ──────────────────────── --}}
    <div class="kore-card">
        <div class="kore-card-header">
            <h5><i class="bi bi-file-earmark-text me-2"></i>Proposal Overview</h5>
        </div>
        <div class="row g-3">
            <div class="col-sm-2">
                <label class="form-label required">Year</label>
                <input type="number" name="year" class="form-control form-control-sm @error('year') is-invalid @enderror"
                    value="{{ old('year', $proposal->year) }}" min="2000" max="2099" required>
                @error('year')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-2">
                <label class="form-label required">Proposal #</label>
                <input type="number" name="proposal_number" class="form-control form-control-sm @error('proposal_number') is-invalid @enderror"
                    value="{{ old('proposal_number', $proposal->proposal_number) }}" min="1" required>
                @error('proposal_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-4">
                <label class="form-label required">Status</label>
                <select name="status_id" id="statusSelect" class="form-select form-select-sm @error('status_id') is-invalid @enderror" required>
                    <option value="">— Select Status —</option>
                    @foreach($statuses as $s)
                    <option value="{{ $s->id }}"
                        data-name="{{ strtolower($s->name) }}"
                        {{ old('status_id', $proposal->status_id) == $s->id ? 'selected' : '' }}>
                        {{ $s->name }}
                    </option>
                    @endforeach
                </select>
                @error('status_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-4">
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

            <div class="col-12">
                <label class="form-label required">Proposal Title</label>
                <input type="text" name="title" class="form-control form-control-sm @error('title') is-invalid @enderror"
                    value="{{ old('title', $proposal->title) }}" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                <div class="form-text" style="font-size:0.68rem;">Proposal validity deadline</div>
            </div>
            <div class="col-sm-4">
                <label class="form-label d-flex align-items-center gap-1">
                    PO Number
                    <span id="poNumberBadge" class="badge d-none" style="font-size:0.6rem; font-weight:500;"></span>
                </label>
                <input type="text" name="po_number" id="poNumberInput" class="form-control form-control-sm"
                    value="{{ old('po_number', $proposal->po_number) }}" placeholder="Client PO #">
                <div id="poNumberHint" class="form-text" style="font-size:0.68rem; color:#9ca3af;">
                    <i class="bi bi-info-circle me-1"></i>Typically provided by client upon approval
                </div>
            </div>
        </div>
    </div>

    {{-- ── SECTION 2: CLIENT & ENGAGEMENT ────────────────────── --}}
    <div class="kore-card mt-4">
        <div class="kore-card-header">
            <h5><i class="bi bi-building me-2"></i>Client &amp; Engagement</h5>
        </div>
        <div class="row g-3">
            <div class="col-sm-6">
                <label class="form-label">Client Company</label>
                <select name="company_id" id="companySelect" class="form-select form-select-sm" onchange="onCompanyChange()">
                    <option value="">— Select Company —</option>
                    @foreach($companies as $c)
                    <option value="{{ $c->id }}"
                        data-vendor-code="{{ $c->vendor_code }}"
                        {{ old('company_id', $proposal->company_id) == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6">
                <label class="form-label">Primary Contact</label>
                <select name="contact_id" id="contactSelect" class="form-select form-select-sm">
                    <option value="">— Select Contact —</option>
                    @foreach($contacts as $ct)
                    <option value="{{ $ct->id }}" data-company="{{ $ct->company_id }}"
                        {{ old('contact_id', $proposal->contact_id) == $ct->id ? 'selected' : '' }}>
                        {{ $ct->full_name }}{{ $ct->company ? ' ('.$ct->company->name.')' : '' }}
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
                <label class="form-label">Project Type</label>
                <select name="project_type_id" class="form-select form-select-sm">
                    <option value="">— Select Type —</option>
                    @foreach($projectTypes->filter(fn($pt) => !in_array(strtolower($pt->name), ['billable', 'non-billable'])) as $pt)
                    <option value="{{ $pt->id }}" {{ old('project_type_id', $proposal->project_type_id) == $pt->id ? 'selected' : '' }}>
                        {{ $pt->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="col-sm-4">
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
            <div class="col-sm-4">
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
                <div class="form-text" style="font-size:0.68rem;">Client's vendor registry ID</div>
            </div>

            <div class="col-12">
                <label class="form-label">Brief Description</label>
                <textarea name="description" class="form-control form-control-sm" rows="3"
                    placeholder="High-level summary of the engagement...">{{ old('description', $proposal->description) }}</textarea>
                <div class="form-text" style="font-size:0.68rem;">2–4 sentence overview. Detailed scope is in the Content tab.</div>
            </div>
        </div>
    </div>

    {{-- ── SECTION 3: BILLING & TERMS ─────────────────────────── --}}
    <div class="kore-card mt-4">
        <div class="kore-card-header">
            <h5><i class="bi bi-receipt me-2"></i>Billing &amp; Terms</h5>
        </div>
        <div class="row g-3">
            {{-- Fee Schedule selector --}}
            <div class="col-12">
                <label class="form-label fw-600">
                    <i class="bi bi-cash-stack me-1 text-warning"></i> Schedule of Fees
                </label>
                <select name="fee_schedule_id" class="form-select form-select-sm">
                    <option value="">— No schedule / manual rates —</option>
                    @foreach($feeSchedules as $fs)
                    <option value="{{ $fs->id }}"
                        {{ old('fee_schedule_id', $proposal->fee_schedule_id) == $fs->id ? 'selected' : '' }}>
                        {{ $fs->name }}{{ $fs->is_default ? ' (Default)' : '' }}
                    </option>
                    @endforeach
                </select>
                <div class="form-text" style="font-size:0.68rem;">
                    Determines hourly rates for T&amp;M deliverables. Manage schedules in
                    <a href="{{ route('admin.fee-schedules.index') }}" target="_blank">Admin → Fee Schedules</a>.
                </div>
            </div>

            <div class="col-sm-5">
                <label class="form-label">Billing Type</label>
                <select name="billing_type" id="billingType" class="form-select form-select-sm">
                    <option value="">— Select Billing Type —</option>
                    <option value="fixed"             {{ old('billing_type', $proposal->billing_type) === 'fixed'             ? 'selected' : '' }}>Fixed Fee</option>
                    <option value="time_and_material" {{ old('billing_type', $proposal->billing_type) === 'time_and_material' ? 'selected' : '' }}>Time &amp; Material</option>
                    <option value="per_deliverable"   {{ old('billing_type', $proposal->billing_type) === 'per_deliverable'   ? 'selected' : '' }}>Per Deliverable</option>
                    <option value="retainer"          {{ old('billing_type', $proposal->billing_type) === 'retainer'          ? 'selected' : '' }}>Retainer</option>
                    <option value="hybrid"            {{ old('billing_type', $proposal->billing_type) === 'hybrid'            ? 'selected' : '' }}>Hybrid</option>
                </select>
            </div>
            <div class="col-sm-7">
                <div id="billingTypeContext" class="rounded p-2 d-none" style="font-size:0.75rem;">
                    <div id="billingTypeContextText"></div>
                </div>
            </div>

            <div class="col-sm-4">
                <label class="form-label" id="contractValueLabel">Contract Value</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">$</span>
                    <input type="number" name="contract_value" id="contractValueInput"
                        class="form-control" step="0.01" min="0"
                        value="{{ old('contract_value', $proposal->contract_value) }}" placeholder="0.00">
                </div>
                <div class="form-text" id="contractValueHint" style="font-size:0.68rem;"></div>
            </div>
            <div class="col-sm-4">
                <label class="form-label">Expenses Reserve</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">$</span>
                    <input type="number" name="expenses_reserve" class="form-control" step="0.01" min="0"
                        value="{{ old('expenses_reserve', $proposal->expenses_reserve ?? 0) }}" placeholder="0.00">
                </div>
                <div class="form-text" style="font-size:0.68rem;">Reimbursable expenses budget</div>
            </div>
            <div class="col-sm-4">
                <label class="form-label">Payment Terms</label>
                <div class="input-group input-group-sm">
                    <input type="number" name="payment_terms_days" class="form-control"
                        value="{{ old('payment_terms_days', $proposal->payment_terms_days ?? 30) }}" min="0" max="365">
                    <span class="input-group-text">days</span>
                </div>
                <div class="form-text" style="font-size:0.68rem;">e.g. 30 = Net-30</div>
            </div>

            <div class="col-sm-5">
                <label class="form-label">Billing Cycle</label>
                <select name="billing_cycle" id="billingCycle" class="form-select form-select-sm">
                    <option value="">— Select Cycle —</option>
                    <option value="biweekly"      {{ old('billing_cycle', $proposal->billing_cycle) === 'biweekly'      ? 'selected' : '' }}>Bi-Weekly (every 2 weeks)</option>
                    <option value="monthly"       {{ old('billing_cycle', $proposal->billing_cycle) === 'monthly'       ? 'selected' : '' }}>Monthly</option>
                    <option value="quarterly"     {{ old('billing_cycle', $proposal->billing_cycle) === 'quarterly'     ? 'selected' : '' }}>Quarterly</option>
                    <option value="on_completion" {{ old('billing_cycle', $proposal->billing_cycle) === 'on_completion' ? 'selected' : '' }}>On Completion</option>
                    <option value="custom"        {{ old('billing_cycle', $proposal->billing_cycle) === 'custom'        ? 'selected' : '' }}>Custom / Milestone-Based</option>
                </select>
                <div class="form-text" id="billingCycleHint" style="font-size:0.68rem;"></div>
            </div>

            <div class="col-sm-4 d-none" id="retainerHoursField">
                <label class="form-label">Hours Included / Period</label>
                <div class="input-group input-group-sm">
                    <input type="number" name="retainer_hours" class="form-control"
                        value="{{ old('retainer_hours') }}" min="0" placeholder="e.g. 40">
                    <span class="input-group-text">hrs</span>
                </div>
                <div class="form-text" style="font-size:0.68rem;">Hours covered each billing period</div>
            </div>

            <div class="col-sm-4 d-none" id="nteNoteField">
                <div class="rounded p-2" style="background:#fefce8; border:1px solid #fde68a; font-size:0.72rem;">
                    <i class="bi bi-clock me-1 text-warning"></i>
                    <strong>T&amp;M Tip:</strong> Rates configured in the <em>Fee Worksheet</em> tab.
                </div>
            </div>

            <div class="col-12">
                <label class="form-label">Terms &amp; Conditions</label>
                <textarea name="terms_and_conditions" class="form-control form-control-sm" rows="4"
                    id="termsTextarea">{{ old('terms_and_conditions', $proposal->terms_and_conditions) }}</textarea>
                <div class="form-text" id="termsHint" style="font-size:0.68rem; color:#6b7280;">
                    Key contractual terms. Full version in the Content tab.
                </div>
            </div>

            {{-- Google Doc URL --}}
            <div class="col-12">
                <label class="form-label">Google Doc URL</label>
                <input type="url" name="google_doc_url" class="form-control form-control-sm"
                    value="{{ old('google_doc_url', $proposal->google_doc_url) }}"
                    placeholder="https://docs.google.com/document/d/...">
                <div class="form-text" style="font-size:0.68rem;">Link to external proposal document (if applicable)</div>
            </div>
        </div>
    </div>

    {{-- ── SECTION 4: PROPOSAL CONTENT ────────────────────────── --}}
    <div class="kore-card mt-4">
        <div class="kore-card-header">
            <h5><i class="bi bi-file-text me-2"></i>Proposal Content</h5>
            <span class="badge bg-secondary" style="font-size:0.65rem;">Also editable in Content tab</span>
        </div>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Executive Summary</label>
                <textarea name="executive_summary" class="form-control form-control-sm" rows="4"
                    placeholder="Brief overview of the engagement, objectives, and value proposition...">{{ old('executive_summary', $proposal->executive_summary) }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Scope of Work</label>
                <textarea name="scope_of_work" class="form-control form-control-sm" rows="6"
                    placeholder="Detailed scope of services, deliverables, inclusions and exclusions...">{{ old('scope_of_work', $proposal->scope_of_work) }}</textarea>
            </div>
        </div>
    </div>

    {{-- ── SECTION 5: INTERNAL NOTES ──────────────────────────── --}}
    <div class="kore-card mt-4">
        <div class="kore-card-header">
            <h5><i class="bi bi-lock me-2"></i>Internal Notes</h5>
            <span class="badge bg-secondary" style="font-size:0.65rem;">Not visible to client</span>
        </div>
        <textarea name="notes" class="form-control form-control-sm" rows="3"
            placeholder="Win strategy, pricing rationale, competitive notes...">{{ old('notes', $proposal->notes) }}</textarea>
    </div>

</div>{{-- /col-lg-8 --}}

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- SIDEBAR                                                     --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="col-lg-4">

    <div class="kore-card mb-4">
        <div class="kore-card-header"><h5>Actions</h5></div>
        <button type="submit" class="btn btn-primary w-100 mb-2">
            <i class="bi bi-check-lg me-1"></i> Save Changes
        </button>
        <a href="{{ route('proposals.show', $proposal) }}" class="btn btn-outline-secondary w-100 mb-3">Cancel</a>
        <hr class="my-2">
        <button type="button" class="btn btn-outline-danger w-100 btn-sm"
            onclick="if(confirm('Permanently delete this proposal?')) document.getElementById('deleteProposalForm').submit()">
            <i class="bi bi-trash me-1"></i> Delete Proposal
        </button>
    </div>

    <div class="kore-card mb-4">
        <div class="kore-card-header"><h5>Info</h5></div>
        @foreach([
            ['Created by', $proposal->createdBy?->full_name ?? '—'],
            ['Created',    $proposal->created_at?->format('M d, Y')],
            ['Updated',    $proposal->updated_at?->format('M d, Y H:i')],
        ] as [$label, $value])
        <div class="d-flex justify-content-between py-1 border-bottom">
            <span style="font-size:0.75rem; color:#6b7280;">{{ $label }}</span>
            <span style="font-size:0.75rem; font-weight:500;">{{ $value }}</span>
        </div>
        @endforeach
    </div>

    {{-- Dynamic billing type summary card --}}
    <div class="kore-card d-none" id="billingTypeSummaryCard">
        <div class="kore-card-header">
            <h5 id="billingTypeSummaryTitle"><i class="bi bi-info-circle me-2"></i>Billing Guide</h5>
        </div>
        <div id="billingTypeSummaryContent" style="font-size:0.75rem; color:#374151;"></div>
    </div>

</div>{{-- /col-lg-4 --}}

</div>{{-- /row --}}
</form>

{{-- Standalone delete form — must be OUTSIDE the main form to avoid nested form issues --}}
<form id="deleteProposalForm" action="{{ route('proposals.destroy', $proposal) }}" method="POST" class="d-none">
    @csrf @method('DELETE')
</form>

@push('styles')
<style>
.form-label { font-size: 0.75rem; font-weight: 600; margin-bottom: 4px; color: #374151; }
.form-label.required::after { content: ' *'; color: #ef4444; }
.form-text { margin-top: 3px; }
</style>
@endpush

@push('scripts')
<script>
/* ════════════════════════════════════════════════════════════════
   BILLING TYPE INTELLIGENCE
   ════════════════════════════════════════════════════════════════ */
const BILLING_CONFIGS = {
    fixed: {
        label:       'Fixed Fee',
        contextIcon: 'bi-lock-fill',
        contextColor:'#f0fdf4',
        contextBorder:'#86efac',
        context:     '<strong>Fixed Fee</strong> — Client pays an agreed total price regardless of hours worked.',
        contractLabel: 'Fixed Contract Amount',
        contractPlaceholder: 'e.g. 75000',
        contractHint:  'Total agreed price for the entire engagement (e.g. $75,000)',
        cycles:       ['on_completion', 'custom'],
        cycleHint:    'Fixed fee projects bill on completion or at predefined milestones.',
        termsHint:    'Include: what triggers final payment, revision limits, and change-order process.',
        summaryTitle: 'Fixed Fee Guide',
        summaryContent: `<ul class="ps-3 mb-0" style="font-size:0.73rem;"><li class="mb-1">Set the total contract amount above</li><li class="mb-1">Configure milestone payments in the <strong>Billing Schedule</strong> tab</li><li class="mb-1">Define scope clearly — change orders require amendment</li><li>Standard: Net-30 payment terms</li></ul>`,
        showRetainerHours: false,
        showNteNote: false,
    },
    time_and_material: {
        label:       'Time & Material',
        contextIcon: 'bi-clock',
        contextColor:'#eff6ff',
        contextBorder:'#93c5fd',
        context:     '<strong>Time &amp; Material</strong> — Billed for actual hours × rates. Consider a Not-to-Exceed cap to protect client budget.',
        contractLabel: 'Not-to-Exceed Cap (optional)',
        contractPlaceholder: 'e.g. 120000',
        contractHint:  'Maximum billable amount. Leave blank if uncapped. Actual billing = hours × rate.',
        cycles:       ['biweekly', 'monthly'],
        cycleHint:    'T&M typically bills bi-weekly or monthly based on logged hours.',
        termsHint:    'Include: NTE cap, overtime policy, rate adjustment notice, and expense approval threshold.',
        summaryTitle: 'T&M Billing Guide',
        summaryContent: `<ul class="ps-3 mb-0" style="font-size:0.73rem;"><li class="mb-1">Set hourly rates per role in the <strong>Fee Worksheet</strong> tab</li><li class="mb-1">Use Contract Value as NTE cap</li><li class="mb-1">Bill bi-weekly or monthly against actual hours</li><li>Attach timesheets to each invoice</li></ul>`,
        showRetainerHours: false,
        showNteNote: true,
    },
    per_deliverable: {
        label:       'Per Deliverable',
        contextIcon: 'bi-boxes',
        contextColor:'#faf5ff',
        contextBorder:'#c4b5fd',
        context:     '<strong>Per Deliverable</strong> — Fixed price per deliverable; client pays upon acceptance of each item.',
        contractLabel: 'Total Contract Amount',
        contractPlaceholder: 'e.g. 90000',
        contractHint:  'Sum of all deliverable prices (e.g. Phase 1 $30k + Phase 2 $60k = $90k)',
        cycles:       ['on_completion', 'custom'],
        cycleHint:    'Invoice triggered upon acceptance of each deliverable.',
        termsHint:    'Include: acceptance criteria per deliverable, revision rounds, and rejection/remedy process.',
        summaryTitle: 'Per-Deliverable Guide',
        summaryContent: `<ul class="ps-3 mb-0" style="font-size:0.73rem;"><li class="mb-1">List each deliverable with fixed price in <strong>Fee Worksheet</strong></li><li class="mb-1">Invoice upon formal client acceptance</li><li class="mb-1">Define acceptance criteria and revision limits in T&C</li><li>Consider a holdback released on final acceptance</li></ul>`,
        showRetainerHours: false,
        showNteNote: false,
    },
    retainer: {
        label:       'Retainer',
        contextIcon: 'bi-arrow-repeat',
        contextColor:'#fff7ed',
        contextBorder:'#fdba74',
        context:     '<strong>Retainer</strong> — Client pays a recurring fixed fee each period for ongoing access to services.',
        contractLabel: 'Retainer Amount (per period)',
        contractPlaceholder: 'e.g. 10000',
        contractHint:  'Amount billed per period (e.g. $10,000/month) — not a total project value.',
        cycles:       ['monthly', 'quarterly'],
        cycleHint:    'Retainers are typically monthly.',
        termsHint:    'Include: hours per period, rollover policy, scope of services, and termination notice (e.g. 30 days).',
        summaryTitle: 'Retainer Guide',
        summaryContent: `<ul class="ps-3 mb-0" style="font-size:0.73rem;"><li class="mb-1">Enter recurring amount per period above</li><li class="mb-1">Set hours included per period below</li><li class="mb-1">Generate recurring invoices in <strong>Billing Schedule</strong> tab</li><li>Document rollover policy in T&C</li></ul>`,
        showRetainerHours: true,
        showNteNote: false,
    },
    hybrid: {
        label:       'Hybrid',
        contextIcon: 'bi-puzzle',
        contextColor:'#f0fdf4',
        contextBorder:'#6ee7b7',
        context:     '<strong>Hybrid</strong> — Mixed billing: fixed fee for some phases, T&M for others. Common in phased engagements.',
        contractLabel: 'Estimated Total Contract Value',
        contractPlaceholder: 'e.g. 150000',
        contractHint:  'Combined estimate (fixed + T&M cap). Actual T&M portions may vary.',
        cycles:       ['monthly', 'custom'],
        cycleHint:    'Hybrid typically uses milestones for fixed phases + monthly for T&M phases.',
        termsHint:    'Specify which phases are fixed vs. T&M. Include a per-phase NTE cap for T&M portions.',
        summaryTitle: 'Hybrid Billing Guide',
        summaryContent: `<ul class="ps-3 mb-0" style="font-size:0.73rem;"><li class="mb-1">Define fixed vs. T&M phases in the <strong>Fee Worksheet</strong></li><li class="mb-1">Set hourly rates for T&M phases; fixed amounts for fixed phases</li><li class="mb-1">Milestone billing for fixed + monthly for T&M</li><li>Include T&M NTE cap per phase in T&C</li></ul>`,
        showRetainerHours: false,
        showNteNote: false,
    }
};

function applyBillingTypeIntelligence(type) {
    const cfg = BILLING_CONFIGS[type];
    const ctxBox    = document.getElementById('billingTypeContext');
    const ctxText   = document.getElementById('billingTypeContextText');
    const cvLabel   = document.getElementById('contractValueLabel');
    const cvInput   = document.getElementById('contractValueInput');
    const cvHint    = document.getElementById('contractValueHint');
    const cycleEl   = document.getElementById('billingCycle');
    const cycleHint = document.getElementById('billingCycleHint');
    const termsHint = document.getElementById('termsHint');
    const retainerF = document.getElementById('retainerHoursField');
    const nteNote   = document.getElementById('nteNoteField');
    const sumCard   = document.getElementById('billingTypeSummaryCard');
    const sumTitle  = document.getElementById('billingTypeSummaryTitle');
    const sumContent= document.getElementById('billingTypeSummaryContent');

    if (!cfg) {
        ctxBox.classList.add('d-none');
        cvLabel.textContent = 'Contract Value';
        cvInput.placeholder = '0.00';
        cvHint.textContent = '';
        cycleHint.textContent = '';
        termsHint.textContent = 'Key contractual terms. Full version in the Content tab.';
        retainerF.classList.add('d-none');
        nteNote.classList.add('d-none');
        sumCard.classList.add('d-none');
        Array.from(cycleEl.options).forEach(o => o.style.display = '');
        return;
    }

    ctxBox.style.background = cfg.contextColor;
    ctxBox.style.border = `1px solid ${cfg.contextBorder}`;
    ctxBox.classList.remove('d-none');
    ctxText.innerHTML = `<i class="bi ${cfg.contextIcon} me-1"></i>${cfg.context}`;

    cvLabel.textContent = cfg.contractLabel;
    cvInput.placeholder = cfg.contractPlaceholder;
    cvHint.textContent = cfg.contractHint;

    const currentCycle = cycleEl.value;
    Array.from(cycleEl.options).forEach(o => {
        if (!o.value) { o.style.display = ''; return; }
        o.style.display = cfg.cycles.includes(o.value) ? '' : 'none';
    });
    if (type === 'retainer') {
        cycleEl.value = 'monthly';
    } else if (currentCycle && !cfg.cycles.includes(currentCycle)) {
        cycleEl.value = '';
    }
    cycleHint.textContent = cfg.cycleHint;

    termsHint.innerHTML = `<i class="bi bi-lightbulb me-1 text-warning"></i><strong>Tip:</strong> ${cfg.termsHint}`;

    retainerF.classList.toggle('d-none', !cfg.showRetainerHours);
    nteNote.classList.toggle('d-none', !cfg.showNteNote);

    sumCard.classList.remove('d-none');
    sumTitle.innerHTML = `<i class="bi bi-info-circle me-2"></i>${cfg.summaryTitle}`;
    sumContent.innerHTML = cfg.summaryContent;
}

document.getElementById('companySelect').addEventListener('change', function () {
    const companyId = this.value;
    const contactSel = document.getElementById('contactSelect');
    Array.from(contactSel.options).forEach(opt => {
        if (!opt.value) { opt.style.display = ''; return; }
        opt.style.display = (!companyId || opt.dataset.company == companyId) ? '' : 'none';
    });
    const selectedOpt = contactSel.options[contactSel.selectedIndex];
    if (companyId && selectedOpt.value && selectedOpt.dataset.company != companyId) {
        contactSel.value = '';
    }
});

document.getElementById('statusSelect').addEventListener('change', function () {
    const name  = this.options[this.selectedIndex]?.dataset?.name ?? '';
    const badge = document.getElementById('poNumberBadge');
    const hint  = document.getElementById('poNumberHint');
    if (name === 'approved') {
        badge.classList.remove('d-none');
        badge.className = 'badge bg-success';
        badge.style.fontSize = '0.6rem';
        badge.textContent = 'Approved';
        hint.innerHTML = '<i class="bi bi-check-circle text-success me-1"></i>Proposal approved — enter PO number provided by client.';
    } else {
        badge.classList.add('d-none');
        hint.innerHTML = '<i class="bi bi-info-circle me-1"></i>Typically provided by client upon approval';
    }
});

document.getElementById('billingType').addEventListener('change', function () {
    applyBillingTypeIntelligence(this.value);
});

(function() {
    const bt = document.getElementById('billingType').value;
    if (bt) applyBillingTypeIntelligence(bt);
    const status = document.getElementById('statusSelect');
    if (status.value) status.dispatchEvent(new Event('change'));
})();

// ── Company → Vendor Code Auto-Fill ──────────────────────────────────────────
function onCompanyChange() {
    const sel = document.getElementById('companySelect');
    const opt = sel.options[sel.selectedIndex];
    const vendorInput = document.querySelector('input[name="vendor_code"]');
    if (!vendorInput) return;
    if (opt && opt.dataset.vendorCode && !vendorInput.value.trim()) {
        vendorInput.value = opt.dataset.vendorCode;
    }
}
</script>
@endpush

@endsection