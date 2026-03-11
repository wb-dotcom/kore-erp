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

<form action="{{ route('proposals.store') }}" method="POST" id="proposalForm">
@csrf

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
            {{-- Reference fields (small, auto-generated) --}}
            <div class="col-sm-2">
                <label class="form-label required">Year</label>
                <input type="number" name="year" class="form-control form-control-sm @error('year') is-invalid @enderror"
                    value="{{ old('year', $year) }}" min="2000" max="2099" required>
                @error('year')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-2">
                <label class="form-label required">Proposal #</label>
                <input type="number" name="proposal_number" class="form-control form-control-sm @error('proposal_number') is-invalid @enderror"
                    value="{{ old('proposal_number', $nextNumber) }}" min="1" required>
                @error('proposal_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-4">
                <label class="form-label required">Status</label>
                <select name="status_id" id="statusSelect" class="form-select form-select-sm @error('status_id') is-invalid @enderror" required>
                    <option value="">— Select Status —</option>
                    @foreach($statuses as $s)
                    <option value="{{ $s->id }}"
                        data-name="{{ strtolower($s->name) }}"
                        {{ old('status_id') == $s->id ? 'selected' : '' }}>
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
                    <option value="{{ $w->id }}" {{ old('work_type_id') == $w->id ? 'selected' : '' }}>
                        {{ $w->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Title — prominent --}}
            <div class="col-12">
                <label class="form-label required">Proposal Title</label>
                <input type="text" name="title" class="form-control form-control-sm @error('title') is-invalid @enderror"
                    value="{{ old('title') }}"
                    placeholder="e.g. Environmental Assessment — Downtown Corridor Expansion" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Dates --}}
            <div class="col-sm-4">
                <label class="form-label">Submitted Date</label>
                <input type="date" name="submitted_date" class="form-control form-control-sm"
                    value="{{ old('submitted_date') }}">
            </div>
            <div class="col-sm-4">
                <label class="form-label">Expiry Date</label>
                <input type="date" name="expiry_date" class="form-control form-control-sm"
                    value="{{ old('expiry_date') }}">
                <div class="form-text" style="font-size:0.68rem;">Proposal validity deadline</div>
            </div>
            <div class="col-sm-4">
                {{-- PO Number — only meaningful upon approval --}}
                <label class="form-label d-flex align-items-center gap-1">
                    PO Number
                    <span id="poNumberBadge" class="badge bg-secondary d-none" style="font-size:0.6rem; font-weight:500;">Available on Approval</span>
                </label>
                <input type="text" name="po_number" id="poNumberInput" class="form-control form-control-sm"
                    value="{{ old('po_number') }}" placeholder="Client PO #">
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
            {{-- Company + Contact — top priority --}}
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
                <label class="form-label">Primary Contact</label>
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

            <div class="col-sm-4">
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
            <div class="col-sm-4">
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
                <div class="form-text" style="font-size:0.68rem;">Client's vendor registry ID</div>
            </div>

            <div class="col-12">
                <label class="form-label">Brief Description</label>
                <textarea name="description" class="form-control form-control-sm" rows="3"
                    placeholder="High-level summary of the engagement, key objectives, and expected outcomes...">{{ old('description') }}</textarea>
                <div class="form-text" style="font-size:0.68rem;">A 2–4 sentence overview. Detailed scope and executive summary are captured in the Content tab after creation.</div>
            </div>
        </div>
    </div>

    {{-- ── SECTION 3: BILLING & TERMS ─────────────────────────── --}}
    <div class="kore-card mt-4">
        <div class="kore-card-header">
            <h5><i class="bi bi-receipt me-2"></i>Billing &amp; Terms</h5>
        </div>

        {{-- Billing Type selector — drives all dynamic behaviour --}}
        <div class="row g-3">
            <div class="col-sm-5">
                <label class="form-label">Billing Type</label>
                <select name="billing_type" id="billingType" class="form-select form-select-sm">
                    <option value="">— Select Billing Type —</option>
                    <option value="fixed"              {{ old('billing_type') === 'fixed'              ? 'selected' : '' }}>Fixed Fee</option>
                    <option value="time_and_material"  {{ old('billing_type') === 'time_and_material'  ? 'selected' : '' }}>Time &amp; Material</option>
                    <option value="per_deliverable"    {{ old('billing_type') === 'per_deliverable'    ? 'selected' : '' }}>Per Deliverable</option>
                    <option value="retainer"           {{ old('billing_type') === 'retainer'           ? 'selected' : '' }}>Retainer</option>
                    <option value="hybrid"             {{ old('billing_type') === 'hybrid'             ? 'selected' : '' }}>Hybrid</option>
                </select>
            </div>

            {{-- Dynamic context card — changes per billing type --}}
            <div class="col-sm-7">
                <div id="billingTypeContext" class="rounded p-2 d-none" style="background:#f0f9ff; border:1px solid #bae6fd; font-size:0.75rem;">
                    <div id="billingTypeContextText"></div>
                </div>
            </div>

            {{-- ── Contract Value — label & hint change per type --}}
            <div class="col-sm-4">
                <label class="form-label" id="contractValueLabel">Contract Value</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">$</span>
                    <input type="number" name="contract_value" id="contractValueInput"
                        class="form-control" step="0.01" min="0"
                        value="{{ old('contract_value') }}" placeholder="0.00">
                </div>
                <div class="form-text" id="contractValueHint" style="font-size:0.68rem;"></div>
            </div>

            {{-- Expenses Reserve --}}
            <div class="col-sm-4">
                <label class="form-label">Expenses Reserve</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">$</span>
                    <input type="number" name="expenses_reserve" class="form-control" step="0.01" min="0"
                        value="{{ old('expenses_reserve', 0) }}" placeholder="0.00">
                </div>
                <div class="form-text" style="font-size:0.68rem;">Reimbursable expenses budget (travel, materials, etc.)</div>
            </div>

            {{-- Payment Terms --}}
            <div class="col-sm-4">
                <label class="form-label">Payment Terms</label>
                <div class="input-group input-group-sm">
                    <input type="number" name="payment_terms_days" class="form-control"
                        value="{{ old('payment_terms_days', 30) }}" min="0" max="365" placeholder="30">
                    <span class="input-group-text">days</span>
                </div>
                <div class="form-text" style="font-size:0.68rem;">e.g. 30 = Net-30. Invoice due within 30 days of issue.</div>
            </div>

            {{-- Billing Cycle — options filtered per billing type --}}
            <div class="col-sm-5">
                <label class="form-label">Billing Cycle</label>
                <select name="billing_cycle" id="billingCycle" class="form-select form-select-sm">
                    <option value="">— Select Cycle —</option>
                    <option value="biweekly"      class="cycle-tm cycle-hybrid"
                        {{ old('billing_cycle') === 'biweekly'      ? 'selected' : '' }}>Bi-Weekly (every 2 weeks)</option>
                    <option value="monthly"       class="cycle-tm cycle-retainer cycle-hybrid"
                        {{ old('billing_cycle') === 'monthly'       ? 'selected' : '' }}>Monthly</option>
                    <option value="quarterly"     class="cycle-retainer cycle-hybrid"
                        {{ old('billing_cycle') === 'quarterly'     ? 'selected' : '' }}>Quarterly</option>
                    <option value="on_completion" class="cycle-fixed cycle-per_deliverable"
                        {{ old('billing_cycle') === 'on_completion' ? 'selected' : '' }}>On Completion</option>
                    <option value="custom"        class="cycle-fixed cycle-hybrid cycle-per_deliverable"
                        {{ old('billing_cycle') === 'custom'        ? 'selected' : '' }}>Custom / Milestone-Based</option>
                </select>
                <div class="form-text" id="billingCycleHint" style="font-size:0.68rem;"></div>
            </div>

            {{-- Retainer-specific: hours per period --}}
            <div class="col-sm-4 d-none" id="retainerHoursField">
                <label class="form-label">Hours Included / Period</label>
                <div class="input-group input-group-sm">
                    <input type="number" name="retainer_hours" class="form-control"
                        value="{{ old('retainer_hours') }}" min="0" placeholder="e.g. 40">
                    <span class="input-group-text">hrs</span>
                </div>
                <div class="form-text" style="font-size:0.68rem;">Hours covered by retainer each billing period</div>
            </div>

            {{-- T&M specific: NTE note --}}
            <div class="col-sm-4 d-none" id="nteNoteField">
                <div class="rounded p-2" style="background:#fefce8; border:1px solid #fde68a; font-size:0.72rem;">
                    <i class="bi bi-clock me-1 text-warning"></i>
                    <strong>T&amp;M Tip:</strong> Set hourly rates in the <em>Fee Worksheet</em> tab after creation. Use Contract Value as a not-to-exceed cap (optional).
                </div>
            </div>

            {{-- Terms & Conditions — combined here --}}
            <div class="col-12">
                <label class="form-label">Terms &amp; Conditions</label>
                <textarea name="terms_and_conditions" class="form-control form-control-sm" rows="3"
                    id="termsTextarea"
                    placeholder="Payment conditions, liability limits, IP ownership, late payment clauses...">{{ old('terms_and_conditions') }}</textarea>
                <div class="form-text" id="termsHint" style="font-size:0.68rem; color:#6b7280;">
                    Key contractual terms. Can be expanded in the Content tab after creation.
                </div>
            </div>
        </div>
    </div>

    {{-- ── SECTION 4: INTERNAL NOTES ──────────────────────────── --}}
    <div class="kore-card mt-4">
        <div class="kore-card-header">
            <h5><i class="bi bi-lock me-2"></i>Internal Notes</h5>
            <span class="badge bg-secondary" style="font-size:0.65rem;">Not visible to client</span>
        </div>
        <textarea name="notes" class="form-control form-control-sm" rows="3"
            placeholder="Win strategy, pricing rationale, competitive notes, internal action items...">{{ old('notes') }}</textarea>
    </div>

</div>{{-- /col-lg-8 --}}

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- SIDEBAR                                                     --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div class="col-lg-4">

    {{-- Actions --}}
    <div class="kore-card mb-4">
        <div class="kore-card-header"><h5>Actions</h5></div>
        <button type="submit" class="btn btn-primary w-100 mb-2">
            <i class="bi bi-check-lg me-1"></i> Create Proposal
        </button>
        <a href="{{ route('proposals.index') }}" class="btn btn-outline-secondary w-100">Cancel</a>
    </div>

    {{-- Proposal checklist / next steps --}}
    <div class="kore-card" id="proposalChecklist">
        <div class="kore-card-header"><h5><i class="bi bi-list-check me-2"></i>Setup Checklist</h5></div>
        <div style="font-size:0.75rem; color:#6b7280; margin-bottom:8px;">After creating the proposal, complete these steps:</div>
        <div class="checklist-item d-flex align-items-start gap-2 py-1 border-bottom">
            <i class="bi bi-1-circle text-primary mt-1" style="font-size:0.9rem; flex-shrink:0;"></i>
            <div><strong style="font-size:0.75rem;">Content tab</strong><br><span style="font-size:0.72rem; color:#6b7280;">Write executive summary &amp; detailed scope of work</span></div>
        </div>
        <div class="checklist-item d-flex align-items-start gap-2 py-1 border-bottom">
            <i class="bi bi-2-circle text-primary mt-1" style="font-size:0.9rem; flex-shrink:0;"></i>
            <div><strong style="font-size:0.75rem;">Fee Worksheet tab</strong><br><span style="font-size:0.72rem; color:#6b7280;">Add line items, roles, and hours — or configure rates</span></div>
        </div>
        <div class="checklist-item d-flex align-items-start gap-2 py-1 border-bottom">
            <i class="bi bi-3-circle text-primary mt-1" style="font-size:0.9rem; flex-shrink:0;"></i>
            <div><strong style="font-size:0.75rem;">Activities tab</strong><br><span style="font-size:0.72rem; color:#6b7280;">Define deliverables, activities, and task breakdown</span></div>
        </div>
        <div class="checklist-item d-flex align-items-start gap-2 py-1 border-bottom">
            <i class="bi bi-4-circle text-primary mt-1" style="font-size:0.9rem; flex-shrink:0;"></i>
            <div><strong style="font-size:0.75rem;">Billing Schedule tab</strong><br><span style="font-size:0.72rem; color:#6b7280;">Generate invoice schedule &amp; set milestone dates</span></div>
        </div>
        <div class="checklist-item d-flex align-items-start gap-2 py-1 pt-1">
            <i class="bi bi-5-circle text-primary mt-1" style="font-size:0.9rem; flex-shrink:0;"></i>
            <div><strong style="font-size:0.75rem;">Upon approval</strong><br><span style="font-size:0.72rem; color:#6b7280;">Add PO Number &amp; convert to Project</span></div>
        </div>
    </div>

    {{-- Dynamic billing type summary card --}}
    <div class="kore-card mt-4 d-none" id="billingTypeSummaryCard">
        <div class="kore-card-header">
            <h5 id="billingTypeSummaryTitle"><i class="bi bi-info-circle me-2"></i>Billing Guide</h5>
        </div>
        <div id="billingTypeSummaryContent" style="font-size:0.75rem; color:#374151;"></div>
    </div>

</div>{{-- /col-lg-4 --}}

</div>{{-- /row --}}
</form>

@push('styles')
<style>
.form-label { font-size: 0.75rem; font-weight: 600; margin-bottom: 4px; color: #374151; }
.form-label.required::after { content: ' *'; color: #ef4444; }
.form-text { margin-top: 3px; }
.checklist-item:last-child { border-bottom: none !important; }
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
        context:     '<strong>Fixed Fee</strong> — Client pays an agreed total price regardless of hours worked. Ideal for well-defined scopes.',
        contractLabel: 'Fixed Contract Amount',
        contractPlaceholder: 'e.g. 75000',
        contractHint:  'Total agreed price for the entire engagement (e.g. $75,000)',
        cycles:       ['on_completion', 'custom'],
        cycleHint:    'Fixed fee projects bill on completion or at predefined milestones.',
        termsHint:    'Include: what triggers final payment, revision limits, and change-order process.',
        summaryTitle: 'Fixed Fee Guide',
        summaryContent: `
            <ul class="ps-3 mb-0" style="font-size:0.73rem;">
                <li class="mb-1">Set the total contract amount above</li>
                <li class="mb-1">Configure milestone payments in the <strong>Billing Schedule</strong> tab (e.g. 30% on start, 40% mid-project, 30% on delivery)</li>
                <li class="mb-1">Define scope clearly — change orders require contract amendment</li>
                <li>Set payment terms (Net-30 is standard)</li>
            </ul>`,
        showRetainerHours: false,
        showNteNote: false,
    },
    time_and_material: {
        label:       'Time & Material',
        contextIcon: 'bi-clock',
        contextColor:'#eff6ff',
        contextBorder:'#93c5fd',
        context:     '<strong>Time &amp; Material</strong> — Client billed for actual hours × hourly rates. Scope can flex; consider adding a Not-to-Exceed (NTE) cap.',
        contractLabel: 'Not-to-Exceed Cap (optional)',
        contractPlaceholder: 'e.g. 120000',
        contractHint:  'Maximum billable amount (leave blank if uncapped). Actual billing = hours × rate.',
        cycles:       ['biweekly', 'monthly'],
        cycleHint:    'T&M projects typically bill bi-weekly or monthly based on time logged.',
        termsHint:    'Include: NTE cap, overtime policy, rate adjustment notice period, and expense approval threshold.',
        summaryTitle: 'T&M Billing Guide',
        summaryContent: `
            <ul class="ps-3 mb-0" style="font-size:0.73rem;">
                <li class="mb-1">Set hourly rates per role in the <strong>Fee Worksheet</strong> tab or Rate Overrides</li>
                <li class="mb-1">Use Contract Value as a NTE cap to protect the client budget</li>
                <li class="mb-1">Bill bi-weekly or monthly against actual logged hours</li>
                <li class="mb-1">Client receives timesheets / hour summaries with each invoice</li>
                <li>Document rate adjustment terms in T&amp;C</li>
            </ul>`,
        showRetainerHours: false,
        showNteNote: true,
    },
    per_deliverable: {
        label:       'Per Deliverable',
        contextIcon: 'bi-boxes',
        contextColor:'#faf5ff',
        contextBorder:'#c4b5fd',
        context:     '<strong>Per Deliverable</strong> — Each deliverable has a fixed price. Client pays upon acceptance of each delivered item.',
        contractLabel: 'Total Contract Amount',
        contractPlaceholder: 'e.g. 90000',
        contractHint:  'Sum of all deliverable prices (e.g. Report $15k + Workshop $10k + Final Plan $65k = $90k)',
        cycles:       ['on_completion', 'custom'],
        cycleHint:    'Invoice triggered upon acceptance of each deliverable.',
        termsHint:    'Include: acceptance criteria per deliverable, revision rounds, and what happens if a deliverable is rejected.',
        summaryTitle: 'Per-Deliverable Guide',
        summaryContent: `
            <ul class="ps-3 mb-0" style="font-size:0.73rem;">
                <li class="mb-1">List each deliverable with its fixed price in the <strong>Fee Worksheet</strong> tab</li>
                <li class="mb-1">Invoice is triggered when client formally accepts the deliverable</li>
                <li class="mb-1">Define acceptance criteria and revision limits in T&amp;C</li>
                <li>Consider a holdback (e.g. 10%) released on final acceptance</li>
            </ul>`,
        showRetainerHours: false,
        showNteNote: false,
    },
    retainer: {
        label:       'Retainer',
        contextIcon: 'bi-arrow-repeat',
        contextColor:'#fff7ed',
        contextBorder:'#fdba74',
        context:     '<strong>Retainer</strong> — Client pays a recurring fixed fee each period for access to your services. Unused hours may or may not roll over.',
        contractLabel: 'Retainer Amount (per period)',
        contractPlaceholder: 'e.g. 10000',
        contractHint:  'Amount billed each billing period (e.g. $10,000/month). Not a total contract value.',
        cycles:       ['monthly', 'quarterly'],
        cycleHint:    'Retainers are typically monthly. Auto-selects monthly.',
        termsHint:    'Include: hours included per period, rollover policy, scope of services covered, and termination notice period.',
        summaryTitle: 'Retainer Guide',
        summaryContent: `
            <ul class="ps-3 mb-0" style="font-size:0.73rem;">
                <li class="mb-1">Enter the recurring amount per billing period above (not a project total)</li>
                <li class="mb-1">Set hours included per period in the field below</li>
                <li class="mb-1">Generate recurring invoices in the <strong>Billing Schedule</strong> tab</li>
                <li class="mb-1">Document rollover policy and scope limits in T&amp;C</li>
                <li>Set a clear termination notice period (e.g. 30 days written notice)</li>
            </ul>`,
        showRetainerHours: true,
        showNteNote: false,
    },
    hybrid: {
        label:       'Hybrid',
        contextIcon: 'bi-puzzle',
        contextColor:'#f0fdf4',
        contextBorder:'#6ee7b7',
        context:     '<strong>Hybrid</strong> — Mix of billing types. Some components are fixed fee, others are T&M. Common for phased engagements.',
        contractLabel: 'Estimated Total Contract Value',
        contractPlaceholder: 'e.g. 150000',
        contractHint:  'Combined estimate (fixed components + T&M cap). Final billing may vary for T&M portions.',
        cycles:       ['monthly', 'custom'],
        cycleHint:    'Hybrid billing typically uses custom milestones for fixed components + monthly for T&M.',
        termsHint:    'Specify which phases/components are fixed vs. T&M. Include NTE cap for T&M portions.',
        summaryTitle: 'Hybrid Billing Guide',
        summaryContent: `
            <ul class="ps-3 mb-0" style="font-size:0.73rem;">
                <li class="mb-1">Clearly define which phases are Fixed vs. T&amp;M in the <strong>Fee Worksheet</strong></li>
                <li class="mb-1">Set hourly rates for T&amp;M phases; fixed amounts for fixed phases</li>
                <li class="mb-1">Use milestone billing for fixed phases, monthly for T&amp;M phases</li>
                <li>Include a T&amp;M NTE cap per phase in the contract</li>
            </ul>`,
        showRetainerHours: false,
        showNteNote: false,
    }
};

const BILLING_CYCLE_LABELS = {
    biweekly:      'Bi-Weekly (every 2 weeks)',
    monthly:       'Monthly',
    quarterly:     'Quarterly',
    on_completion: 'On Completion',
    custom:        'Custom / Milestone-Based',
};

function applyBillingTypeIntelligence(type) {
    const cfg = BILLING_CONFIGS[type];

    // Elements
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
        // Reset to defaults
        ctxBox.classList.add('d-none');
        cvLabel.textContent = 'Contract Value';
        cvInput.placeholder = '0.00';
        cvHint.textContent = '';
        cycleHint.textContent = '';
        termsHint.textContent = 'Key contractual terms. Can be expanded in the Content tab after creation.';
        retainerF.classList.add('d-none');
        nteNote.classList.add('d-none');
        sumCard.classList.add('d-none');
        // Show all cycle options
        Array.from(cycleEl.options).forEach(o => o.style.display = '');
        return;
    }

    // Context box
    ctxBox.style.background = cfg.contextColor;
    ctxBox.style.borderColor = cfg.contextBorder;
    ctxBox.classList.remove('d-none');
    ctxText.innerHTML = `<i class="bi ${cfg.contextIcon} me-1"></i>${cfg.context}`;

    // Contract value
    cvLabel.textContent = cfg.contractLabel;
    cvInput.placeholder = cfg.contractPlaceholder;
    cvHint.textContent = cfg.contractHint;

    // Filter billing cycle options
    const currentCycle = cycleEl.value;
    Array.from(cycleEl.options).forEach(o => {
        if (!o.value) { o.style.display = ''; return; }
        o.style.display = cfg.cycles.includes(o.value) ? '' : 'none';
    });
    // Auto-select if current value is not in valid cycles or for retainer force monthly
    if (type === 'retainer') {
        cycleEl.value = 'monthly';
    } else if (currentCycle && !cfg.cycles.includes(currentCycle)) {
        cycleEl.value = '';
    }
    cycleHint.textContent = cfg.cycleHint;

    // Terms hint
    termsHint.innerHTML = `<i class="bi bi-lightbulb me-1 text-warning"></i><strong>Tip:</strong> ${cfg.termsHint}`;

    // Conditional fields
    retainerF.classList.toggle('d-none', !cfg.showRetainerHours);
    nteNote.classList.toggle('d-none', !cfg.showNteNote);

    // Summary card
    sumCard.classList.remove('d-none');
    sumTitle.innerHTML = `<i class="bi bi-info-circle me-2"></i>${cfg.summaryTitle}`;
    sumContent.innerHTML = cfg.summaryContent;
}

/* ════════════════════════════════════════════════════════════════
   COMPANY → CONTACT FILTER
   ════════════════════════════════════════════════════════════════ */
document.getElementById('companySelect').addEventListener('change', function () {
    const companyId = this.value;
    const contactSel = document.getElementById('contactSelect');
    Array.from(contactSel.options).forEach(opt => {
        if (!opt.value) { opt.style.display = ''; return; }
        opt.style.display = (!companyId || opt.dataset.company == companyId) ? '' : 'none';
    });
    // Reset contact if it no longer belongs to selected company
    const selectedOpt = contactSel.options[contactSel.selectedIndex];
    if (companyId && selectedOpt.value && selectedOpt.dataset.company != companyId) {
        contactSel.value = '';
    }
});

/* ════════════════════════════════════════════════════════════════
   STATUS → PO NUMBER HINT
   ════════════════════════════════════════════════════════════════ */
document.getElementById('statusSelect').addEventListener('change', function () {
    const name  = this.options[this.selectedIndex]?.dataset?.name ?? '';
    const badge = document.getElementById('poNumberBadge');
    const hint  = document.getElementById('poNumberHint');
    if (name === 'approved') {
        badge.classList.remove('d-none');
        badge.classList.remove('bg-secondary');
        badge.classList.add('bg-success');
        badge.textContent = 'Approved';
        hint.innerHTML = '<i class="bi bi-check-circle text-success me-1"></i>Proposal approved — enter PO number provided by client.';
    } else {
        badge.classList.add('d-none');
        hint.innerHTML = '<i class="bi bi-info-circle me-1"></i>Typically provided by client upon approval';
    }
});

/* ════════════════════════════════════════════════════════════════
   INIT
   ════════════════════════════════════════════════════════════════ */
document.getElementById('billingType').addEventListener('change', function () {
    applyBillingTypeIntelligence(this.value);
});

// Apply on page load if old() value was set
(function() {
    const billingType = document.getElementById('billingType').value;
    if (billingType) applyBillingTypeIntelligence(billingType);
    const status = document.getElementById('statusSelect');
    if (status.value) status.dispatchEvent(new Event('change'));
})();
</script>
@endpush

@endsection
