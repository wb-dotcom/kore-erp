@extends('layouts.app')

@section('content')

@php $isApproved = $proposal->isApproved(); @endphp

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
        </div>
        <div style="font-size:0.72rem; color:#6b7280;">
            {{ $proposal->ref }}
            @if($proposal->company) &mdash; {{ $proposal->company->name }} @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('proposals.edit', $proposal) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        @if(!$proposal->project && $isApproved)
        <a href="{{ route('projects.create') }}?proposal_id={{ $proposal->id }}" class="btn btn-sm btn-success">
            <i class="bi bi-folder-plus me-1"></i> Convert to Project
        </a>
        @elseif(!$proposal->project && !$isApproved)
        <span class="btn btn-sm btn-outline-secondary disabled" title="Proposal must be Approved before converting to a project">
            <i class="bi bi-lock me-1"></i> Convert to Project
        </span>
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
            <i class="bi bi-file-text me-1"></i> Content
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-fees" type="button">
            <i class="bi bi-table me-1"></i> Fee Worksheet
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-billing" type="button">
            <i class="bi bi-calendar-check me-1"></i> Billing Schedule
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-activities" type="button">
            <i class="bi bi-diagram-3 me-1"></i> Activities
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-attachments" type="button">
            <i class="bi bi-paperclip me-1"></i> Attachments
        </button>
    </li>
</ul>

<div class="tab-content pt-4" id="proposalTabContent">

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- TAB: DASHBOARD                                                            --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade show active" id="tab-dashboard">

    {{-- Financial Health Cards --}}
    @php
        $contractVal   = $proposal->contract_value ?? $proposal->total_fee ?? 0;
        $expReserve    = $proposal->expenses_reserve ?? 0;
        $netFees       = $contractVal - $expReserve;
        $bs            = $proposal->billingSchedule;
        $invoiced      = $bs ? $bs->total_invoiced : 0;
        $paid          = $bs ? $bs->total_paid : 0;
        $outstanding   = $invoiced - $paid;
        $pct           = $contractVal > 0 ? round(($invoiced / $contractVal) * 100) : 0;
    @endphp

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
        {{-- Summary details --}}
        <div class="col-lg-8">
            <div class="kore-card">
                <div class="kore-card-header">
                    <h5><i class="bi bi-info-circle me-2"></i>Proposal Summary</h5>
                </div>
                <div class="row g-3">
                    @php
                        $fields = [
                            ['Year',           $proposal->year],
                            ['Reference',      $proposal->ref],
                            ['Account Manager', $proposal->accountManager?->full_name ?? '—'],
                            ['Client',         $proposal->company?->name ?? '—'],
                            ['Contact',        $proposal->contact?->full_name ?? '—'],
                            ['Work Type',      $proposal->workType?->name ?? '—'],
                            ['PO Number',      $proposal->po_number ?? '—'],
                            ['Vendor Code',    $proposal->vendor_code ?? '—'],
                            ['Billing Type',   $proposal->billing_type ? ucwords(str_replace('_',' ',$proposal->billing_type)) : '—'],
                            ['Project Type',   $proposal->projectType?->name ?? '—'],
                            ['Submitted',      $proposal->submitted_date?->format('M d, Y') ?? '—'],
                            ['Approved',       $proposal->approved_date?->format('M d, Y') ?? '—'],
                            ['Expires',        $proposal->expiry_date?->format('M d, Y') ?? '—'],
                            ['Program',        $proposal->program?->name ?? '—'],
                        ];
                    @endphp
                    @foreach($fields as [$label, $value])
                    <div class="col-sm-4">
                        <div style="font-size:0.7rem; font-weight:600; text-transform:uppercase; color:#9ca3af; letter-spacing:0.5px;">{{ $label }}</div>
                        <div style="font-size:0.83rem; color:#111827; font-weight:500;">{{ $value }}</div>
                    </div>
                    @endforeach
                </div>

                @if($proposal->description)
                <hr class="my-3">
                <div style="font-size:0.7rem; font-weight:600; text-transform:uppercase; color:#9ca3af; letter-spacing:0.5px;">Description</div>
                <div style="font-size:0.83rem; white-space:pre-line; margin-top:4px;">{{ $proposal->description }}</div>
                @endif

                @if($proposal->notes)
                <div class="kore-alert kore-alert-info mt-3 mb-0" style="font-size:0.8rem;">
                    <i class="bi bi-lock me-1"></i> <strong>Internal Notes:</strong> {{ $proposal->notes }}
                </div>
                @endif
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
                    <i class="bi bi-folder-plus me-1"></i> Convert to Project
                </a>
                @elseif(!$proposal->project && !$isApproved)
                <span class="btn btn-outline-secondary btn-sm w-100 mb-2 disabled" title="Proposal must be Approved first">
                    <i class="bi bi-lock me-1"></i> Convert to Project
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
                    <i class="bi bi-lock me-1"></i> Locked — rates are fixed on an approved proposal.
                </div>
                @else
                <form id="addRateOverrideForm" action="{{ route('proposals.rate-schedules.store', $proposal) }}" method="POST" class="d-none mb-3">
                    @csrf
                    <div class="row g-2">
                        <div class="col-7">
                            <input type="text" name="scope_value" class="form-control form-control-sm"
                                placeholder="Role name (e.g. Principal)" required>
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
                        <div class="spinner-border spinner-border-sm me-1"></div> Scanning prior proposals…
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
{{-- TAB: CONTENT                                                              --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade" id="tab-content">
    <div class="row g-4">
        <div class="col-lg-8">
            {{-- Google Doc Integration --}}
            <div class="kore-card mb-4">
                <div class="kore-card-header">
                    <h5><i class="bi bi-google me-2"></i>Google Document</h5>
                    <div class="d-flex gap-2">
                        @if($proposal->google_doc_url)
                        <a href="{{ $proposal->google_doc_url }}" target="_blank" class="btn btn-sm btn-outline-primary" style="font-size:0.72rem;">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Open in Google
                        </a>
                        @endif
                    </div>
                </div>
                <div class="row g-3 align-items-end">
                    <div class="col-sm-9">
                        <label style="font-size:0.72rem; font-weight:600; color:#6b7280;">Google Doc URL</label>
                        <input type="url" id="googleDocUrlInput" class="form-control form-control-sm"
                            value="{{ $proposal->google_doc_url }}"
                            placeholder="https://docs.google.com/document/d/...">
                    </div>
                    <div class="col-sm-3">
                        <button type="button" class="btn btn-sm btn-primary w-100" onclick="saveGoogleDocUrl()">
                            Save URL
                        </button>
                    </div>
                </div>
                @if($proposal->google_doc_synced_at)
                <div style="font-size:0.72rem; color:#6b7280; margin-top:8px;">
                    <i class="bi bi-check-circle text-success me-1"></i>
                    Last synced: {{ $proposal->google_doc_synced_at->format('M d, Y \a\t g:ia') }}
                </div>
                @endif
                @if($proposal->google_doc_url)
                <div class="mt-3" style="border-radius:8px; overflow:hidden; border:1px solid #e5e7eb;">
                    <iframe src="{{ $proposal->google_doc_url }}/preview"
                        style="width:100%; height:500px; border:none;" allowfullscreen></iframe>
                </div>
                @endif
            </div>

            {{-- Executive Summary --}}
            <div class="kore-card mb-4">
                <div class="kore-card-header">
                    <h5><i class="bi bi-file-earmark-text me-2"></i>Executive Summary</h5>
                </div>
                @if($proposal->executive_summary)
                <div style="font-size:0.85rem; white-space:pre-line; line-height:1.7;">{{ $proposal->executive_summary }}</div>
                @else
                <p class="text-muted" style="font-size:0.83rem;">No executive summary yet. <a href="{{ route('proposals.edit', $proposal) }}">Edit proposal</a> to add one.</p>
                @endif
            </div>

            {{-- Scope of Work --}}
            <div class="kore-card mb-4">
                <div class="kore-card-header">
                    <h5><i class="bi bi-list-check me-2"></i>Scope of Work</h5>
                </div>
                @if($proposal->scope_of_work)
                <div style="font-size:0.85rem; white-space:pre-line; line-height:1.7;">{{ $proposal->scope_of_work }}</div>
                @else
                <p class="text-muted" style="font-size:0.83rem;">No scope of work defined yet.</p>
                @endif
            </div>

            {{-- Terms & Conditions --}}
            <div class="kore-card mb-4">
                <div class="kore-card-header">
                    <h5><i class="bi bi-file-lock me-2"></i>Terms &amp; Conditions</h5>
                </div>
                @if($proposal->terms_and_conditions)
                <div style="font-size:0.85rem; white-space:pre-line; line-height:1.7; color:#4b5563;">{{ $proposal->terms_and_conditions }}</div>
                @else
                <p class="text-muted" style="font-size:0.83rem;">No terms &amp; conditions defined yet.</p>
                @endif
            </div>
        </div>
        <div class="col-lg-4">
            <div class="kore-card">
                <div class="kore-card-header"><h5>Quick Actions</h5></div>
                <a href="{{ route('proposals.edit', $proposal) }}" class="btn btn-outline-primary btn-sm w-100 mb-2">
                    <i class="bi bi-pencil me-1"></i> Edit All Content
                </a>
                @if($proposal->google_doc_url)
                <a href="{{ $proposal->google_doc_url }}" target="_blank" class="btn btn-outline-secondary btn-sm w-100 mb-2">
                    <i class="bi bi-google me-1"></i> Open Google Doc
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- TAB: FEE WORKSHEET                                                        --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade" id="tab-fees">
    {{-- Financial Summary Bar --}}
    @php
        $worksheetTotal = $proposal->total_fee ?? 0;
        $contractVal2   = $proposal->contract_value ?? $worksheetTotal;
        $variance       = $contractVal2 - $worksheetTotal;
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="kore-card" style="border-top:3px solid #3b82f6;">
                <div style="font-size:0.7rem; font-weight:600; text-transform:uppercase; color:#6b7280;">Worksheet Total</div>
                <div style="font-size:1.3rem; font-weight:700;">${{ number_format($worksheetTotal, 2) }}</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="kore-card" style="border-top:3px solid #10b981;">
                <div style="font-size:0.7rem; font-weight:600; text-transform:uppercase; color:#6b7280;">Contract Value</div>
                <div style="font-size:1.3rem; font-weight:700;">${{ number_format($contractVal2, 2) }}</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="kore-card" style="border-top:3px solid {{ $variance >= 0 ? '#10b981' : '#ef4444' }};">
                <div style="font-size:0.7rem; font-weight:600; text-transform:uppercase; color:#6b7280;">Variance</div>
                <div style="font-size:1.3rem; font-weight:700; color:{{ $variance >= 0 ? '#10b981' : '#ef4444' }};">
                    {{ $variance >= 0 ? '+' : '' }}${{ number_format($variance, 2) }}
                </div>
            </div>
        </div>
    </div>

    <div class="kore-card">
        <div class="kore-card-header">
            <h5><i class="bi bi-table me-2"></i>Fee Worksheet</h5>
            @if($isApproved)
            <span class="badge bg-warning text-dark" style="font-size:0.7rem;">
                <i class="bi bi-lock me-1"></i> Locked — Approved
            </span>
            @else
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addLineItemModal">
                <i class="bi bi-plus-lg me-1"></i> Add Line Item
            </button>
            @endif
        </div>
        @if($isApproved)
        <div class="kore-alert kore-alert-info mb-3" style="font-size:0.8rem;">
            <i class="bi bi-lock me-1"></i> <strong>Fee worksheet is locked.</strong> Hours and rates are fixed once a proposal is approved. To make changes, edit the full proposal.
        </div>
        @endif

        <div id="feeWorksheetContainer">
            <div class="text-center py-4 text-muted" id="feeLoadingMsg">
                <div class="spinner-border spinner-border-sm me-2"></div> Loading fee worksheet…
            </div>
            <div id="feeWorksheetBody" class="d-none"></div>
        </div>

        <div class="border-top pt-3 mt-2 d-flex justify-content-end align-items-center gap-3">
            <span style="font-size:0.8rem; color:#6b7280;">Total Fee</span>
            <span class="fw-700" style="font-size:1.1rem;" id="totalFeeDisplay">
                ${{ number_format($proposal->total_fee ?? 0, 2) }}
            </span>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- TAB: BILLING SCHEDULE                                                     --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade" id="tab-billing">
    @php $bs = $proposal->billingSchedule; @endphp

    {{-- Schedule Generator --}}
    <div class="kore-card mb-4">
        <div class="kore-card-header">
            <h5><i class="bi bi-calendar-plus me-2"></i>Billing Schedule Setup</h5>
        </div>
        <div class="row g-3">
            <div class="col-sm-3">
                <label class="form-label">Billing Type</label>
                <select id="bs_billing_type" class="form-select form-select-sm">
                    @foreach(['fixed' => 'Fixed Fee', 'time_and_material' => 'Time & Material', 'per_deliverable' => 'Per Deliverable', 'retainer' => 'Retainer'] as $val => $label)
                    <option value="{{ $val }}" {{ ($bs?->billing_type === $val) ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label">Billing Cycle</label>
                <select id="bs_billing_cycle" class="form-select form-select-sm">
                    @foreach(['biweekly' => 'Bi-Weekly (15 days)', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'on_completion' => 'On Completion', 'custom' => 'Custom'] as $val => $label)
                    <option value="{{ $val }}" {{ ($bs?->billing_cycle === $val) ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
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
            <div class="col-sm-3 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="bs_include_expenses"
                        {{ $bs?->include_expenses ? 'checked' : '' }}>
                    <label class="form-check-label" for="bs_include_expenses" style="font-size:0.8rem;">
                        Include Expenses per Period
                    </label>
                </div>
            </div>
            <div class="col-sm-9 d-flex align-items-end gap-2">
                <div style="font-size:0.8rem; color:#6b7280;">
                    Contract: <strong>${{ number_format($proposal->contract_value ?? $proposal->total_fee ?? 0, 0) }}</strong>
                    &nbsp;|&nbsp;
                    Expenses Reserve: <strong>${{ number_format($proposal->expenses_reserve ?? 0, 0) }}</strong>
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
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="showAddPeriodForm()">
                <i class="bi bi-plus-lg me-1"></i> Add Period
            </button>
        </div>

        <div id="billingPeriodsContainer">
            <div class="text-center py-4 text-muted" id="billingLoadingMsg" style="display:none;">
                <div class="spinner-border spinner-border-sm me-2"></div> Loading…
            </div>
            <div id="billingPeriodsBody">
                @if($bs && $bs->periods->count() > 0)
                    @include('proposals.partials.billing-periods', ['periods' => $bs->periods])
                @else
                <p class="text-muted text-center py-3" style="font-size:0.83rem;">
                    No billing periods yet. Configure the schedule above and click Generate.
                </p>
                @endif
            </div>
        </div>
    </div>

    {{-- Financial Summary --}}
    <div class="row g-3" id="billingSummarySection" style="{{ (!$bs || $bs->periods->count() === 0) ? 'display:none;' : '' }}">
        @php
            $invoiced2 = $bs ? $bs->total_invoiced : 0;
            $paid2     = $bs ? $bs->total_paid : 0;
            $unpaid    = $invoiced2 - $paid2;
            $cv        = $proposal->contract_value ?? $proposal->total_fee ?? 0;
            $balance   = $cv - $invoiced2;
        @endphp
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
                <div class="kore-card-header"><h5>Proposal Summary</h5></div>
                @foreach([
                    ['Contract Value',  '$' . number_format($cv, 2),       '#374151'],
                    ['Expenses Reserve','$' . number_format($expReserve, 2), '#6b7280'],
                    ['Remaining Balance','$' . number_format($balance, 2), $balance > 0 ? '#10b981' : '#ef4444'],
                ] as [$label, $value, $color])
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span style="font-size:0.82rem; color:#6b7280;">{{ $label }}</span>
                    <span style="font-size:0.9rem; font-weight:600; color:{{ $color }};">{{ $value }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- TAB: ACTIVITIES                                                           --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade" id="tab-activities">
    {{-- Toolbar --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div style="font-size:0.82rem; color:#6b7280;" id="activitiesSummaryBar">
            Loading activities…
        </div>
        <div class="d-flex gap-2">
            @if($activityTemplates->count() > 0)
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-copy me-1"></i> Copy from Template
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @foreach($activityTemplates as $tpl)
                    <li>
                        <button class="dropdown-item" style="font-size:0.82rem;"
                            onclick="copyFromTemplate({{ $tpl->id }}, '{{ addslashes($tpl->name) }}')">
                            {{ $tpl->name }}
                            <span class="text-muted" style="font-size:0.72rem;">{{ number_format($tpl->total_budgeted_hours, 0) }}hrs</span>
                        </button>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
            <button type="button" class="btn btn-sm btn-outline-info" onclick="openImportFromProposalModal()">
                <i class="bi bi-intersect me-1"></i> Import from Prior Proposal
            </button>
            <button type="button" class="btn btn-sm btn-primary" onclick="showAddDeliverableModal()">
                <i class="bi bi-plus-lg me-1"></i> Add Deliverable
            </button>
        </div>
    </div>

    {{-- Activities Tree --}}
    <div id="activitiesTree">
        <div class="text-center py-5 text-muted">
            <div class="spinner-border spinner-border-sm me-2"></div> Loading activities…
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- TAB: ATTACHMENTS                                                          --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade" id="tab-attachments">
    <div class="kore-card">
        <div class="kore-card-header">
            <h5><i class="bi bi-paperclip me-2"></i>Attachments</h5>
        </div>
        <div class="text-muted text-center py-5" style="font-size:0.85rem;">
            <i class="bi bi-cloud-upload" style="font-size:2rem; color:#d1d5db;"></i>
            <p class="mt-2">File attachments will be available in the next release.</p>
        </div>
    </div>
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

{{-- Add Deliverable Modal --}}
<div class="modal fade" id="addDeliverableModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deliverableModalTitle">Add Deliverable</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editDeliverableId">
                <div class="mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" id="deliverableName" class="form-control form-control-sm" placeholder="e.g. Store Rollout Assessment">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea id="deliverableDesc" class="form-control form-control-sm" rows="2" placeholder="Optional description..."></textarea>
                </div>
                <div id="deliverableError" class="alert alert-danger d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="saveDeliverable()">Save</button>
            </div>
        </div>
    </div>
</div>

{{-- Add Activity Modal --}}
<div class="modal fade" id="addActivityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="activityModalTitle">Add Activity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="activityDeliverableId">
                <input type="hidden" id="editActivityId">
                <div class="mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" id="activityName" class="form-control form-control-sm" placeholder="e.g. Site Survey & Documentation">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Start Day (from project start)</label>
                        <input type="number" id="activityStartDay" class="form-control form-control-sm" min="0" placeholder="e.g. 1">
                    </div>
                    <div class="col-6">
                        <label class="form-label">End Day</label>
                        <input type="number" id="activityEndDay" class="form-control form-control-sm" min="0" placeholder="e.g. 14">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-8">
                        <label class="form-label">Assigned Role</label>
                        <input type="text" id="activityRole" class="form-control form-control-sm" placeholder="e.g. Senior Architect">
                    </div>
                    <div class="col-4">
                        <label class="form-label">Budgeted Hours</label>
                        <input type="number" id="activityHours" class="form-control form-control-sm" min="0" step="0.5" placeholder="40">
                    </div>
                </div>
                <div id="activityError" class="alert alert-danger d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="saveActivity()">Save</button>
            </div>
        </div>
    </div>
</div>

{{-- Add Task Modal --}}
<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="taskModalTitle">Add Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="taskActivityId">
                <input type="hidden" id="editTaskId">
                <div class="mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" id="taskName" class="form-control form-control-sm" placeholder="e.g. Conduct site visits">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Due Day (from project start)</label>
                        <input type="number" id="taskDueDay" class="form-control form-control-sm" min="0" placeholder="e.g. 10">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Est. Hours</label>
                        <input type="number" id="taskHours" class="form-control form-control-sm" min="0" step="0.5" placeholder="8">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Assigned Role</label>
                    <input type="text" id="taskRole" class="form-control form-control-sm" placeholder="e.g. Architect">
                </div>
                <div id="taskError" class="alert alert-danger d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="saveTask()">Save</button>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- MODAL: Import from Prior Proposal                                        --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="importFromProposalModal" tabindex="-1" aria-labelledby="importProposalModalLabel">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importProposalModalLabel">
                    <i class="bi bi-intersect me-2"></i>Import Deliverables from Prior Proposal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                {{-- Step 1: Choose source proposal --}}
                <div id="importStep1" class="p-3">
                    <div class="d-flex gap-2 mb-3 align-items-center">
                        <div class="flex-grow-1">
                            <input type="text" id="importProposalSearch" class="form-control form-control-sm"
                                placeholder="Search by proposal ref, title, or client…" oninput="filterImportProposals()">
                        </div>
                        <span id="importLoadingSpinner" class="text-muted" style="font-size:0.78rem; white-space:nowrap;">
                            <span class="spinner-border spinner-border-sm me-1"></span>Loading…
                        </span>
                    </div>
                    <div id="importProposalsList" style="max-height:420px; overflow-y:auto;">
                        {{-- Populated by JS --}}
                    </div>
                </div>

                {{-- Step 2: Pick deliverables from the selected proposal --}}
                <div id="importStep2" class="d-none">
                    <div class="bg-light border-bottom px-3 py-2 d-flex align-items-center gap-2" style="font-size:0.82rem;">
                        <button type="button" class="btn btn-link btn-sm p-0 text-secondary" onclick="importGoBack()">
                            <i class="bi bi-arrow-left me-1"></i> Back
                        </button>
                        <span id="importStep2Title" class="fw-600"></span>
                    </div>

                    <div class="px-3 pt-2 pb-1 d-flex gap-2 align-items-center" style="font-size:0.8rem;">
                        <button type="button" class="btn btn-link btn-sm p-0" onclick="importSelectAll()">Select All</button>
                        &nbsp;/&nbsp;
                        <button type="button" class="btn btn-link btn-sm p-0" onclick="importSelectNone()">None</button>
                        <span class="ms-auto text-muted" id="importDupeWarning" style="font-size:0.75rem;"></span>
                    </div>

                    <div id="importDeliverableList" class="px-3 pb-3" style="max-height:400px; overflow-y:auto;">
                        {{-- Populated by JS --}}
                    </div>

                    <div id="importMsg" class="mx-3 mb-2 alert alert-info d-none" style="font-size:0.82rem;"></div>
                </div>
            </div>
            <div class="modal-footer" id="importModalFooter">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm d-none" id="importConfirmBtn" onclick="doImportFromProposal()">
                    <i class="bi bi-box-arrow-in-down me-1"></i> Import Selected
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.nav-tabs .nav-link { font-size:0.82rem; color:#6b7280; border:none; padding:10px 16px; border-radius:0; }
.nav-tabs .nav-link:hover { color:#374151; background:#f9fafb; }
.nav-tabs .nav-link.active { color:#2563eb; font-weight:600; border-bottom:2px solid #2563eb !important; background:transparent; }
.form-label { font-size:0.75rem; font-weight:600; margin-bottom:4px; color:#374151; }

/* Activities tree */
.deliverable-block { border:1px solid #e5e7eb; border-radius:8px; margin-bottom:12px; overflow:hidden; }
.deliverable-header { background:#f8fafc; padding:10px 14px; display:flex; align-items:center; gap:10px; border-bottom:1px solid #e5e7eb; }
.deliverable-body { padding:0 0 0 20px; }
.activity-block { border-left:3px solid #3b82f6; margin:10px 14px 10px 0; border-radius:0 6px 6px 0; background:#fff; }
.activity-header { padding:8px 12px; display:flex; align-items:center; gap:8px; background:#f0f6ff; border-radius:0 6px 0 0; }
.task-row { padding:6px 12px 6px 28px; font-size:0.78rem; border-top:1px solid #f3f4f6; display:flex; align-items:center; gap:8px; }
.task-row:first-child { border-top:none; }
.activity-tasks { padding-bottom:4px; }
.badge-role { background:#eff6ff; color:#3b82f6; font-size:0.68rem; padding:2px 6px; border-radius:4px; font-weight:500; }
.badge-days { background:#f0fdf4; color:#16a34a; font-size:0.68rem; padding:2px 6px; border-radius:4px; }

/* Billing periods table */
.billing-period-row { display:grid; grid-template-columns:130px 130px 1fr 1fr 1fr 120px 120px 60px; gap:8px; align-items:center; padding:8px 0; border-bottom:1px solid #f3f4f6; font-size:0.8rem; }
.billing-period-row.header { font-size:0.7rem; font-weight:600; text-transform:uppercase; color:#9ca3af; letter-spacing:0.5px; padding-bottom:6px; border-bottom:2px solid #e5e7eb; }

/* Similar proposals card */
.similar-proposal-row { display:flex; flex-direction:column; gap:2px; padding:8px 0; border-bottom:1px solid #f3f4f6; cursor:pointer; }
.similar-proposal-row:last-child { border-bottom:none; }
.similar-proposal-row:hover { background:#f8fafc; border-radius:4px; padding-left:4px; }
.sim-score-bar { height:4px; border-radius:2px; background:#3b82f6; transition:width 0.3s; }
.sim-reason-chip { display:inline-block; font-size:0.62rem; padding:1px 5px; border-radius:3px; background:#eff6ff; color:#3b82f6; margin-right:3px; }
.import-deliverable-row { padding:8px; border:1px solid #e5e7eb; border-radius:6px; margin-bottom:6px; }
.import-deliverable-row.is-duplicate { border-color:#fbbf24; background:#fffbeb; }
.import-deliverable-row.is-duplicate .dupe-badge { display:inline-block; font-size:0.62rem; padding:1px 5px; border-radius:3px; background:#fef3c7; color:#92400e; margin-left:6px; }
.import-deliverable-row .dupe-badge { display:none; }
</style>
@endpush

@push('scripts')
<script>
const PROPOSAL_ID    = {{ $proposal->id }};
const CSRF_TOKEN     = '{{ csrf_token() }}';
const BASE_URL       = '/proposals/' + PROPOSAL_ID;
const LINE_ITEMS_URL = BASE_URL + '/line-items';

// ── Init ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadWorksheet();
    loadSimilarProposals();

    // Load activities when tab is shown
    document.querySelector('[data-bs-target="#tab-activities"]')?.addEventListener('shown.bs.tab', () => {
        loadActivities();
    });

    document.getElementById('li_role')?.addEventListener('change', resolveRateForRole);
    document.getElementById('saveLineItemBtn')?.addEventListener('click', saveLineItem);
});

// ── Google Doc URL ────────────────────────────────────────────────────────────
async function saveGoogleDocUrl() {
    const url = document.getElementById('googleDocUrlInput')?.value.trim();
    const res = await fetch(BASE_URL, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ google_doc_url: url }),
    });
    if (res.ok) {
        window.location.reload();
    }
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
                <button class="btn btn-link btn-sm p-0 text-danger" onclick="deleteLineItem(${item.id})">
                    <i class="bi bi-x-lg"></i>
                </button>
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

function renderBillingPeriods(periods) {
    const container = document.getElementById('billingPeriodsBody');
    const summarySection = document.getElementById('billingSummarySection');
    if (!container) return;

    if (!periods || periods.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-3" style="font-size:0.83rem;">No periods generated.</p>';
        return;
    }

    const statusColors = { draft:'secondary', approved:'info', invoiced:'primary', paid:'success', overdue:'danger' };

    let html = `<div class="billing-period-row header">
        <div>Period Start</div><div>Period End</div>
        <div>Fees</div><div>Expenses</div><div>Total</div>
        <div>Status</div><div>Invoice #</div><div></div>
    </div>`;

    periods.forEach(p => {
        const color = statusColors[p.status] || 'secondary';
        const locked = p.is_locked ? '🔒 ' : '';
        html += `<div class="billing-period-row">
            <div>${p.period_start}</div>
            <div>${p.period_end}</div>
            <div>$${fmtMoney(p.fees_amount)}</div>
            <div>$${fmtMoney(p.expenses_amount)}</div>
            <div><strong>$${fmtMoney(p.total_amount)}</strong></div>
            <div><span class="badge bg-${color}">${locked}${p.status}</span></div>
            <div style="font-size:0.75rem; color:#6b7280;">${p.invoice_number ?? '—'}</div>
            <div>
                ${!p.is_locked ? `<button class="btn btn-link btn-sm p-0 text-danger" onclick="deletePeriod(${p.id})" title="Delete"><i class="bi bi-trash" style="font-size:0.75rem;"></i></button>` : ''}
            </div>
        </div>`;
    });

    container.innerHTML = html;
    if (summarySection) summarySection.style.display = '';
}

async function deletePeriod(id) {
    if (!confirm('Delete this billing period?')) return;
    await fetch(BASE_URL + '/billing-schedule/periods/' + id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF_TOKEN } });
    loadBillingSchedule();
}

async function loadBillingSchedule() {
    const res  = await fetch(BASE_URL + '/billing-schedule');
    const data = await res.json();
    if (data.periods) renderBillingPeriods(data.periods);
}

function showAddPeriodForm() {
    alert('Manual period entry — coming soon. Use Generate Schedule above.');
}

// ── Activities ────────────────────────────────────────────────────────────────
async function loadActivities() {
    const tree = document.getElementById('activitiesTree');
    if (!tree) return;
    tree.innerHTML = '<div class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm me-2"></div></div>';

    const res  = await fetch(BASE_URL + '/deliverables');
    const data = await res.json();

    const summaryBar = document.getElementById('activitiesSummaryBar');
    if (summaryBar) {
        summaryBar.textContent = `${data.total_activities ?? 0} activities · ${data.total_tasks ?? 0} tasks · ${Number(data.total_hours ?? 0).toFixed(1)} budgeted hours`;
    }

    if (!data.deliverables || data.deliverables.length === 0) {
        tree.innerHTML = '<div class="text-center py-5 text-muted" style="font-size:0.85rem;"><i class="bi bi-diagram-3" style="font-size:2rem; color:#d1d5db;"></i><p class="mt-2">No deliverables yet.<br>Click "Add Deliverable" to start building your work breakdown.</p></div>';
        return;
    }

    tree.innerHTML = data.deliverables.map(d => renderDeliverable(d)).join('');
}

function renderDeliverable(d) {
    const totalHours = (d.activities || []).reduce((s, a) => s + (parseFloat(a.budgeted_hours) || 0), 0);
    const activitiesHtml = (d.activities || []).map(a => renderActivity(a)).join('');

    return `<div class="deliverable-block" id="deliv-${d.id}">
        <div class="deliverable-header">
            <i class="bi bi-collection text-primary"></i>
            <strong style="font-size:0.88rem; flex:1;">${esc(d.name)}</strong>
            <span class="text-muted" style="font-size:0.75rem;">${totalHours.toFixed(1)} hrs</span>
            <button class="btn btn-link btn-sm p-0 ms-2" onclick="showAddActivityModal(${d.id})" title="Add Activity">
                <i class="bi bi-plus-circle text-primary" style="font-size:0.85rem;"></i>
            </button>
            <button class="btn btn-link btn-sm p-0 ms-1" onclick="editDeliverable(${d.id}, '${esc(d.name)}', '${esc(d.description ?? '')}')" title="Edit">
                <i class="bi bi-pencil text-muted" style="font-size:0.78rem;"></i>
            </button>
            <button class="btn btn-link btn-sm p-0 ms-1" onclick="deleteDeliverable(${d.id})" title="Delete">
                <i class="bi bi-trash text-danger" style="font-size:0.78rem;"></i>
            </button>
        </div>
        <div class="deliverable-body">
            ${activitiesHtml || '<div style="padding:12px 14px; font-size:0.78rem; color:#9ca3af;">No activities yet. Click + to add.</div>'}
        </div>
    </div>`;
}

function renderActivity(a) {
    const tasksHtml = (a.tasks || []).map(t => renderTask(t)).join('');
    const daysLabel = (a.relative_start_day != null && a.relative_end_day != null)
        ? `Day ${a.relative_start_day}–${a.relative_end_day}` : '';

    return `<div class="activity-block" id="act-${a.id}">
        <div class="activity-header">
            <i class="bi bi-arrow-right-circle" style="color:#3b82f6; font-size:0.85rem;"></i>
            <span style="font-size:0.83rem; font-weight:600; flex:1;">${esc(a.name)}</span>
            ${a.assigned_role ? `<span class="badge-role">${esc(a.assigned_role)}</span>` : ''}
            ${daysLabel ? `<span class="badge-days">${daysLabel}</span>` : ''}
            <span class="text-muted" style="font-size:0.75rem;">${Number(a.budgeted_hours || 0).toFixed(1)} hrs</span>
            <button class="btn btn-link btn-sm p-0 ms-2" onclick="showAddTaskModal(${a.id})" title="Add Task">
                <i class="bi bi-plus-circle text-success" style="font-size:0.8rem;"></i>
            </button>
            <button class="btn btn-link btn-sm p-0 ms-1" onclick="editActivity(${a.id}, ${JSON.stringify(a)})" title="Edit">
                <i class="bi bi-pencil text-muted" style="font-size:0.75rem;"></i>
            </button>
            <button class="btn btn-link btn-sm p-0 ms-1" onclick="deleteActivity(${a.id})" title="Delete">
                <i class="bi bi-trash text-danger" style="font-size:0.75rem;"></i>
            </button>
        </div>
        <div class="activity-tasks">
            ${tasksHtml}
        </div>
    </div>`;
}

function renderTask(t) {
    return `<div class="task-row" id="task-${t.id}">
        <i class="bi bi-check2-square text-muted" style="font-size:0.78rem;"></i>
        <span style="flex:1;">${esc(t.name)}</span>
        ${t.assigned_role ? `<span class="badge-role">${esc(t.assigned_role)}</span>` : ''}
        ${t.relative_due_day != null ? `<span class="badge-days">Day ${t.relative_due_day}</span>` : ''}
        <span class="text-muted" style="font-size:0.72rem;">${Number(t.estimated_hours || 0).toFixed(1)} hrs</span>
        <button class="btn btn-link btn-sm p-0" onclick="editTask(${t.id}, ${JSON.stringify(t)})" title="Edit">
            <i class="bi bi-pencil text-muted" style="font-size:0.72rem;"></i>
        </button>
        <button class="btn btn-link btn-sm p-0" onclick="deleteTask(${t.id})" title="Delete">
            <i class="bi bi-trash text-danger" style="font-size:0.72rem;"></i>
        </button>
    </div>`;
}

// ── Deliverable CRUD ──────────────────────────────────────────────────────────
function showAddDeliverableModal() {
    document.getElementById('editDeliverableId').value = '';
    document.getElementById('deliverableName').value = '';
    document.getElementById('deliverableDesc').value = '';
    document.getElementById('deliverableModalTitle').textContent = 'Add Deliverable';
    document.getElementById('deliverableError').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('addDeliverableModal')).show();
}

function editDeliverable(id, name, desc) {
    document.getElementById('editDeliverableId').value = id;
    document.getElementById('deliverableName').value = name;
    document.getElementById('deliverableDesc').value = desc;
    document.getElementById('deliverableModalTitle').textContent = 'Edit Deliverable';
    document.getElementById('deliverableError').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('addDeliverableModal')).show();
}

async function saveDeliverable() {
    const id   = document.getElementById('editDeliverableId').value;
    const name = document.getElementById('deliverableName').value.trim();
    const desc = document.getElementById('deliverableDesc').value.trim();
    const errEl = document.getElementById('deliverableError');

    if (!name) { errEl.textContent = 'Name is required.'; errEl.classList.remove('d-none'); return; }

    const url    = id ? `${BASE_URL}/deliverables/${id}` : `${BASE_URL}/deliverables`;
    const method = id ? 'PUT' : 'POST';

    const res = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }, body: JSON.stringify({ name, description: desc || null }) });

    if (res.ok) {
        bootstrap.Modal.getInstance(document.getElementById('addDeliverableModal')).hide();
        loadActivities();
    } else {
        const data = await res.json();
        errEl.textContent = data.message || 'Failed to save.';
        errEl.classList.remove('d-none');
    }
}

async function deleteDeliverable(id) {
    if (!confirm('Delete this deliverable and all its activities and tasks?')) return;
    await fetch(`${BASE_URL}/deliverables/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF_TOKEN } });
    loadActivities();
}

// ── Activity CRUD ─────────────────────────────────────────────────────────────
function showAddActivityModal(deliverableId) {
    document.getElementById('activityDeliverableId').value = deliverableId;
    document.getElementById('editActivityId').value = '';
    document.getElementById('activityName').value = '';
    document.getElementById('activityStartDay').value = '';
    document.getElementById('activityEndDay').value = '';
    document.getElementById('activityRole').value = '';
    document.getElementById('activityHours').value = '';
    document.getElementById('activityModalTitle').textContent = 'Add Activity';
    document.getElementById('activityError').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('addActivityModal')).show();
}

function editActivity(id, a) {
    document.getElementById('editActivityId').value = id;
    document.getElementById('activityDeliverableId').value = a.proposal_deliverable_id;
    document.getElementById('activityName').value = a.name;
    document.getElementById('activityStartDay').value = a.relative_start_day ?? '';
    document.getElementById('activityEndDay').value = a.relative_end_day ?? '';
    document.getElementById('activityRole').value = a.assigned_role ?? '';
    document.getElementById('activityHours').value = a.budgeted_hours ?? '';
    document.getElementById('activityModalTitle').textContent = 'Edit Activity';
    document.getElementById('activityError').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('addActivityModal')).show();
}

async function saveActivity() {
    const id           = document.getElementById('editActivityId').value;
    const delivId      = document.getElementById('activityDeliverableId').value;
    const errEl        = document.getElementById('activityError');
    const body = {
        name:               document.getElementById('activityName').value.trim(),
        relative_start_day: document.getElementById('activityStartDay').value || null,
        relative_end_day:   document.getElementById('activityEndDay').value || null,
        assigned_role:      document.getElementById('activityRole').value.trim() || null,
        budgeted_hours:     parseFloat(document.getElementById('activityHours').value) || 0,
    };

    if (!body.name) { errEl.textContent = 'Name is required.'; errEl.classList.remove('d-none'); return; }

    const url    = id ? `${BASE_URL}/activities/${id}` : `${BASE_URL}/deliverables/${delivId}/activities`;
    const method = id ? 'PUT' : 'POST';

    const res = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }, body: JSON.stringify(body) });

    if (res.ok) {
        bootstrap.Modal.getInstance(document.getElementById('addActivityModal')).hide();
        loadActivities();
    } else {
        const data = await res.json();
        errEl.textContent = data.message || 'Failed to save.';
        errEl.classList.remove('d-none');
    }
}

async function deleteActivity(id) {
    if (!confirm('Delete this activity and all its tasks?')) return;
    await fetch(`${BASE_URL}/activities/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF_TOKEN } });
    loadActivities();
}

// ── Task CRUD ─────────────────────────────────────────────────────────────────
function showAddTaskModal(activityId) {
    document.getElementById('taskActivityId').value = activityId;
    document.getElementById('editTaskId').value = '';
    document.getElementById('taskName').value = '';
    document.getElementById('taskDueDay').value = '';
    document.getElementById('taskHours').value = '';
    document.getElementById('taskRole').value = '';
    document.getElementById('taskModalTitle').textContent = 'Add Task';
    document.getElementById('taskError').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('addTaskModal')).show();
}

function editTask(id, t) {
    document.getElementById('editTaskId').value = id;
    document.getElementById('taskActivityId').value = t.proposal_activity_id;
    document.getElementById('taskName').value = t.name;
    document.getElementById('taskDueDay').value = t.relative_due_day ?? '';
    document.getElementById('taskHours').value = t.estimated_hours ?? '';
    document.getElementById('taskRole').value = t.assigned_role ?? '';
    document.getElementById('taskModalTitle').textContent = 'Edit Task';
    document.getElementById('taskError').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('addTaskModal')).show();
}

async function saveTask() {
    const id      = document.getElementById('editTaskId').value;
    const actId   = document.getElementById('taskActivityId').value;
    const errEl   = document.getElementById('taskError');
    const body = {
        name:             document.getElementById('taskName').value.trim(),
        relative_due_day: document.getElementById('taskDueDay').value || null,
        estimated_hours:  parseFloat(document.getElementById('taskHours').value) || 0,
        assigned_role:    document.getElementById('taskRole').value.trim() || null,
    };

    if (!body.name) { errEl.textContent = 'Name is required.'; errEl.classList.remove('d-none'); return; }

    const url    = id ? `${BASE_URL}/tasks/${id}` : `${BASE_URL}/activities/${actId}/tasks`;
    const method = id ? 'PUT' : 'POST';

    const res = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }, body: JSON.stringify(body) });

    if (res.ok) {
        bootstrap.Modal.getInstance(document.getElementById('addTaskModal')).hide();
        loadActivities();
    } else {
        const data = await res.json();
        errEl.textContent = data.message || 'Failed to save.';
        errEl.classList.remove('d-none');
    }
}

async function deleteTask(id) {
    if (!confirm('Delete this task?')) return;
    await fetch(`${BASE_URL}/tasks/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF_TOKEN } });
    loadActivities();
}

// ── Template Copy ─────────────────────────────────────────────────────────────
async function copyFromTemplate(templateId, templateName) {
    if (!confirm(`Copy all deliverables and activities from "${templateName}" into this proposal?`)) return;

    const res = await fetch(`${BASE_URL}/deliverables/copy-template`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ template_id: templateId }),
    });

    if (res.ok) {
        loadActivities();
    } else {
        alert('Failed to copy template.');
    }
}

// ── Similar Proposals (Dashboard sidebar) ─────────────────────────────────────
let _similarData = [];

async function loadSimilarProposals() {
    const container = document.getElementById('similarProposalsList');
    if (!container) return;
    container.innerHTML = '<div class="text-center py-3 text-muted" style="font-size:0.8rem;"><span class="spinner-border spinner-border-sm me-1"></span> Scanning…</div>';

    try {
        const res  = await fetch(`${BASE_URL}/similar`);
        const data = await res.json();
        _similarData = data.similar ?? [];

        if (_similarData.length === 0) {
            container.innerHTML = '<div class="text-muted py-2" style="font-size:0.78rem;">No similar prior proposals found.</div>';
            return;
        }

        container.innerHTML = _similarData.map(p => {
            const overlap = p.deliverable_overlap;
            const overlapPct = overlap?.similarity_pct ?? 0;
            const matchedCount = (overlap?.matched ?? []).length;
            const reasons = (p.match_reasons ?? []).map(r =>
                `<span class="sim-reason-chip">${esc(r.label)}</span>`
            ).join('');
            const dupeNote = matchedCount > 0
                ? `<span class="text-warning" style="font-size:0.68rem;">⚠ ${matchedCount} overlapping deliverable${matchedCount > 1 ? 's' : ''}</span>`
                : '';

            return `<div class="similar-proposal-row" onclick="importFromSimilar(${p.id})" title="Click to import deliverables from this proposal">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span style="font-size:0.78rem; font-weight:600; color:#111827;">${esc(p.ref)}</span>
                        <span style="font-size:0.72rem; color:#6b7280; margin-left:6px;">${esc(p.title)}</span>
                    </div>
                    <span style="font-size:0.68rem; color:#6b7280;">${p.deliverable_count} deliverable${p.deliverable_count !== 1 ? 's' : ''}</span>
                </div>
                <div style="font-size:0.7rem; color:#9ca3af;">
                    ${p.company ? esc(p.company) + ' &mdash; ' : ''}${p.status ?? ''}
                    ${p.contract_value ? ' &mdash; $' + Number(p.contract_value).toLocaleString('en-US', {maximumFractionDigits:0}) : ''}
                </div>
                <div class="mt-1">${reasons} ${dupeNote}</div>
                <div class="mt-1" style="background:#f3f4f6; border-radius:2px; height:4px; width:100%;">
                    <div class="sim-score-bar" style="width:${Math.min(p.score, 100)}%;"></div>
                </div>
            </div>`;
        }).join('');
    } catch (e) {
        container.innerHTML = '<div class="text-muted py-2" style="font-size:0.78rem;">Could not load similar proposals.</div>';
    }
}

// ── Import from Prior Proposal (Activities tab) ───────────────────────────────
let _allImportProposals = [];
let _selectedSourceId   = null;

async function openImportFromProposalModal() {
    _selectedSourceId = null;
    document.getElementById('importStep1').classList.remove('d-none');
    document.getElementById('importStep2').classList.add('d-none');
    document.getElementById('importConfirmBtn').classList.add('d-none');
    document.getElementById('importProposalSearch').value = '';

    const modal = new bootstrap.Modal(document.getElementById('importFromProposalModal'));
    modal.show();

    if (_allImportProposals.length === 0) {
        // Load similar first, then fall back to all proposals
        try {
            if (_similarData.length > 0) {
                _allImportProposals = _similarData;
            } else {
                const res  = await fetch(`${BASE_URL}/similar`);
                const data = await res.json();
                _allImportProposals = data.similar ?? [];
            }
        } catch { _allImportProposals = []; }
    }

    document.getElementById('importLoadingSpinner').style.display = 'none';
    renderImportProposalList(_allImportProposals);
}

function filterImportProposals() {
    const q = document.getElementById('importProposalSearch').value.toLowerCase();
    const filtered = _allImportProposals.filter(p =>
        (p.ref ?? '').toLowerCase().includes(q) ||
        (p.title ?? '').toLowerCase().includes(q) ||
        (p.company ?? '').toLowerCase().includes(q)
    );
    renderImportProposalList(filtered);
}

function renderImportProposalList(list) {
    const el = document.getElementById('importProposalsList');
    if (list.length === 0) {
        el.innerHTML = '<div class="text-muted py-3 text-center" style="font-size:0.82rem;">No proposals found. Try a different search term or go directly to another proposal to import from there.</div>';
        return;
    }
    el.innerHTML = list.map(p => {
        const matched = (p.deliverable_overlap?.matched ?? []).length;
        const dupeWarning = matched > 0 ? `<span class="text-warning ms-2" style="font-size:0.68rem;">⚠ ${matched} duplicate${matched > 1 ? 's' : ''}</span>` : '';
        return `<div class="d-flex align-items-center gap-3 py-2 px-2 border-bottom" style="cursor:pointer;" onclick="loadImportStep2(${p.id}, '${esc(p.ref)} — ${esc(p.title)}')">
            <div class="flex-grow-1">
                <div style="font-size:0.82rem; font-weight:600;">${esc(p.ref)} <span style="font-weight:400; color:#6b7280;">${esc(p.title)}</span></div>
                <div style="font-size:0.72rem; color:#9ca3af;">${p.company ? esc(p.company) + ' — ' : ''}${p.status ?? ''} &nbsp;|&nbsp; ${p.deliverable_count ?? 0} deliverables${dupeWarning}</div>
            </div>
            <i class="bi bi-chevron-right text-muted" style="font-size:0.75rem;"></i>
        </div>`;
    }).join('');
}

async function importFromSimilar(proposalId) {
    // Called from the dashboard similar card — opens the modal pre-loaded to step 2
    openImportFromProposalModal();
    // Give modal time to render
    setTimeout(() => {
        const p = _similarData.find(x => x.id === proposalId);
        if (p) loadImportStep2(p.id, `${p.ref} — ${p.title}`);
    }, 350);
}

async function loadImportStep2(sourceId, label) {
    _selectedSourceId = sourceId;
    document.getElementById('importStep2Title').textContent = label;
    document.getElementById('importStep1').classList.add('d-none');
    document.getElementById('importStep2').classList.remove('d-none');
    document.getElementById('importConfirmBtn').classList.remove('d-none');

    const listEl    = document.getElementById('importDeliverableList');
    const dupeWarn  = document.getElementById('importDupeWarning');
    const msgEl     = document.getElementById('importMsg');
    msgEl.classList.add('d-none');
    listEl.innerHTML = '<div class="text-center py-3"><span class="spinner-border spinner-border-sm"></span> Loading deliverables…</div>';

    try {
        const res  = await fetch(`${BASE_URL}/deliverables/prior/${sourceId}`);
        const data = await res.json();

        const dupeCount = (data.duplicate_names ?? []).length;
        const newCount  = (data.new_names ?? []).length;

        dupeWarn.innerHTML = dupeCount > 0
            ? `<span class="text-warning">⚠ ${dupeCount} deliverable${dupeCount > 1 ? 's' : ''} already exist in this proposal (shown in yellow)</span>`
            : `<span class="text-success">No duplicates detected</span>`;

        if (!data.deliverables || data.deliverables.length === 0) {
            listEl.innerHTML = '<div class="text-muted py-3 text-center" style="font-size:0.82rem;">This proposal has no deliverables.</div>';
            return;
        }

        listEl.innerHTML = data.deliverables.map(d => {
            const isDupe = d.is_duplicate;
            const actCount = (d.activities ?? []).length;
            const totalHours = (d.activities ?? []).reduce((sum, a) => sum + (a.budgeted_hours ?? 0), 0);
            return `<div class="import-deliverable-row${isDupe ? ' is-duplicate' : ''}">
                <div class="d-flex align-items-center gap-2">
                    <input type="checkbox" class="import-deliverable-cb" value="${d.id}"
                        id="idcb_${d.id}" ${isDupe ? '' : 'checked'}>
                    <label for="idcb_${d.id}" style="font-size:0.82rem; font-weight:600; cursor:pointer; margin:0;">
                        ${esc(d.name)}
                        <span class="dupe-badge">Already exists</span>
                    </label>
                </div>
                <div style="font-size:0.72rem; color:#9ca3af; margin-top:2px; padding-left:22px;">
                    ${actCount} activit${actCount === 1 ? 'y' : 'ies'}
                    ${totalHours > 0 ? ' &nbsp;·&nbsp; ' + totalHours + ' hrs' : ''}
                    ${d.description ? ' &nbsp;·&nbsp; ' + esc(d.description.substring(0, 60)) + (d.description.length > 60 ? '…' : '') : ''}
                </div>
            </div>`;
        }).join('');
    } catch (e) {
        listEl.innerHTML = '<div class="text-danger py-2" style="font-size:0.82rem;">Failed to load deliverables.</div>';
    }
}

function importGoBack() {
    _selectedSourceId = null;
    document.getElementById('importStep1').classList.remove('d-none');
    document.getElementById('importStep2').classList.add('d-none');
    document.getElementById('importConfirmBtn').classList.add('d-none');
}

function importSelectAll() {
    document.querySelectorAll('.import-deliverable-cb').forEach(cb => cb.checked = true);
}

function importSelectNone() {
    document.querySelectorAll('.import-deliverable-cb').forEach(cb => cb.checked = false);
}

async function doImportFromProposal() {
    if (!_selectedSourceId) return;

    const selected = [...document.querySelectorAll('.import-deliverable-cb:checked')].map(cb => Number(cb.value));
    if (selected.length === 0) {
        alert('Please select at least one deliverable to import.');
        return;
    }

    const btn = document.getElementById('importConfirmBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Importing…';

    try {
        const res  = await fetch(`${BASE_URL}/deliverables/copy-from-proposal`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({
                source_proposal_id: _selectedSourceId,
                deliverable_ids:    selected,
                skip_duplicates:    true,
            }),
        });
        const data = await res.json();

        const msgEl = document.getElementById('importMsg');
        msgEl.textContent = data.message;
        msgEl.classList.remove('d-none', 'alert-danger');
        msgEl.classList.add('alert-success');

        // Refresh activities tab and similar proposals
        loadActivities();
        _allImportProposals = [];
        loadSimilarProposals();

        setTimeout(() => {
            bootstrap.Modal.getInstance(document.getElementById('importFromProposalModal'))?.hide();
            // Switch to activities tab
            document.querySelector('[data-bs-target="#tab-activities"]')?.click();
        }, 1200);
    } catch (e) {
        const msgEl = document.getElementById('importMsg');
        msgEl.textContent = 'Import failed. Please try again.';
        msgEl.classList.remove('d-none', 'alert-success');
        msgEl.classList.add('alert-danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-box-arrow-in-down me-1"></i> Import Selected';
    }
}

// ── Utilities ─────────────────────────────────────────────────────────────────
function fmtMoney(v) { return Number(v ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2 }); }
function esc(str) { return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
</script>
@endpush

@endsection
