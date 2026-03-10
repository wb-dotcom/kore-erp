@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('proposals.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div class="flex-grow-1">
        <h4 class="mb-0 fw-700" style="font-size:1rem;">{{ $proposal->ref }} &mdash; {{ $proposal->title }}</h4>
        <div style="font-size:0.72rem; color:#6b7280;">
            Created {{ $proposal->created_at?->format('M d, Y') }}
            @if($proposal->createdBy) by {{ $proposal->createdBy->full_name }} @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('proposals.edit', $proposal) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="exportDocsBtn"
            title="Export to Google Docs">
            <i class="bi bi-google me-1"></i> Google Docs
        </button>
        @if(!$proposal->project)
        <a href="{{ route('projects.create') }}?proposal_id={{ $proposal->id }}" class="btn btn-sm btn-success">
            <i class="bi bi-folder-plus me-1"></i> Convert to Project
        </a>
        @endif
    </div>
</div>

<div class="row g-4">

    {{-- Left: Main Details --}}
    <div class="col-lg-8">

        @if($proposal->executive_summary)
        {{-- Executive Summary --}}
        <div class="kore-card mb-4">
            <div class="kore-card-header">
                <h5><i class="bi bi-file-earmark-text me-2"></i>Executive Summary</h5>
            </div>
            <div style="font-size:0.85rem; white-space:pre-line; line-height:1.6;">{{ $proposal->executive_summary }}</div>
        </div>
        @endif

        {{-- Details Card --}}
        <div class="kore-card mb-4">
            <div class="kore-card-header">
                <h5><i class="bi bi-info-circle me-2"></i>Proposal Details</h5>
                @php
                    $cls = match($proposal->status?->name) {
                        'Approved'  => 'approved',
                        'Submitted' => 'active',
                        'Rejected'  => 'rejected',
                        default     => 'draft',
                    };
                @endphp
                <span class="badge badge-{{ $cls }} fs-6">{{ $proposal->status?->name ?? '—' }}</span>
            </div>

            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Client</div>
                    <div style="font-size:0.85rem;">{{ $proposal->company?->name ?? '—' }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">PO Number</div>
                    <div style="font-size:0.85rem;">{{ $proposal->po_number ?? '—' }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Sector</div>
                    <div style="font-size:0.85rem;">{{ $proposal->sector?->name ?? '—' }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Work Type</div>
                    <div style="font-size:0.85rem;">{{ $proposal->workType?->name ?? '—' }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Account Manager</div>
                    <div style="font-size:0.85rem;">{{ $proposal->accountManager?->full_name ?? '—' }}</div>
                </div>
                <div class="col-sm-3">
                    <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Submitted</div>
                    <div style="font-size:0.85rem;">{{ $proposal->submitted_date?->format('M d, Y') ?? '—' }}</div>
                </div>
                <div class="col-sm-3">
                    <div class="text-muted" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Approved</div>
                    <div style="font-size:0.85rem;">{{ $proposal->approved_date?->format('M d, Y') ?? '—' }}</div>
                </div>
            </div>

            @if($proposal->description)
            <hr>
            <div class="text-muted mb-1" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Description</div>
            <div style="font-size:0.85rem; white-space:pre-line;">{{ $proposal->description }}</div>
            @endif

            @if($proposal->notes)
            <hr>
            <div class="text-muted mb-1" style="font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Internal Notes</div>
            <div class="kore-alert kore-alert-info mb-0" style="font-size:0.8rem;">
                {{ $proposal->notes }}
            </div>
            @endif
        </div>

        @if($proposal->scope_of_work)
        {{-- Scope of Work --}}
        <div class="kore-card mb-4">
            <div class="kore-card-header">
                <h5><i class="bi bi-list-check me-2"></i>Scope of Work</h5>
            </div>
            <div style="font-size:0.85rem; white-space:pre-line; line-height:1.6;">{{ $proposal->scope_of_work }}</div>
        </div>
        @endif

        {{-- Fee Worksheet --}}
        <div class="kore-card mb-4">
            <div class="kore-card-header">
                <h5><i class="bi bi-table me-2"></i>Fee Worksheet</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addLineItemModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Line Item
                </button>
            </div>

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

        @if($proposal->terms_and_conditions)
        {{-- Terms & Conditions --}}
        <div class="kore-card mb-4">
            <div class="kore-card-header">
                <h5><i class="bi bi-file-lock me-2"></i>Terms &amp; Conditions</h5>
            </div>
            <div style="font-size:0.85rem; white-space:pre-line; line-height:1.6; color:#4b5563;">{{ $proposal->terms_and_conditions }}</div>
        </div>
        @endif

    </div>

    {{-- Right: Sidebar --}}
    <div class="col-lg-4">

        {{-- Status / Actions --}}
        <div class="kore-card mb-3">
            <div class="kore-card-header"><h5>Actions</h5></div>
            <a href="{{ route('proposals.edit', $proposal) }}" class="btn btn-outline-primary btn-sm w-100 mb-2">
                <i class="bi bi-pencil me-1"></i> Edit Proposal
            </a>
            @if(!$proposal->project)
            <a href="{{ route('projects.create') }}?proposal_id={{ $proposal->id }}" class="btn btn-success btn-sm w-100 mb-2">
                <i class="bi bi-folder-plus me-1"></i> Convert to Project
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

        {{-- Associated Project --}}
        @if($proposal->project)
        <div class="kore-card mb-3">
            <div class="kore-card-header"><h5><i class="bi bi-folder2-open me-1"></i>Associated Project</h5></div>
            <a href="{{ route('projects.show', $proposal->project) }}" class="text-decoration-none d-flex align-items-center gap-2">
                <i class="bi bi-folder2-open text-primary"></i>
                <span style="font-size:0.82rem;">{{ $proposal->project->project_number }} — {{ $proposal->project->title }}</span>
            </a>
        </div>
        @endif

        {{-- Billing Info --}}
        @if($proposal->billing_type)
        <div class="kore-card mb-3">
            <div class="kore-card-header"><h5><i class="bi bi-receipt me-1"></i>Billing</h5></div>
            @foreach([
                ['Type',          ucwords(str_replace('_', ' ', $proposal->billing_type ?? '—'))],
                ['Cycle',         ucwords(str_replace('_', ' ', $proposal->billing_cycle ?? '—'))],
                ['Payment Terms', ($proposal->payment_terms_days ? $proposal->payment_terms_days . ' days' : '—')],
                ['Total Fee',     '$' . number_format($proposal->total_fee ?? 0, 2)],
            ] as [$label, $value])
            <div class="d-flex justify-content-between py-1 border-bottom">
                <span style="font-size:0.75rem; color:#6b7280;">{{ $label }}</span>
                <span style="font-size:0.75rem; font-weight:500;">{{ $value }}</span>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Rate Overrides --}}
        <div class="kore-card mb-3">
            <div class="kore-card-header">
                <h5><i class="bi bi-currency-dollar me-1"></i>Rate Overrides</h5>
                <button type="button" class="btn btn-sm btn-outline-secondary"
                    onclick="document.getElementById('addRateOverrideForm').classList.toggle('d-none')"
                    style="font-size:0.7rem; padding:2px 8px;">
                    <i class="bi bi-plus-sm"></i>
                </button>
            </div>

            {{-- Add rate override form (hidden by default) --}}
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
                            <input type="number" name="hourly_rate" class="form-control" step="0.01" min="0"
                                placeholder="Rate" required>
                        </div>
                    </div>
                    <div class="col-1 d-flex align-items-center">
                        <button type="submit" class="btn btn-primary btn-sm px-2"><i class="bi bi-check-lg"></i></button>
                    </div>
                </div>
                <input type="hidden" name="scope" value="role">
            </form>

            @forelse($proposal->rateSchedules as $rs)
            <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                <span style="font-size:0.75rem;">{{ $rs->scope_value }}</span>
                <div class="d-flex align-items-center gap-2">
                    <span style="font-size:0.75rem; font-weight:600; color:#374151;">${{ number_format($rs->hourly_rate, 0) }}/hr</span>
                    <form action="{{ route('proposals.rate-schedules.destroy', [$proposal, $rs]) }}" method="POST"
                        onsubmit="return confirm('Remove this rate override?')" class="d-inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-link btn-sm p-0 text-danger" title="Remove">
                            <i class="bi bi-x-lg" style="font-size:0.7rem;"></i>
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div style="font-size:0.75rem; color:#9ca3af;">No rate overrides. Global schedule of fees applies.</div>
            @endforelse
        </div>

        {{-- Meta --}}
        <div class="kore-card">
            <div class="kore-card-header"><h5>Info</h5></div>
            @foreach([
                ['Reference',     $proposal->ref],
                ['Created by',    $proposal->createdBy?->full_name ?? '—'],
                ['Created',       $proposal->created_at?->format('M d, Y H:i')],
                ['Last updated',  $proposal->updated_at?->format('M d, Y H:i')],
            ] as [$label, $value])
            <div class="d-flex justify-content-between py-1 border-bottom">
                <span style="font-size:0.75rem; color:#6b7280;">{{ $label }}</span>
                <span style="font-size:0.75rem; font-weight:500;">{{ $value }}</span>
            </div>
            @endforeach
        </div>

    </div>
</div>

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

@push('scripts')
<script>
const PROPOSAL_ID    = {{ $proposal->id }};
const LINE_ITEMS_URL = '/proposals/' + PROPOSAL_ID + '/line-items';
const CSRF_TOKEN     = '{{ csrf_token() }}';

// ── Load fee worksheet on page load ──────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadWorksheet();

    // Google Docs export
    document.getElementById('exportDocsBtn')?.addEventListener('click', exportToDocs);

    // Rate auto-fill when role changes
    document.getElementById('li_role')?.addEventListener('change', resolveRateForRole);

    // Save line item
    document.getElementById('saveLineItemBtn')?.addEventListener('click', saveLineItem);
});

async function loadWorksheet() {
    try {
        const res  = await fetch(LINE_ITEMS_URL);
        const data = await res.json();

        document.getElementById('feeLoadingMsg').classList.add('d-none');
        document.getElementById('feeWorksheetBody').classList.remove('d-none');
        renderWorksheet(data);
    } catch (e) {
        document.getElementById('feeLoadingMsg').textContent = 'Failed to load worksheet.';
    }
}

function renderWorksheet(data) {
    const container = document.getElementById('feeWorksheetBody');
    const total     = data.total_fee ?? 0;
    document.getElementById('totalFeeDisplay').textContent = '$' + Number(total).toLocaleString('en-US', {minimumFractionDigits: 2});

    if (!data.items || data.items.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-3" style="font-size:0.82rem;">No line items yet. Add items to build the fee worksheet.</p>';
        return;
    }

    let html = '<table class="table table-sm" style="font-size:0.78rem;">';
    html += '<thead class="table-light"><tr><th>Phase</th><th>Deliverable</th><th>Role</th><th class="text-end">Hrs</th><th class="text-end">Rate</th><th class="text-end">Amount</th><th></th></tr></thead><tbody>';

    let lastPhase = '';
    data.items.forEach(item => {
        const phase  = item.phase_label || item.phase_code;
        const amount = item.amount_override ?? item.amount;
        html += `<tr>
            <td class="text-muted">${phase !== lastPhase ? escHtml(phase) : ''}</td>
            <td>${escHtml(item.deliverable)}</td>
            <td>${escHtml(item.role_name)}</td>
            <td class="text-end">${Number(item.hours).toFixed(1)}</td>
            <td class="text-end">$${Number(item.rate).toFixed(0)}</td>
            <td class="text-end fw-600">$${Number(amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
            <td class="text-end">
                <button class="btn btn-link btn-sm p-0 text-danger" onclick="deleteLineItem(${item.id})" title="Remove">
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
        const res  = await fetch(LINE_ITEMS_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify(body),
        });
        const data = await res.json();

        if (!res.ok) {
            errEl.textContent = data.message || 'Failed to save line item.';
            errEl.classList.remove('d-none');
            return;
        }

        bootstrap.Modal.getInstance(document.getElementById('addLineItemModal')).hide();
        loadWorksheet();
    } catch (e) {
        errEl.textContent = 'Network error — could not save.';
        errEl.classList.remove('d-none');
    }
}

async function deleteLineItem(id) {
    if (!confirm('Remove this line item?')) return;

    await fetch(LINE_ITEMS_URL + '/' + id, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
    });

    loadWorksheet();
}

async function exportToDocs() {
    const btn = document.getElementById('exportDocsBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Exporting…';

    try {
        const res  = await fetch(LINE_ITEMS_URL + '/export-docs', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
        });
        const data = await res.json();

        if (data.url) {
            window.open(data.url, '_blank');
        } else {
            alert('Export failed: ' + (data.error || 'Unknown error'));
        }
    } catch (e) {
        alert('Export failed: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-google me-1"></i> Google Docs';
    }
}

function escHtml(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
@endpush

@endsection
