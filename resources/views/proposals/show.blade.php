@extends('layouts.app')

@section('content')

@php
    $isApproved  = $proposal->isApproved();
    $billingType = $proposal->billing_type ?? 'fixed';
    $contractVal = $proposal->contract_value ?? $proposal->total_fee ?? 0;
    $expReserve  = $proposal->expenses_reserve ?? 0;
    $netFees     = $contractVal - $expReserve;
    $bs          = $proposal->billingSchedule;
    $invoiced    = $bs ? $bs->total_invoiced : 0;
    $paid        = $bs ? $bs->total_paid : 0;
    $outstanding = $invoiced - $paid;
    $pct         = $contractVal > 0 ? round(($invoiced / $contractVal) * 100) : 0;
@endphp

{{-- Page Header --}}
<div class="d-flex align-items-center gap-3 mb-3">
    <a href="{{ route('proposals.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div class="flex-grow-1">
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0 fw-700" style="font-size:1rem;">{{ $proposal->title }}</h4>
            @php
                $cls = match($proposal->status?->name) {
                    'Approved'  => 'approved',
                    'Submitted','Under Review' => 'active',
                    'Rejected'  => 'rejected',
                    default     => 'draft',
                };
            @endphp
            <span class="badge badge-{{ $cls }}">{{ $proposal->status?->name ?? 'Draft' }}</span>
            @if($isApproved && $proposal->po_number)
            <span class="badge bg-light text-dark border" style="font-size:0.7rem;">
                <i class="bi bi-receipt me-1"></i>PO: {{ $proposal->po_number }}
            </span>
            @endif
        </div>
        <div style="font-size:0.72rem; color:#6b7280;">
            {{ $proposal->ref }}
            @if($proposal->company) &mdash; {{ $proposal->company->name }} @endif
            @if($proposal->billing_type) &mdash; {{ ucwords(str_replace('_',' ',$proposal->billing_type)) }} @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('proposals.edit', $proposal) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        @if(!$proposal->project && $isApproved)
        <a href="{{ route('projects.create') }}?proposal_id={{ $proposal->id }}" class="btn btn-sm btn-success">
            <i class="bi bi-folder-plus me-1"></i> Create Project
        </a>
        @elseif($proposal->project)
        <a href="{{ route('projects.show', $proposal->project) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-folder2-open me-1"></i> View Project
        </a>
        @endif
    </div>
</div>

{{-- Tab Navigation --}}
<ul class="nav nav-tabs mb-0" id="proposalTabs" role="tablist" style="border-bottom: 2px solid #e5e7eb;">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-dashboard" type="button">
            <i class="bi bi-speedometer2 me-1"></i> Dashboard
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-content" type="button">
            <i class="bi bi-file-richtext me-1"></i> Content &amp; Attachments
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-billing" type="button"
            id="tabBillingBtn"
            onclick="initBillingTab()">
            <i class="bi bi-calendar-check me-1"></i> Billing Schedule
        </button>
    </li>
</ul>

<div class="tab-content pt-4" id="proposalTabContent">

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- TAB: DASHBOARD                                                            --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade show active" id="tab-dashboard">

    {{-- Financial Health Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="kore-card h-100" style="border-top: 3px solid #3b82f6;">
                <div style="font-size:0.7rem; font-weight:600; text-transform:uppercase; color:#6b7280; letter-spacing:0.5px;">Contract Value</div>
                <div style="font-size:1.5rem; font-weight:700; color:#111827; margin-top:4px;">${{ number_format($contractVal, 0) }}</div>
                @if($expReserve > 0)
                <div style="font-size:0.72rem; color:#6b7280;">Net Fees: ${{ number_format($netFees, 0) }}</div>
                @endif
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="kore-card h-100" style="border-top: 3px solid #f59e0b;">
                <div style="font-size:0.7rem; font-weight:600; text-transform:uppercase; color:#6b7280; letter-spacing:0.5px;">Expenses Reserve</div>
                <div style="font-size:1.5rem; font-weight:700; color:#111827; margin-top:4px;">${{ number_format($expReserve, 0) }}</div>
                <div style="font-size:0.72rem; color:#6b7280;">Reimbursable budget</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="kore-card h-100" style="border-top: 3px solid #10b981;">
                <div style="font-size:0.7rem; font-weight:600; text-transform:uppercase; color:#6b7280; letter-spacing:0.5px;">Invoiced to Date</div>
                <div style="font-size:1.5rem; font-weight:700; color:#111827; margin-top:4px;">${{ number_format($invoiced, 0) }}</div>
                <div style="font-size:0.72rem; color:#6b7280;">{{ $pct }}% of contract value</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="kore-card h-100" style="border-top: 3px solid {{ $outstanding > 0 ? '#ef4444' : '#6b7280' }};">
                <div style="font-size:0.7rem; font-weight:600; text-transform:uppercase; color:#6b7280; letter-spacing:0.5px;">Outstanding Balance</div>
                <div style="font-size:1.5rem; font-weight:700; color:{{ $outstanding > 0 ? '#ef4444' : '#111827' }}; margin-top:4px;">${{ number_format($outstanding, 0) }}</div>
                <div style="font-size:0.72rem; color:#6b7280;">Paid: ${{ number_format($paid, 0) }}</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Main column --}}
        <div class="col-lg-8">

            {{-- Proposal Overview --}}
            <div class="kore-card mb-4">
                <div class="kore-card-header">
                    <h5><i class="bi bi-clipboard-data me-2"></i>Proposal Overview</h5>
                </div>
                <div class="row g-3">
                    <div class="col-sm-3">
                        <div class="field-label">Year</div>
                        <div class="field-value">{{ $proposal->year }}</div>
                    </div>
                    <div class="col-sm-3">
                        <div class="field-label">Reference</div>
                        <div class="field-value">{{ $proposal->ref }}</div>
                    </div>
                    <div class="col-sm-3">
                        <div class="field-label">Status</div>
                        <div class="field-value">
                            <span class="badge badge-{{ $cls }}">{{ $proposal->status?->name ?? 'Draft' }}</span>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="field-label">Work Type</div>
                        <div class="field-value">{{ $proposal->workType?->name ?? '—' }}</div>
                    </div>
                    @if($isApproved)
                    <div class="col-sm-4">
                        <div class="field-label">PO Number</div>
                        <div class="field-value">{{ $proposal->po_number ?? '—' }}</div>
                    </div>
                    @endif
                    <div class="col-sm-4">
                        <div class="field-label">Submitted</div>
                        <div class="field-value">{{ $proposal->submitted_date?->format('M d, Y') ?? '—' }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="field-label">Approved</div>
                        <div class="field-value">{{ $proposal->approved_date?->format('M d, Y') ?? '—' }}</div>
                    </div>
                    @if($proposal->expiry_date)
                    <div class="col-sm-4">
                        <div class="field-label">Expires</div>
                        <div class="field-value">{{ $proposal->expiry_date->format('M d, Y') }}</div>
                    </div>
                    @endif
                </div>
                @if($proposal->description)
                <hr class="my-3">
                <div class="field-label mb-1">Description</div>
                <div style="font-size:0.83rem; white-space:pre-line; line-height:1.6;">{{ $proposal->description }}</div>
                @endif
                @if($proposal->notes)
                <div class="kore-alert kore-alert-info mt-3 mb-0" style="font-size:0.8rem;">
                    <i class="bi bi-sticky me-1"></i> <strong>Internal Notes:</strong> {{ $proposal->notes }}
                </div>
                @endif
            </div>

            {{-- Client & Engagement --}}
            <div class="kore-card mb-4">
                <div class="kore-card-header">
                    <h5><i class="bi bi-building me-2"></i>Client &amp; Engagement</h5>
                </div>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="field-label">Client Company</div>
                        <div class="field-value">
                            @if($proposal->company)
                            <a href="{{ route('companies.show', $proposal->company) }}" style="color:#2563eb; text-decoration:none;">
                                {{ $proposal->company->name }}
                            </a>
                            @else
                            <span class="text-muted">—
                                <a href="{{ route('companies.create') }}" class="ms-1" style="font-size:0.72rem;" target="_blank">+ Add Company</a>
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="field-label">Primary Contact</div>
                        <div class="field-value">{{ $proposal->contact?->full_name ?? '—' }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="field-label">Account Manager</div>
                        <div class="field-value">{{ $proposal->accountManager?->full_name ?? '—' }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="field-label">Vendor Code</div>
                        <div class="field-value">
                            {{ $proposal->vendor_code ?? $proposal->company?->vendor_code ?? '—' }}
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="field-label">Sector</div>
                        <div class="field-value">{{ $proposal->sector?->name ?? '—' }}</div>
                    </div>
                    @if($proposal->projectType && !in_array(strtolower($proposal->projectType->name), ['billable','non-billable']))
                    <div class="col-sm-4">
                        <div class="field-label">Internal Project Type</div>
                        <div class="field-value">{{ $proposal->projectType->name }}</div>
                    </div>
                    @endif
                    @if($proposal->program)
                    <div class="col-sm-4">
                        <div class="field-label">Program</div>
                        <div class="field-value">{{ $proposal->program->name }}</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Billing & Terms --}}
            <div class="kore-card mb-4">
                <div class="kore-card-header">
                    <h5><i class="bi bi-receipt me-2"></i>Billing &amp; Terms</h5>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-sm-4">
                        <div class="field-label">Billing Type</div>
                        <div class="field-value fw-600">
                            @php
                                $btLabels = [
                                    'fixed'            => 'Fixed Fee',
                                    'time_and_material'=> 'Time & Material',
                                    'per_deliverable'  => 'Per Deliverable',
                                    'retainer'         => 'Retainer',
                                    'hybrid'           => 'Hybrid',
                                ];
                            @endphp
                            {{ $btLabels[$billingType] ?? ucwords(str_replace('_',' ',$billingType)) }}
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="field-label">Billing Cycle</div>
                        <div class="field-value">
                            @php
                                $bcLabels = [
                                    'biweekly'     => 'Bi-Weekly',
                                    'monthly'      => 'Monthly',
                                    'quarterly'    => 'Quarterly',
                                    'on_completion'=> 'On Completion',
                                    'custom'       => 'Custom',
                                ];
                            @endphp
                            {{ $bcLabels[$proposal->billing_cycle ?? ''] ?? ($proposal->billing_cycle ? ucwords(str_replace('_',' ',$proposal->billing_cycle)) : '—') }}
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="field-label">Payment Terms</div>
                        <div class="field-value">
                            {{ $proposal->payment_terms_days ? 'Net ' . $proposal->payment_terms_days : '—' }}
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="field-label">Contract Value</div>
                        <div class="field-value fw-600">${{ number_format($contractVal, 2) }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="field-label">Expenses Reserve</div>
                        <div class="field-value">${{ number_format($expReserve, 2) }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="field-label">Net Professional Fees</div>
                        <div class="field-value fw-600 text-success">${{ number_format($netFees, 2) }}</div>
                    </div>
                </div>

                {{-- Fee Worksheet (embedded) --}}
                <div style="border-top:2px solid #e5e7eb; padding-top:16px;">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div style="font-size:0.78rem; font-weight:600; text-transform:uppercase; color:#6b7280; letter-spacing:0.5px;">
                            <i class="bi bi-table me-1"></i> Fee Worksheet
                        </div>
                        @if($isApproved)
                        <span class="badge bg-warning text-dark" style="font-size:0.7rem;">
                            <i class="bi bi-lock me-1"></i> Locked — Approved
                        </span>
                        @else
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addLineItemModal">
                            <i class="bi bi-plus-lg me-1"></i> Add Line Item
                        </button>
                        @endif
                    </div>

                    @php
                        $worksheetTotal = $proposal->total_fee ?? 0;
                        $variance       = $contractVal - $worksheetTotal;
                    @endphp
                    <div class="row g-3 mb-3">
                        <div class="col-sm-4">
                            <div class="p-2 rounded" style="background:#f0f9ff; border:1px solid #bae6fd;">
                                <div style="font-size:0.68rem; font-weight:600; text-transform:uppercase; color:#0369a1;">Worksheet Total</div>
                                <div style="font-size:1.1rem; font-weight:700; color:#0c4a6e;">${{ number_format($worksheetTotal, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-2 rounded" style="background:#f0fdf4; border:1px solid #bbf7d0;">
                                <div style="font-size:0.68rem; font-weight:600; text-transform:uppercase; color:#15803d;">Contract Value</div>
                                <div style="font-size:1.1rem; font-weight:700; color:#14532d;">${{ number_format($contractVal, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-2 rounded" style="background:{{ $variance >= 0 ? '#f0fdf4' : '#fef2f2' }}; border:1px solid {{ $variance >= 0 ? '#bbf7d0' : '#fecaca' }};">
                                <div style="font-size:0.68rem; font-weight:600; text-transform:uppercase; color:{{ $variance >= 0 ? '#15803d' : '#dc2626' }};">Variance</div>
                                <div style="font-size:1.1rem; font-weight:700; color:{{ $variance >= 0 ? '#14532d' : '#991b1b' }};">
                                    {{ $variance >= 0 ? '+' : '' }}${{ number_format($variance, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="feeWorksheetContainer">
                        <div class="text-center py-3 text-muted" id="feeLoadingMsg" style="font-size:0.8rem;">
                            <div class="spinner-border spinner-border-sm me-2"></div> Loading fee worksheet…
                        </div>
                        <div id="feeWorksheetBody" class="d-none"></div>
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-3 mt-2 pt-2" style="border-top:1px solid #f3f4f6;">
                        <span style="font-size:0.8rem; color:#6b7280;">Total Fee</span>
                        <span class="fw-700" style="font-size:1.1rem;" id="totalFeeDisplay">
                            ${{ number_format($proposal->total_fee ?? 0, 2) }}
                        </span>
                    </div>
                </div>
            </div>

        </div>

        {{-- Right sidebar --}}
        <div class="col-lg-4">

            {{-- Actions --}}
            <div class="kore-card mb-3">
                <div class="kore-card-header"><h5>Actions</h5></div>
                <a href="{{ route('proposals.edit', $proposal) }}" class="btn btn-outline-primary btn-sm w-100 mb-2">
                    <i class="bi bi-pencil me-1"></i> Edit Proposal
                </a>
                @if(!$proposal->project && $isApproved)
                <a href="{{ route('projects.create') }}?proposal_id={{ $proposal->id }}" class="btn btn-success btn-sm w-100 mb-2">
                    <i class="bi bi-folder-plus me-1"></i> Create Project
                </a>
                @elseif(!$proposal->project && !$isApproved)
                <span class="btn btn-outline-secondary btn-sm w-100 mb-2 disabled" title="Proposal must be Approved first">
                    <i class="bi bi-lock me-1"></i> Create Project
                </span>
                @else
                <a href="{{ route('projects.show', $proposal->project) }}" class="btn btn-outline-secondary btn-sm w-100 mb-2">
                    <i class="bi bi-folder2-open me-1"></i> View Project
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

            {{-- Rate Overrides --}}
            <div class="kore-card mb-3">
                <div class="kore-card-header">
                    <h5><i class="bi bi-currency-dollar me-1"></i>Rate Overrides</h5>
                    @if(!$isApproved)
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                        onclick="document.getElementById('addRateOverrideForm').classList.toggle('d-none')"
                        style="font-size:0.7rem; padding:2px 8px;">
                        <i class="bi bi-plus-sm"></i>
                    </button>
                    @endif
                </div>
                @if($isApproved)
                <div class="kore-alert kore-alert-info mb-2" style="font-size:0.75rem;">
                    <i class="bi bi-lock me-1"></i> Locked on approved proposals.
                </div>
                @else
                <form id="addRateOverrideForm" action="{{ route('proposals.rate-schedules.store', $proposal) }}" method="POST" class="d-none mb-3">
                    @csrf
                    <div class="row g-2">
                        <div class="col-7">
                            <input type="text" name="scope_value" class="form-control form-control-sm"
                                placeholder="Role (e.g. Principal)" required>
                        </div>
                        <div class="col-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="font-size:0.72rem;">$</span>
                                <input type="number" name="hourly_rate" class="form-control" step="0.01" min="0" placeholder="Rate" required>
                            </div>
                        </div>
                        <div class="col-1 d-flex align-items-center">
                            <button type="submit" class="btn btn-primary btn-sm px-2"><i class="bi bi-check-lg"></i></button>
                        </div>
                    </div>
                    <input type="hidden" name="scope" value="role">
                </form>
                @endif
                @forelse($proposal->rateSchedules as $rs)
                <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                    <span style="font-size:0.75rem;">{{ $rs->scope_value }}</span>
                    <div class="d-flex align-items-center gap-2">
                        <span style="font-size:0.75rem; font-weight:600;">${{ number_format($rs->hourly_rate, 0) }}/hr</span>
                        @if(!$isApproved)
                        <form action="{{ route('proposals.rate-schedules.destroy', [$proposal, $rs]) }}" method="POST"
                            onsubmit="return confirm('Remove this rate override?')" class="d-inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-link btn-sm p-0 text-danger">
                                <i class="bi bi-x-lg" style="font-size:0.7rem;"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                @empty
                <div style="font-size:0.75rem; color:#9ca3af;">Global schedule of fees applies.</div>
                @endforelse
            </div>

            {{-- Similar Prior Proposals --}}
            <div class="kore-card mb-3" id="similarProposalsCard">
                <div class="kore-card-header">
                    <h5><i class="bi bi-intersect me-2"></i>Similar Prior Proposals</h5>
                    <button type="button" class="btn btn-link p-0" style="font-size:0.72rem; color:#6b7280;" onclick="loadSimilarProposals()">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
                <div id="similarProposalsList">
                    <div class="text-center py-3 text-muted" style="font-size:0.8rem;">
                        <div class="spinner-border spinner-border-sm me-1"></div> Scanning…
                    </div>
                </div>
            </div>

            {{-- Meta --}}
            <div class="kore-card">
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
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- TAB: CONTENT & ATTACHMENTS                                               --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade" id="tab-content">
    <div class="row g-4">
        <div class="col-lg-8">

            {{-- Save/Action bar --}}
            <div class="d-flex align-items-center justify-content-between mb-3 p-3 rounded"
                style="background:#f8fafc; border:1px solid #e5e7eb;">
                <div style="font-size:0.8rem; color:#6b7280;">
                    <i class="bi bi-info-circle me-1"></i>
                    Edit content directly below. Changes save automatically when you click <strong>Save Content</strong>.
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print Preview
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" id="saveContentBtn" onclick="saveAllContent()">
                        <i class="bi bi-cloud-upload me-1"></i> Save Content
                    </button>
                </div>
            </div>

            {{-- Executive Summary --}}
            <div class="kore-card mb-4">
                <div class="kore-card-header">
                    <h5><i class="bi bi-file-earmark-text me-2"></i>Executive Summary</h5>
                </div>
                <div id="editor-executive" class="quill-editor">
                    {!! $proposal->executive_summary !!}
                </div>
            </div>

            {{-- Scope of Work / Proposal Description --}}
            <div class="kore-card mb-4">
                <div class="kore-card-header">
                    <h5><i class="bi bi-list-check me-2"></i>Scope of Work &amp; Proposal Description</h5>
                </div>
                <div id="editor-scope" class="quill-editor">
                    {!! $proposal->scope_of_work !!}
                </div>
            </div>

            {{-- Deliverables Description --}}
            <div class="kore-card mb-4">
                <div class="kore-card-header">
                    <h5><i class="bi bi-check2-square me-2"></i>Deliverables</h5>
                </div>
                <div id="editor-description" class="quill-editor">
                    {!! $proposal->description !!}
                </div>
            </div>

            {{-- Terms & Conditions --}}
            <div class="kore-card mb-4">
                <div class="kore-card-header">
                    <h5><i class="bi bi-file-lock me-2"></i>Terms &amp; Conditions</h5>
                </div>
                <div id="editor-terms" class="quill-editor">
                    {!! $proposal->terms_and_conditions !!}
                </div>
            </div>

            {{-- Fee Summary (read-only from fee worksheet) --}}
            <div class="kore-card mb-4">
                <div class="kore-card-header">
                    <h5><i class="bi bi-calculator me-2"></i>Fee Summary</h5>
                    <span style="font-size:0.72rem; color:#6b7280;">Synced from Fee Worksheet</span>
                </div>
                <div class="row g-3">
                    <div class="col-sm-4">
                        <div class="field-label">Worksheet Total</div>
                        <div class="field-value fw-700">${{ number_format($proposal->total_fee ?? 0, 2) }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="field-label">Contract Value</div>
                        <div class="field-value fw-700">${{ number_format($contractVal, 2) }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="field-label">Expenses Reserve</div>
                        <div class="field-value">${{ number_format($expReserve, 2) }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="field-label">Net Professional Fees</div>
                        <div class="field-value fw-700 text-success">${{ number_format($netFees, 2) }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="field-label">Payment Terms</div>
                        <div class="field-value">{{ $proposal->payment_terms_days ? 'Net ' . $proposal->payment_terms_days : '—' }}</div>
                    </div>
                </div>
            </div>

        </div>
        <div class="col-lg-4">

            {{-- Google Doc --}}
            <div class="kore-card mb-3">
                <div class="kore-card-header">
                    <h5><i class="bi bi-google me-2"></i>Google Document</h5>
                </div>
                <div class="row g-2 align-items-end">
                    <div class="col-12">
                        <label style="font-size:0.72rem; font-weight:600; color:#6b7280;">Google Doc URL</label>
                        <input type="url" id="googleDocUrlInput" class="form-control form-control-sm"
                            value="{{ $proposal->google_doc_url }}"
                            placeholder="https://docs.google.com/document/d/...">
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-primary flex-grow-1" onclick="saveGoogleDocUrl()">
                            Save URL
                        </button>
                        @if($proposal->google_doc_url)
                        <a href="{{ $proposal->google_doc_url }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                        @endif
                    </div>
                </div>
                @if($proposal->google_doc_synced_at)
                <div style="font-size:0.72rem; color:#6b7280; margin-top:8px;">
                    <i class="bi bi-check-circle text-success me-1"></i>
                    Synced {{ $proposal->google_doc_synced_at->format('M d, Y g:ia') }}
                </div>
                @endif
            </div>

            {{-- Attachments --}}
            <div class="kore-card mb-3">
                <div class="kore-card-header"><h5><i class="bi bi-paperclip me-2"></i>Attachments</h5></div>
                <div class="text-muted text-center py-4" style="font-size:0.82rem;">
                    <i class="bi bi-cloud-upload" style="font-size:1.8rem; color:#d1d5db;"></i>
                    <p class="mt-2 mb-0">File attachments coming soon.</p>
                </div>
            </div>

            {{-- Quick Tips --}}
            <div class="kore-card" style="font-size:0.8rem; color:#6b7280;">
                <div class="fw-600 mb-2" style="color:#374151;">Content Tips</div>
                <ul class="ps-3 mb-0" style="line-height:1.8;">
                    <li>Use the rich-text editors to write proposal content directly.</li>
                    <li>Click <strong>Save Content</strong> to persist all changes.</li>
                    <li>Use <strong>Print Preview</strong> to generate a formatted proposal.</li>
                    <li>Link a Google Doc URL to embed an external proposal document.</li>
                </ul>
            </div>

        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- TAB: BILLING SCHEDULE                                                     --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade" id="tab-billing">
    @php $bs = $proposal->billingSchedule; @endphp

    {{-- Smart billing type notice --}}
    @php
        $billingTypeLabel = $btLabels[$billingType] ?? ucwords(str_replace('_',' ',$billingType));
    @endphp
    <div class="kore-alert kore-alert-info mb-4" style="font-size:0.8rem;">
        <i class="bi bi-info-circle me-1"></i>
        Billing type: <strong>{{ $billingTypeLabel }}</strong> — {{ match($billingType) {
            'time_and_material' => 'Set the engagement date range and billing cycle. Periods will be generated based on duration.',
            'fixed'             => 'Set the project date range and choose a billing cycle. Fees are divided evenly across periods.',
            'per_deliverable'   => 'Billing periods are triggered by deliverable and milestone completions in the linked project.',
            'retainer'          => 'Set the recurring period dates. The retainer amount repeats each billing cycle.',
            'hybrid'            => 'Combine time & material and fixed fee components for flexible billing.',
            default             => 'Configure your billing schedule below.',
        } }}
    </div>

    @if($billingType === 'per_deliverable')
    {{-- Per Deliverable: show project milestones --}}
    <div class="kore-card mb-4">
        <div class="kore-card-header">
            <h5><i class="bi bi-check2-all me-2"></i>Deliverable Billing Milestones</h5>
        </div>
        @if($proposal->project)
        <div class="mb-3" style="font-size:0.82rem; color:#6b7280;">
            Linked project: <a href="{{ route('projects.show', $proposal->project) }}" style="color:#2563eb;">
                {{ $proposal->project->year }}-{{ $proposal->project->project_number }}: {{ $proposal->project->title }}
            </a>
        </div>
        @php
            $deliverables = $proposal->project->deliverables()->with('milestones')->get();
        @endphp
        @if($deliverables->count() > 0)
        <div class="table-responsive">
            <table class="table table-sm" style="font-size:0.8rem;">
                <thead class="table-light">
                    <tr>
                        <th>Deliverable / Milestone</th>
                        <th>Due Date</th>
                        <th class="text-end">Fee</th>
                        <th>Billing Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($deliverables as $deliv)
                    <tr class="table-light">
                        <td colspan="4"><strong>{{ $deliv->name }}</strong></td>
                    </tr>
                    @forelse($deliv->milestones as $ms)
                    <tr>
                        <td class="ps-4"><i class="bi bi-flag text-primary me-1"></i>{{ $ms->name }}</td>
                        <td>{{ $ms->due_date?->format('M d, Y') ?? '—' }}</td>
                        <td class="text-end">
                            {{ $ms->deliverable_fee ? '$' . number_format($ms->deliverable_fee, 2) : '—' }}
                        </td>
                        <td>
                            @php
                                $bsColors = ['pending'=>'secondary','ready_to_bill'=>'warning','invoiced'=>'primary','paid'=>'success'];
                            @endphp
                            <span class="badge bg-{{ $bsColors[$ms->billing_status ?? 'pending'] ?? 'secondary' }}">
                                {{ ucwords(str_replace('_',' ', $ms->billing_status ?? 'pending')) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="ps-4 text-muted" style="font-size:0.75rem;">No milestones defined.</td>
                    </tr>
                    @endforelse
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-muted" style="font-size:0.82rem;">No deliverables defined in the project yet.
            <a href="{{ route('projects.show', $proposal->project) }}">Go to project</a> to add them.</p>
        @endif
        @else
        <div class="text-center py-4 text-muted" style="font-size:0.85rem;">
            <i class="bi bi-folder-x" style="font-size:2rem; color:#d1d5db;"></i>
            <p class="mt-2">No project linked yet. Create a project from this proposal to enable deliverable-based billing.</p>
            @if($isApproved)
            <a href="{{ route('projects.create') }}?proposal_id={{ $proposal->id }}" class="btn btn-sm btn-success">
                <i class="bi bi-folder-plus me-1"></i> Create Project
            </a>
            @endif
        </div>
        @endif
    </div>
    @endif

    {{-- Schedule Generator --}}
    <div class="kore-card mb-4">
        <div class="kore-card-header">
            <h5><i class="bi bi-calendar-plus me-2"></i>Billing Schedule Setup</h5>
        </div>
        <input type="hidden" id="bs_billing_type" value="{{ $billingType }}">
        <div class="row g-3">
            <div class="col-sm-3" id="bs_cycle_col">
                <label class="form-label">Billing Cycle</label>
                <select id="bs_billing_cycle" class="form-select form-select-sm">
                    @foreach(['biweekly' => 'Bi-Weekly', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'on_completion' => 'On Completion', 'custom' => 'Custom Dates'] as $val => $label)
                    <option value="{{ $val }}" {{ (($bs?->billing_cycle ?? $proposal->billing_cycle) === $val) ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <div id="bs_cycle_hint" style="font-size:0.7rem; color:#6b7280; margin-top:3px;"></div>
            </div>
            <div class="col-sm-2">
                <label class="form-label">Start Date</label>
                <input type="date" id="bs_start_date" class="form-control form-control-sm"
                    value="{{ $bs?->start_date?->format('Y-m-d') }}">
            </div>
            <div class="col-sm-2">
                <label class="form-label">End Date</label>
                <input type="date" id="bs_end_date" class="form-control form-control-sm"
                    value="{{ $bs?->end_date?->format('Y-m-d') }}">
            </div>
            <div class="col-sm-2">
                <label class="form-label">Payment Terms (days)</label>
                <input type="number" id="bs_payment_terms" class="form-control form-control-sm"
                    value="{{ $bs?->payment_terms_days ?? $proposal->payment_terms_days ?? 30 }}" min="0" max="365">
            </div>
            <div class="col-sm-4 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="bs_include_expenses"
                        {{ $bs?->include_expenses ? 'checked' : '' }}>
                    <label class="form-check-label" for="bs_include_expenses" style="font-size:0.8rem;">
                        Include Expenses per Period
                    </label>
                </div>
            </div>
            <div class="col-sm-8 d-flex align-items-end gap-2">
                <div style="font-size:0.8rem; color:#6b7280;">
                    Contract: <strong>${{ number_format($contractVal, 0) }}</strong>
                    &nbsp;|&nbsp; Expenses: <strong>${{ number_format($expReserve, 0) }}</strong>
                </div>
                <button type="button" class="btn btn-primary btn-sm ms-auto" onclick="generateBillingSchedule()">
                    <i class="bi bi-lightning-charge me-1"></i> Generate Schedule
                </button>
            </div>
        </div>
    </div>

    {{-- Periods Table --}}
    <div class="kore-card mb-4">
        <div class="kore-card-header">
            <h5><i class="bi bi-table me-2"></i>Invoice Periods</h5>
            <div class="d-flex align-items-center gap-2">
                @if(!in_array($billingType, ['fixed', 'retainer']))
                <button type="button" class="btn btn-sm btn-outline-info" onclick="syncAllPeriods(this)"
                    title="Re-compute all period fees from actual timesheet hours and deliverable completions">
                    <i class="bi bi-arrow-repeat me-1"></i> Sync from Work Data
                </button>
                @endif
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addPeriodModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Period
                </button>
            </div>
        </div>

        {{-- Billing-type context banner for data-driven types --}}
        @if($billingType === 'time_and_material')
        <div class="bs-context-banner bs-banner-tm">
            <i class="bi bi-clock me-1"></i>
            <strong>Time &amp; Material:</strong> Fees are calculated from billable timesheet entries logged against the linked project. Period amounts update automatically when you click <em>Sync from Work Data</em> or use the <i class="bi bi-calculator"></i> icon per period. <strong>$0.00 means no hours have been logged yet.</strong>
        </div>
        @elseif($billingType === 'per_deliverable')
        <div class="bs-context-banner bs-banner-del">
            <i class="bi bi-check2-square me-1"></i>
            <strong>Per Deliverable:</strong> Each period corresponds to one deliverable. Fee is included only when the deliverable is marked <strong>Ready to Bill</strong> in the project. Update deliverable status in the project, then click <em>Sync from Work Data</em>.
        </div>
        @elseif($billingType === 'hybrid')
        <div class="bs-context-banner bs-banner-hybrid">
            <i class="bi bi-layers me-1"></i>
            <strong>Hybrid:</strong> Fees combine T&amp;M hours from timesheets plus any ready-to-bill deliverables. Use the <i class="bi bi-calculator"></i> icon to add an optional fixed component per period.
        </div>
        @endif

        <div id="billingPeriodsBody">
            @if($bs && $bs->periods->count() > 0)
                @php
                    $statusColors    = ['draft'=>'secondary','approved'=>'info','invoiced'=>'primary','paid'=>'success','overdue'=>'danger'];
                    $delStatusColors = ['pending'=>'secondary','ready_to_bill'=>'warning','invoiced'=>'primary','paid'=>'success'];
                    $isDataDriven    = !in_array($billingType, ['fixed', 'retainer']);
                    $basisLabel      = $billingType === 'per_deliverable' ? 'Deliverable' : 'Basis';
                @endphp
                <div class="table-responsive">
                <table class="table table-sm bp-table">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>{{ $basisLabel }}</th>
                            <th class="text-end">Amount Billed</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($bs->periods->sortBy('sort_order') as $period)
                    @php
                        // Build context for fee basis cell
                        $ctx = [];
                        if (in_array($billingType, ['time_and_material', 'hybrid'])) {
                            $project = $proposal->project;
                            if ($project) {
                                $hrs = \App\Models\TimesheetEntry::where('project_id', $project->id)
                                    ->where('entry_type', 'billable')
                                    ->whereBetween('entry_date', [$period->period_start->toDateString(), $period->period_end->toDateString()])
                                    ->sum('hours');
                                $ctx['hours_logged'] = round((float)$hrs, 2);
                            } else {
                                $ctx['hours_logged'] = 0;
                            }
                        }
                        if ($billingType === 'per_deliverable' && $period->notes) {
                            $project = $proposal->project;
                            $ctx['deliverable_name']   = $period->notes;
                            $ctx['deliverable_status'] = 'pending';
                            if ($project) {
                                $d = $project->deliverables()->where('name', $period->notes)->first();
                                $ctx['deliverable_status'] = $d?->billing_status ?? 'pending';
                            }
                        }
                        // Actual billed = fees from real work; expenses shown as sub-line if non-zero
                        $billedFees     = (float) $period->fees_amount;
                        $billedExpenses = (float) $period->expenses_amount;
                        $billedTotal    = $billedFees + $billedExpenses;
                    @endphp
                    <tr id="bpr-{{ $period->id }}">
                        {{-- Period --}}
                        <td style="white-space:nowrap; font-weight:600; font-size:0.8rem;">
                            {{ $period->period_start->format('M d') }} – {{ $period->period_end->format('M d, Y') }}
                        </td>

                        {{-- Fee basis --}}
                        <td style="font-size:0.78rem; color:#6b7280;">
                            @if($billingType === 'time_and_material')
                                @if(($ctx['hours_logged'] ?? 0) > 0)
                                    <span class="badge bg-light text-dark border"><i class="bi bi-clock me-1"></i>{{ $ctx['hours_logged'] }} hrs</span>
                                @else
                                    <span class="text-muted"><i class="bi bi-clock me-1"></i>No hours logged</span>
                                @endif
                            @elseif($billingType === 'per_deliverable')
                                @if(isset($ctx['deliverable_name']))
                                    <div style="font-weight:500; color:#374151; font-size:0.77rem;">{{ $ctx['deliverable_name'] }}</div>
                                    <span class="badge bg-{{ $delStatusColors[$ctx['deliverable_status']] ?? 'secondary' }}" style="font-size:0.65rem;">
                                        {{ ucwords(str_replace('_',' ', $ctx['deliverable_status'])) }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            @elseif($billingType === 'hybrid')
                                @if(($ctx['hours_logged'] ?? 0) > 0)
                                    <span class="badge bg-light text-dark border"><i class="bi bi-clock me-1"></i>{{ $ctx['hours_logged'] }} hrs + deliverables</span>
                                @else
                                    <span class="text-muted">T&M + deliverables</span>
                                @endif
                            @else
                                <span class="text-muted" style="font-size:0.72rem;">Fixed split</span>
                            @endif
                        </td>

                        {{-- Amount billed (actual only) --}}
                        <td class="text-end" style="white-space:nowrap;">
                            <strong class="period-total-val" style="font-size:0.88rem;">
                                @if($isDataDriven && $billedTotal == 0)
                                    <span class="text-muted">$0.00</span>
                                @else
                                    ${{ number_format($billedTotal, 2) }}
                                @endif
                            </strong>
                            @if($isDataDriven && !$period->is_locked)
                            <button class="btn btn-link p-0 ms-1" style="font-size:0.65rem; color:#2563eb;"
                                onclick="openPeriodBreakdown({{ $period->id }})" title="View breakdown">
                                <i class="bi bi-calculator"></i>
                            </button>
                            @endif
                            @if($billedExpenses > 0)
                            <div style="font-size:0.68rem; color:#9ca3af;">incl. ${{ number_format($billedExpenses, 2) }} exp.</div>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td style="white-space:nowrap;">
                            <span class="badge bg-{{ $statusColors[$period->status] ?? 'secondary' }}">
                                {{ $period->is_locked ? '🔒 ' : '' }}{{ ucfirst($period->status) }}
                            </span>
                        </td>

                        {{-- Actions --}}
                        <td class="text-end" style="white-space:nowrap;">
                            @if($period->invoice_id)
                                <a href="{{ route('invoices.show', $period->invoice_id) }}"
                                   class="btn btn-xs btn-outline-success" style="font-size:0.7rem; padding:3px 8px;">
                                    <i class="bi bi-eye me-1"></i>{{ $period->invoice_number }}
                                </a>
                            @else
                                <button class="btn btn-xs btn-outline-primary" style="font-size:0.7rem; padding:3px 8px;"
                                    onclick="generateInvoiceFromPeriod({{ $period->id }}, this)">
                                    <i class="bi bi-receipt me-1"></i>Invoice
                                </button>
                            @endif
                            @if(!$period->is_locked)
                            <button class="btn btn-link btn-sm p-0 ms-2 text-danger"
                                onclick="deletePeriod({{ $period->id }})" title="Delete">
                                <i class="bi bi-trash" style="font-size:0.8rem;"></i>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
            @else
            <p class="text-muted text-center py-3" style="font-size:0.83rem;">
                No billing periods yet. Configure the schedule above and click Generate.
            </p>
            @endif
        </div>
    </div>

    {{-- Financial Summary --}}
    @if($bs && $bs->periods->count() > 0)
    @php
        $invoiced2 = $bs->total_invoiced;
        $paid2     = $bs->total_paid;
        $unpaid    = $invoiced2 - $paid2;
        $balance   = $contractVal - $invoiced2;
    @endphp
    <div class="row g-3">
        <div class="col-md-6">
            <div class="kore-card">
                <div class="kore-card-header"><h5>Invoiced Summary</h5></div>
                @foreach([
                    ['Invoiced to Date',  '$' . number_format($invoiced2, 2), '#374151'],
                    ['Paid to Date',      '$' . number_format($paid2, 2),     '#10b981'],
                    ['Unpaid Balance',    '$' . number_format($unpaid, 2),    $unpaid > 0 ? '#ef4444' : '#374151'],
                ] as [$label, $value, $color])
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span style="font-size:0.82rem; color:#6b7280;">{{ $label }}</span>
                    <span style="font-size:0.9rem; font-weight:600; color:{{ $color }};">{{ $value }}</span>
                </div>
                @endforeach
            </div>
        </div>
        <div class="col-md-6">
            <div class="kore-card">
                <div class="kore-card-header"><h5>Proposal Totals</h5></div>
                @foreach([
                    ['Contract Value',    '$' . number_format($contractVal, 2), '#374151'],
                    ['Expenses Reserve',  '$' . number_format($expReserve, 2),  '#6b7280'],
                    ['Remaining Balance', '$' . number_format($balance, 2),     $balance > 0 ? '#10b981' : '#ef4444'],
                ] as [$label, $value, $color])
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span style="font-size:0.82rem; color:#6b7280;">{{ $label }}</span>
                    <span style="font-size:0.9rem; font-weight:600; color:{{ $color }};">{{ $value }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>

</div>{{-- end tab-content --}}

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- MODALS                                                                    --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}

{{-- Add Line Item Modal --}}
<div class="modal fade" id="addLineItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Line Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-sm-4">
                        <label class="form-label">Phase Code</label>
                        <input type="text" id="li_phase_code" class="form-control form-control-sm" placeholder="SD, DD, CD, CA...">
                    </div>
                    <div class="col-sm-8">
                        <label class="form-label">Phase Label</label>
                        <input type="text" id="li_phase_label" class="form-control form-control-sm" placeholder="Schematic Design...">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Deliverable <span class="text-danger">*</span></label>
                        <input type="text" id="li_deliverable" class="form-control form-control-sm" placeholder="Architectural drawings set...">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <input type="text" id="li_role" class="form-control form-control-sm" placeholder="Principal, Senior Architect...">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Hours <span class="text-danger">*</span></label>
                        <input type="number" id="li_hours" class="form-control form-control-sm" min="0" step="0.5" placeholder="40">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Rate ($/hr)</label>
                        <input type="number" id="li_rate" class="form-control form-control-sm" min="0" step="0.01" placeholder="Auto">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Amount Override</label>
                        <input type="number" id="li_override" class="form-control form-control-sm" min="0" step="0.01" placeholder="Leave blank to use hours × rate">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Notes</label>
                        <input type="text" id="li_notes" class="form-control form-control-sm" placeholder="Optional notes...">
                    </div>
                </div>
                <div class="mt-3 p-2 bg-light rounded" id="ratePreview" style="font-size:0.78rem; display:none;">
                    <i class="bi bi-info-circle me-1"></i>
                    Resolved rate: <strong id="resolvedRateDisplay">—</strong> /hr
                </div>
                <div id="lineItemError" class="alert alert-danger mt-3 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="saveLineItemBtn">
                    <i class="bi bi-plus-lg me-1"></i> Add Line Item
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Period Fee Breakdown Modal --}}
<div class="modal fade" id="periodBreakdownModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pbm-title">Fee Breakdown</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="pbm-body" style="font-size:0.82rem;">
                <div class="text-center py-4"><span class="spinner-border spinner-border-sm"></span></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="pbm-apply"
                    data-period-id="" data-fixed-component="0" onclick="applyPeriodFees()">
                    <i class="bi bi-check-lg me-1"></i> Apply Computed Fees
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Add Period Modal --}}
<div class="modal fade" id="addPeriodModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Billing Period</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label">Period Start <span class="text-danger">*</span></label>
                        <input type="date" id="ap_start" class="form-control form-control-sm">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Period End <span class="text-danger">*</span></label>
                        <input type="date" id="ap_end" class="form-control form-control-sm">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Fees Amount <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" id="ap_fees" class="form-control" min="0" step="0.01" placeholder="0.00">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Expenses Amount</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" id="ap_expenses" class="form-control" min="0" step="0.01" placeholder="0.00">
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <input type="text" id="ap_notes" class="form-control form-control-sm" placeholder="Optional">
                    </div>
                </div>
                <div id="addPeriodError" class="alert alert-danger mt-3 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="saveManualPeriod()">Add Period</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
.nav-tabs .nav-link { font-size:0.82rem; color:#6b7280; border:none; padding:10px 16px; border-radius:0; }
.nav-tabs .nav-link:hover { color:#374151; background:#f9fafb; }
.nav-tabs .nav-link.active { color:#2563eb; font-weight:600; border-bottom:2px solid #2563eb !important; background:transparent; }
.form-label { font-size:0.75rem; font-weight:600; margin-bottom:4px; color:#374151; }
.field-label { font-size:0.7rem; font-weight:600; text-transform:uppercase; color:#9ca3af; letter-spacing:0.5px; }
.field-value { font-size:0.83rem; color:#111827; font-weight:500; }

/* Billing periods table */
.bp-table { font-size:0.8rem; margin-bottom:0; }
.bp-table thead th { font-size:0.7rem; font-weight:600; text-transform:uppercase; color:#9ca3af; letter-spacing:0.4px; border-bottom:2px solid #e5e7eb; padding:6px 8px; white-space:nowrap; }
.bp-table tbody td { padding:10px 8px; vertical-align:middle; border-color:#f3f4f6; }
.bp-table tbody tr:last-child td { border-bottom:none; }

/* Billing-type context banners */
.bs-context-banner { padding:8px 12px; border-radius:6px; font-size:0.78rem; margin-bottom:12px; }
.bs-banner-tm     { background:#eff6ff; border-left:3px solid #3b82f6; color:#1e40af; }
.bs-banner-del    { background:#fefce8; border-left:3px solid #eab308; color:#854d0e; }
.bs-banner-hybrid { background:#f0fdf4; border-left:3px solid #22c55e; color:#14532d; }

/* Quill editor */
.quill-editor { min-height:120px; }
.ql-container { font-size:0.88rem; border-radius:0 0 6px 6px; }
.ql-toolbar { border-radius:6px 6px 0 0; border-color:#e5e7eb; background:#f9fafb; }
.ql-container.ql-snow { border-color:#e5e7eb; }

/* Similar proposals */
.similar-proposal-row { padding:8px 0; border-bottom:1px solid #f3f4f6; }
.similar-proposal-row:last-child { border-bottom:none; }
.sim-score-bar { height:4px; border-radius:2px; background:#3b82f6; }
.sim-reason-chip { display:inline-block; font-size:0.62rem; padding:1px 5px; border-radius:3px; background:#eff6ff; color:#3b82f6; margin-right:3px; }

@media print {
    .nav-tabs, .btn, .kore-card-header .btn, #similarProposalsCard, .col-lg-4 { display:none !important; }
    .col-lg-8 { width:100% !important; max-width:100% !important; flex:0 0 100% !important; }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
const PROPOSAL_ID    = {{ $proposal->id }};
const CSRF_TOKEN     = '{{ csrf_token() }}';
const BASE_URL       = '/proposals/' + PROPOSAL_ID;
const LINE_ITEMS_URL = BASE_URL + '/line-items';
const IS_APPROVED    = {{ $isApproved ? 'true' : 'false' }};

// ── Quill Editors ─────────────────────────────────────────────────────────────
let editors = {};
document.addEventListener('DOMContentLoaded', () => {
    const editorConfig = {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ header: [1, 2, 3, false] }],
                ['bold', 'italic', 'underline'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['blockquote', 'link'],
                ['clean'],
            ]
        }
    };

    ['executive', 'scope', 'description', 'terms'].forEach(key => {
        const el = document.getElementById('editor-' + key);
        if (el) {
            const existing = el.innerHTML;
            editors[key] = new Quill(el, editorConfig);
            if (existing && existing.trim() !== '<br>') {
                editors[key].root.innerHTML = existing;
            }
        }
    });

    loadWorksheet();
    loadSimilarProposals();
    document.getElementById('li_role')?.addEventListener('change', resolveRateForRole);
    document.getElementById('saveLineItemBtn')?.addEventListener('click', saveLineItem);
});

// ── Save All Content ──────────────────────────────────────────────────────────
async function saveAllContent() {
    const btn = document.getElementById('saveContentBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving…';

    const body = {
        executive_summary:    editors['executive']?.root.innerHTML || '',
        scope_of_work:        editors['scope']?.root.innerHTML || '',
        description:          editors['description']?.root.innerHTML || '',
        terms_and_conditions: editors['terms']?.root.innerHTML || '',
    };

    try {
        const res = await fetch(BASE_URL + '/content', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify(body),
        });
        if (res.ok) {
            btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Saved!';
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-success');
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i> Save Content';
                btn.classList.remove('btn-success');
                btn.classList.add('btn-primary');
            }, 2500);
        } else {
            throw new Error('Save failed');
        }
    } catch (e) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i> Save Failed';
        btn.classList.add('btn-danger');
        setTimeout(() => {
            btn.classList.remove('btn-danger');
            btn.classList.add('btn-primary');
            btn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i> Save Content';
        }, 3000);
    }
}

// ── Google Doc URL ────────────────────────────────────────────────────────────
async function saveGoogleDocUrl() {
    const url = document.getElementById('googleDocUrlInput')?.value.trim();
    const res = await fetch(BASE_URL + '/content', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ google_doc_url: url }),
    });
    if (res.ok) window.location.reload();
}

// ── Fee Worksheet ─────────────────────────────────────────────────────────────
async function loadWorksheet() {
    try {
        const res  = await fetch(LINE_ITEMS_URL);
        const data = await res.json();
        document.getElementById('feeLoadingMsg')?.classList.add('d-none');
        document.getElementById('feeWorksheetBody')?.classList.remove('d-none');
        renderWorksheet(data);
    } catch (e) {
        const el = document.getElementById('feeLoadingMsg');
        if (el) el.textContent = 'Failed to load worksheet.';
    }
}

function renderWorksheet(data) {
    const container = document.getElementById('feeWorksheetBody');
    if (!container) return;
    const total = data.total_fee ?? 0;
    const display = document.getElementById('totalFeeDisplay');
    if (display) display.textContent = '$' + fmtMoney(total);

    if (!data.items || data.items.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-3" style="font-size:0.82rem;">No line items yet. Click "Add Line Item" to begin.</p>';
        return;
    }

    let html = '<table class="table table-sm" style="font-size:0.78rem;"><thead class="table-light"><tr><th>Phase</th><th>Deliverable</th><th>Role</th><th class="text-end">Hrs</th><th class="text-end">Rate</th><th class="text-end">Amount</th><th></th></tr></thead><tbody>';
    let lastPhase = '';
    data.items.forEach(item => {
        const phase  = item.phase_label || item.phase_code;
        const amount = item.amount_override ?? item.amount;
        html += `<tr>
            <td class="text-muted">${phase !== lastPhase ? esc(phase) : ''}</td>
            <td>${esc(item.deliverable)}</td>
            <td>${esc(item.role_name)}</td>
            <td class="text-end">${Number(item.hours).toFixed(1)}</td>
            <td class="text-end">$${Number(item.rate).toFixed(0)}</td>
            <td class="text-end fw-600">$${fmtMoney(amount)}</td>
            <td class="text-end">
                ${IS_APPROVED ? '' : `<button class="btn btn-link btn-sm p-0 text-danger" onclick="deleteLineItem(${item.id})"><i class="bi bi-x-lg"></i></button>`}
            </td>
        </tr>`;
        lastPhase = phase;
    });
    html += '</tbody></table>';
    container.innerHTML = html;
}

async function resolveRateForRole() {
    const role = document.getElementById('li_role').value.trim();
    if (!role) return;
    try {
        const res  = await fetch(LINE_ITEMS_URL + '/resolve-rate?role=' + encodeURIComponent(role));
        const data = await res.json();
        if (data.rate !== undefined) {
            document.getElementById('resolvedRateDisplay').textContent = '$' + Number(data.rate).toFixed(2);
            document.getElementById('ratePreview').style.display = 'block';
            if (!document.getElementById('li_rate').value) {
                document.getElementById('li_rate').value = data.rate;
            }
        }
    } catch {}
}

async function saveLineItem() {
    const errEl = document.getElementById('lineItemError');
    errEl.classList.add('d-none');
    const body = {
        phase_code:      document.getElementById('li_phase_code').value.trim() || 'GENERAL',
        phase_label:     document.getElementById('li_phase_label').value.trim() || null,
        deliverable:     document.getElementById('li_deliverable').value.trim(),
        role_name:       document.getElementById('li_role').value.trim(),
        hours:           parseFloat(document.getElementById('li_hours').value) || 0,
        rate:            document.getElementById('li_rate').value ? parseFloat(document.getElementById('li_rate').value) : null,
        amount_override: document.getElementById('li_override').value ? parseFloat(document.getElementById('li_override').value) : null,
        notes:           document.getElementById('li_notes').value.trim() || null,
    };
    if (!body.deliverable || !body.role_name) {
        errEl.textContent = 'Deliverable and Role are required.';
        errEl.classList.remove('d-none');
        return;
    }
    try {
        const res  = await fetch(LINE_ITEMS_URL, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }, body: JSON.stringify(body) });
        const data = await res.json();
        if (!res.ok) { errEl.textContent = data.message || 'Failed to save.'; errEl.classList.remove('d-none'); return; }
        bootstrap.Modal.getInstance(document.getElementById('addLineItemModal')).hide();
        loadWorksheet();
    } catch (e) {
        errEl.textContent = 'Network error.';
        errEl.classList.remove('d-none');
    }
}

async function deleteLineItem(id) {
    if (!confirm('Remove this line item?')) return;
    await fetch(LINE_ITEMS_URL + '/' + id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF_TOKEN } });
    loadWorksheet();
}

// ── Billing Schedule ──────────────────────────────────────────────────────────
function initBillingTab() {
    // Apply billing type intelligence on first open
    onBillingTypeChange();
}

const BILLING_TYPE_CYCLES = {
    fixed:            ['on_completion', 'custom'],
    time_and_material:['biweekly', 'monthly', 'quarterly', 'custom'],
    hybrid:           ['monthly', 'quarterly', 'on_completion', 'custom'],
    retainer:         ['biweekly', 'monthly', 'quarterly'],
    per_deliverable:  ['on_completion', 'custom'],
};
const BILLING_TYPE_DESC = {
    fixed:             'Fees divided evenly across periods. Set date range and cycle.',
    time_and_material: 'Invoices pull actual timesheet entries per period.',
    hybrid:            'Combine fixed fees and T&M hours. Both appear as invoice line items.',
    retainer:          'Recurring flat amount per billing cycle.',
    per_deliverable:   'One invoice period per deliverable, triggered on completion.',
};

function onBillingTypeChange() {
    const type = document.getElementById('bs_billing_type')?.value;
    const cycleSelect = document.getElementById('bs_billing_cycle');
    const cycleCol  = document.getElementById('bs_cycle_col');
    const cycleHint = document.getElementById('bs_cycle_hint');

    if (!type || !cycleSelect) return;

    const allowed = BILLING_TYPE_CYCLES[type] || [];
    const isPerDel = type === 'per_deliverable';

    // Show/hide cycle column
    if (cycleCol) cycleCol.style.opacity = isPerDel ? '0.45' : '1';

    // Filter cycle options to only valid ones for this billing type
    Array.from(cycleSelect.options).forEach(opt => {
        if (!opt.value) return; // skip blank option if any
        opt.disabled = allowed.length > 0 && !allowed.includes(opt.value);
        opt.style.display = (allowed.length > 0 && !allowed.includes(opt.value)) ? 'none' : '';
    });

    // Auto-select first valid option if current selection is now invalid
    if (allowed.length > 0 && !allowed.includes(cycleSelect.value)) {
        cycleSelect.value = allowed[0];
    }

    // Show contextual hint
    if (cycleHint) {
        cycleHint.textContent = BILLING_TYPE_DESC[type] || '';
    }
}

async function generateBillingSchedule() {
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generating…';

    const body = {
        billing_type:       document.getElementById('bs_billing_type').value,
        billing_cycle:      document.getElementById('bs_billing_cycle').value,
        start_date:         document.getElementById('bs_start_date').value,
        end_date:           document.getElementById('bs_end_date').value,
        include_expenses:   document.getElementById('bs_include_expenses').checked,
        payment_terms_days: parseInt(document.getElementById('bs_payment_terms').value) || 30,
    };

    try {
        const res  = await fetch(BASE_URL + '/billing-schedule/generate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify(body),
        });
        const data = await res.json();
        if (res.ok) {
            renderBillingPeriods(data.schedule.periods);
        } else {
            alert(data.message || 'Failed to generate schedule.');
        }
    } catch (e) {
        alert('Network error.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-lightning-charge me-1"></i> Generate Schedule';
    }
}

/**
 * Renders billing period rows with billing-type-specific context cells.
 * Each period includes a `context` object from the API with:
 *   - hours_logged / hours_fee        (T&M / hybrid)
 *   - deliverable_name / status       (per_deliverable)
 *   - period_index / period_count     (fixed / retainer)
 */
function renderBillingPeriods(periods) {
    const container = document.getElementById('billingPeriodsBody');
    if (!container) return;
    if (!periods || periods.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-3" style="font-size:0.83rem;">No periods generated.</p>';
        return;
    }

    const statusColors    = { draft:'secondary', approved:'info', invoiced:'primary', paid:'success', overdue:'danger' };
    const delStatusColors = { pending:'secondary', ready_to_bill:'warning', invoiced:'primary', paid:'success' };
    const billingType     = document.getElementById('bs_billing_type')?.value || 'fixed';
    const isDataDriven    = !['fixed', 'retainer'].includes(billingType);
    const basisLabel      = billingType === 'per_deliverable' ? 'Deliverable' : 'Basis';

    const fmtDate = d => {
        if (!d) return '';
        const dt = new Date((d + '').substring(0, 10) + 'T00:00:00');
        return dt.toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' });
    };

    let rows = '';
    periods.forEach(p => {
        const color  = statusColors[p.status] || 'secondary';
        const locked = p.is_locked ? '🔒 ' : '';
        const ctx    = p.context || {};
        const total  = (p.fees_amount || 0) + (p.expenses_amount || 0);
        const expAmt = p.expenses_amount || 0;

        // ── Fee basis cell ──────────────────────────────────────────────────
        let basisHtml = '';
        if (billingType === 'time_and_material') {
            basisHtml = (ctx.hours_logged > 0)
                ? `<span class="badge bg-light text-dark border"><i class="bi bi-clock me-1"></i>${ctx.hours_logged} hrs</span>`
                : `<span class="text-muted" style="font-size:0.75rem;"><i class="bi bi-clock me-1"></i>No hours logged</span>`;
        } else if (billingType === 'per_deliverable') {
            if (ctx.deliverable_name) {
                const dColor = delStatusColors[ctx.deliverable_status] || 'secondary';
                const dLabel = (ctx.deliverable_status || 'pending').replace(/_/g,' ');
                basisHtml = `<div style="font-size:0.77rem; font-weight:500; color:#374151;">${esc(ctx.deliverable_name)}</div>
                    <span class="badge bg-${dColor}" style="font-size:0.65rem;">${esc(dLabel)}</span>`;
            } else {
                basisHtml = `<span class="text-muted">—</span>`;
            }
        } else if (billingType === 'hybrid') {
            basisHtml = (ctx.hours_logged > 0)
                ? `<span class="badge bg-light text-dark border"><i class="bi bi-clock me-1"></i>${ctx.hours_logged} hrs + deliverables</span>`
                : `<span class="text-muted" style="font-size:0.75rem;">T&M + deliverables</span>`;
        } else {
            basisHtml = ctx.period_count > 1
                ? `<span class="text-muted" style="font-size:0.72rem;">Period ${ctx.period_index} of ${ctx.period_count}</span>`
                : `<span class="text-muted" style="font-size:0.72rem;">Fixed split</span>`;
        }

        // ── Amount cell ─────────────────────────────────────────────────────
        const computeBtn = (isDataDriven && !p.is_locked)
            ? `<button class="btn btn-link p-0 ms-1" style="font-size:0.65rem; color:#2563eb;"
                   onclick="openPeriodBreakdown(${p.id})" title="View breakdown"><i class="bi bi-calculator"></i></button>`
            : '';
        const amountHtml = (isDataDriven && total === 0)
            ? `<span class="text-muted period-total-val">$0.00</span>`
            : `<strong class="period-total-val">$${fmtMoney(total)}</strong>`;
        const expLine = expAmt > 0
            ? `<div style="font-size:0.68rem; color:#9ca3af;">incl. $${fmtMoney(expAmt)} exp.</div>`
            : '';

        // ── Action buttons ──────────────────────────────────────────────────
        let invoiceBtn = '';
        if (p.invoice_id && p.invoice_url) {
            invoiceBtn = `<a href="${p.invoice_url}" class="btn btn-xs btn-outline-success" style="font-size:0.7rem; padding:3px 8px;"><i class="bi bi-eye me-1"></i>${esc(p.invoice_number)}</a>`;
        } else {
            invoiceBtn = `<button class="btn btn-xs btn-outline-primary" style="font-size:0.7rem; padding:3px 8px;" onclick="generateInvoiceFromPeriod(${p.id}, this)"><i class="bi bi-receipt me-1"></i>Invoice</button>`;
        }
        const deleteBtn = !p.is_locked
            ? `<button class="btn btn-link btn-sm p-0 ms-2 text-danger" onclick="deletePeriod(${p.id})" title="Delete"><i class="bi bi-trash" style="font-size:0.8rem;"></i></button>`
            : '';

        rows += `<tr id="bpr-${p.id}">
            <td style="white-space:nowrap; font-weight:600; font-size:0.8rem;">${fmtDate(p.period_start)} – ${fmtDate(p.period_end)}</td>
            <td style="font-size:0.78rem; color:#6b7280;">${basisHtml}</td>
            <td class="text-end" style="white-space:nowrap;">${amountHtml}${computeBtn}${expLine}</td>
            <td style="white-space:nowrap;"><span class="badge bg-${color}">${locked}${p.status.charAt(0).toUpperCase()+p.status.slice(1)}</span></td>
            <td class="text-end" style="white-space:nowrap;">${invoiceBtn}${deleteBtn}</td>
        </tr>`;
    });

    container.innerHTML = `<div class="table-responsive"><table class="table table-sm bp-table">
        <thead><tr>
            <th>Period</th><th>${esc(basisLabel)}</th>
            <th class="text-end">Amount Billed</th>
            <th>Status</th><th class="text-end">Actions</th>
        </tr></thead>
        <tbody>${rows}</tbody>
    </table></div>`;
}

/**
 * Re-computes fees for all unlocked periods from actual timesheet/deliverable data.
 * For T&M: sums billable hours × rate for each period date range.
 * For per_deliverable: sets fee if deliverable is "ready_to_bill", else $0.
 * For hybrid: T&M hours + ready-to-bill deliverables.
 */
async function syncAllPeriods(btn) {
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Syncing…';

    try {
        const res  = await fetch(BASE_URL + '/billing-schedule/compute-all', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({}),
        });
        const data = await res.json();
        if (res.ok) {
            renderBillingPeriods(data.periods);
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Synced!';
            btn.classList.replace('btn-outline-info', 'btn-success');
            setTimeout(() => {
                btn.innerHTML = orig;
                btn.classList.replace('btn-success', 'btn-outline-info');
                btn.disabled = false;
            }, 2500);
        } else {
            alert(data.message || 'Failed to sync periods.');
            btn.disabled = false;
            btn.innerHTML = orig;
        }
    } catch (e) {
        alert('Network error syncing periods.');
        btn.disabled = false;
        btn.innerHTML = orig;
    }
}

// ── Period Fee Breakdown & Compute ────────────────────────────────────────────

async function openPeriodBreakdown(periodId) {
    const modal = new bootstrap.Modal(document.getElementById('periodBreakdownModal'));
    const body  = document.getElementById('pbm-body');
    const title = document.getElementById('pbm-title');
    const applyBtn = document.getElementById('pbm-apply');

    body.innerHTML = '<div class="text-center py-4"><span class="spinner-border spinner-border-sm"></span> Loading…</div>';
    applyBtn.dataset.periodId = periodId;
    applyBtn.dataset.fixedComponent = 0;
    modal.show();

    try {
        const res  = await fetch(BASE_URL + '/billing-schedule/periods/' + periodId + '/breakdown');
        const data = await res.json();

        title.textContent = 'Fee Breakdown — ' + data.period_start + ' to ' + data.period_end;

        let html = '';
        const type = data.billing_type;

        // ── Time & Material / Hybrid: timesheet hours ──────────────────────────
        if (data.timesheet) {
            const ts = data.timesheet;
            html += `<h6 style="font-size:0.78rem; font-weight:700; text-transform:uppercase; color:#6b7280; letter-spacing:.5px;">Billable Hours</h6>`;

            if (!ts.has_project) {
                html += `<p class="text-muted" style="font-size:0.8rem;">No project linked to this proposal yet.</p>`;
            } else if (ts.rows.length === 0) {
                html += `<p class="text-warning" style="font-size:0.8rem;"><i class="bi bi-exclamation-triangle me-1"></i>No billable timesheet entries recorded for this period.</p>`;
            } else {
                html += `<table class="table table-sm mb-2" style="font-size:0.78rem;">
                    <thead class="table-light"><tr><th>Staff</th><th>Role</th><th class="text-end">Hours</th><th class="text-end">Rate</th><th class="text-end">Subtotal</th></tr></thead><tbody>`;
                ts.rows.forEach(r => {
                    html += `<tr><td>${esc(r.user_name)}</td><td>${esc(r.role)}</td>
                        <td class="text-end">${r.hours.toFixed(2)}</td>
                        <td class="text-end">$${fmtMoney(r.rate)}</td>
                        <td class="text-end fw-600">$${fmtMoney(r.subtotal)}</td></tr>`;
                });
                html += `</tbody><tfoot><tr class="table-light">
                    <td colspan="2" class="fw-600">Total Billable Hours</td>
                    <td class="text-end fw-600">${ts.total_hours.toFixed(2)} hrs</td>
                    <td></td>
                    <td class="text-end fw-600 text-primary">$${fmtMoney(ts.total_fees)}</td>
                </tr></tfoot></table>`;
            }

            // Hybrid: fixed component input
            if (type === 'hybrid') {
                html += `<div class="mb-3">
                    <label class="form-label" style="font-size:0.75rem; font-weight:600;">Fixed Component (additional flat fee)</label>
                    <div class="input-group input-group-sm" style="max-width:180px;">
                        <span class="input-group-text">$</span>
                        <input type="number" id="pbm-fixed" class="form-control" min="0" step="0.01" placeholder="0.00"
                            oninput="document.getElementById('pbm-apply').dataset.fixedComponent=this.value||0">
                    </div>
                </div>`;
            }
        }

        // ── Per Deliverable / Hybrid: deliverable status ───────────────────────
        if (data.deliverables) {
            const dl = data.deliverables;
            html += `<h6 style="font-size:0.78rem; font-weight:700; text-transform:uppercase; color:#6b7280; letter-spacing:.5px; margin-top:12px;">Deliverables</h6>`;

            if (!dl.has_project) {
                html += `<p class="text-muted" style="font-size:0.8rem;">No project linked.</p>`;
            } else if (dl.rows.length === 0) {
                html += `<p class="text-warning" style="font-size:0.8rem;"><i class="bi bi-exclamation-triangle me-1"></i>No deliverables found for this period.</p>`;
            } else {
                const statusBadge = { pending:'secondary', ready_to_bill:'warning', invoiced:'primary', paid:'success' };
                html += `<table class="table table-sm mb-2" style="font-size:0.78rem;">
                    <thead class="table-light"><tr><th>Deliverable</th><th>Status</th><th class="text-end">Fee</th></tr></thead><tbody>`;
                dl.rows.forEach(r => {
                    const badge = statusBadge[r.billing_status] || 'secondary';
                    const readyCheck = r.ready ? '<i class="bi bi-check-circle-fill text-success me-1"></i>' : '';
                    html += `<tr>
                        <td>${readyCheck}${esc(r.name)}</td>
                        <td><span class="badge bg-${badge}">${esc(r.billing_status.replace(/_/g,' '))}</span></td>
                        <td class="text-end">${r.ready ? '$' + fmtMoney(r.fee) : '<span class="text-muted">—</span>'}</td>
                    </tr>`;
                });
                html += `</tbody><tfoot><tr class="table-light">
                    <td colspan="2" class="fw-600">Ready to Bill</td>
                    <td class="text-end fw-600 text-primary">$${fmtMoney(dl.total_fees)}</td>
                </tr></tfoot></table>`;

                if (dl.rows.every(r => !r.ready)) {
                    html += `<p class="text-muted" style="font-size:0.77rem;"><i class="bi bi-info-circle me-1"></i>Mark deliverables as <strong>Ready to Bill</strong> in the project to include them here.</p>`;
                }
            }
        }

        // Total preview
        const tsTotal = data.timesheet?.total_fees ?? 0;
        const dlTotal = data.deliverables?.total_fees ?? 0;
        const preview = tsTotal + dlTotal;
        html += `<div class="p-2 mt-2 rounded" style="background:#eff6ff; font-size:0.82rem;">
            <i class="bi bi-calculator me-1"></i>Computed fees will be set to: <strong id="pbm-preview">$${fmtMoney(preview)}</strong>
            <span class="text-muted ms-1" style="font-size:0.72rem;">(plus any fixed component for hybrid)</span>
        </div>`;

        body.innerHTML = html;
    } catch (e) {
        body.innerHTML = '<p class="text-danger">Failed to load breakdown.</p>';
    }
}

async function applyPeriodFees() {
    const btn      = document.getElementById('pbm-apply');
    const periodId = btn.dataset.periodId;
    const fixed    = parseFloat(btn.dataset.fixedComponent) || 0;
    const orig     = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Computing…';

    try {
        const res  = await fetch(BASE_URL + '/billing-schedule/periods/' + periodId + '/compute-fees', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body:    JSON.stringify({ fixed_component: fixed }),
        });
        const data = await res.json();

        if (res.ok) {
            // Update the row in place
            const row = document.getElementById('bpr-' + periodId);
            if (row) {
                const feeEl   = row.querySelector('.period-fee-val');
                const totalEl = row.querySelector('.period-total-val');
                if (feeEl)   feeEl.textContent   = '$' + fmtMoney(data.period.fees_amount);
                if (totalEl) totalEl.textContent  = '$' + fmtMoney(data.period.total_amount);
            }
            bootstrap.Modal.getInstance(document.getElementById('periodBreakdownModal')).hide();
        } else {
            alert(data.message || 'Failed to compute fees.');
        }
    } catch (e) {
        alert('Network error.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = orig;
    }
}

async function generateInvoiceFromPeriod(periodId, btn) {
    if (!confirm('Generate an invoice for this billing period?')) return;
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    try {
        const res  = await fetch(BASE_URL + '/billing-schedule/periods/' + periodId + '/generate-invoice', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({}),
        });
        let data;
        try {
            data = await res.json();
        } catch (parseErr) {
            const text = await res.text().catch(() => '');
            throw new Error('HTTP ' + res.status + ' — server returned non-JSON response. Run: php artisan migrate');
        }
        if (res.ok && data.success) {
            // Replace button with link to invoice
            btn.outerHTML = `<a href="${data.invoice_url}" class="btn btn-outline-success" style="font-size:0.68rem; padding:2px 7px;">
                <i class="bi bi-eye me-1"></i>${data.invoice_number}</a>`;
            // Reload to show updated status
            setTimeout(() => location.reload(), 800);
        } else {
            if (data.invoice_url) {
                window.location.href = data.invoice_url;
            } else {
                alert(data.message || 'Failed to generate invoice.');
                btn.disabled = false;
                btn.innerHTML = orig;
            }
        }
    } catch (e) {
        // Try to parse the response text for a real error message
        console.error('Invoice generation error:', e);
        alert('Server error: ' + (e.message || 'Could not generate invoice. Check that the database migration has been run.'));
        btn.disabled = false;
        btn.innerHTML = orig;
    }
}

async function deletePeriod(id) {
    if (!confirm('Delete this billing period?')) return;
    await fetch(BASE_URL + '/billing-schedule/periods/' + id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF_TOKEN } });
    location.reload();
}

async function saveManualPeriod() {
    const errEl = document.getElementById('addPeriodError');
    errEl.classList.add('d-none');
    const body = {
        period_start:    document.getElementById('ap_start').value,
        period_end:      document.getElementById('ap_end').value,
        fees_amount:     parseFloat(document.getElementById('ap_fees').value) || 0,
        expenses_amount: parseFloat(document.getElementById('ap_expenses').value) || 0,
        notes:           document.getElementById('ap_notes').value.trim() || null,
    };
    if (!body.period_start || !body.period_end) {
        errEl.textContent = 'Start and end dates are required.';
        errEl.classList.remove('d-none');
        return;
    }
    try {
        const res = await fetch(BASE_URL + '/billing-schedule/periods', {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify(body),
        });
        if (res.ok) {
            bootstrap.Modal.getInstance(document.getElementById('addPeriodModal')).hide();
            location.reload();
        } else {
            const data = await res.json();
            errEl.textContent = data.message || 'Failed to save.';
            errEl.classList.remove('d-none');
        }
    } catch (e) {
        errEl.textContent = 'Network error.';
        errEl.classList.remove('d-none');
    }
}

// ── Similar Proposals ──────────────────────────────────────────────────────────
async function loadSimilarProposals() {
    const list = document.getElementById('similarProposalsList');
    if (!list) return;
    try {
        const res  = await fetch(BASE_URL + '/similar');
        const data = await res.json();
        if (!data.similar || data.similar.length === 0) {
            list.innerHTML = '<div style="font-size:0.75rem; color:#9ca3af; text-align:center; padding:12px;">No similar proposals found.</div>';
            return;
        }
        list.innerHTML = data.similar.map(s => `
            <div class="similar-proposal-row">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <a href="/proposals/${s.id}" style="font-size:0.78rem; font-weight:600; color:#2563eb;">${esc(s.ref)}</a>
                    <span style="font-size:0.72rem; color:#6b7280;">${esc(s.company ?? '')}</span>
                    <span class="ms-auto" style="font-size:0.68rem; color:#6b7280;">${Math.round(s.score * 100)}% match</span>
                </div>
                <div style="font-size:0.75rem; color:#374151; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${esc(s.title)}</div>
                <div class="mt-1">
                    <div class="sim-score-bar" style="width:${Math.round(s.score * 100)}%;"></div>
                </div>
                <div class="mt-1">
                    ${(s.match_reasons || []).map(r => `<span class="sim-reason-chip">${esc(r)}</span>`).join('')}
                </div>
            </div>
        `).join('');
    } catch (e) {
        if (list) list.innerHTML = '<div style="font-size:0.75rem; color:#9ca3af; text-align:center; padding:12px;">Could not load similar proposals.</div>';
    }
}

// ── Utilities ────────────────────────────────────────────────────────────────
function fmtMoney(v) { return Number(v || 0).toLocaleString('en-US', { minimumFractionDigits:2, maximumFractionDigits:2 }); }
function esc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
</script>
@endpush

@endsection
