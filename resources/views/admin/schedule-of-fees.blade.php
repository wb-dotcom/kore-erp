@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">Schedule of Fees</h4>
        <div style="font-size:0.72rem; color:#6b7280;">Standard billing rates used in proposals and invoicing</div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

<div class="kore-card">
    <p style="font-size:0.82rem; color:#6b7280; margin-bottom:20px;">
        Define standard hourly billing rates per role. These rates are used automatically when building proposal fee worksheets.
        Rates with an <strong>Effective Date</strong> apply from that date onward (most recent takes precedence).
    </p>

    <form action="{{ route('admin.schedule-of-fees.save') }}" method="POST">
        @csrf

        <div class="row g-2 mb-2" style="font-size:0.72rem; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px;">
            <div class="col-sm-5">Role / Service</div>
            <div class="col-sm-3">Hourly Rate</div>
            <div class="col-sm-3">Effective Date</div>
            <div class="col-sm-1"></div>
        </div>

        <div id="feesContainer">
            @forelse($fees as $i => $fee)
            <div class="row g-2 mb-2 fee-row align-items-center">
                <input type="hidden" name="fees[{{ $i }}][id]" value="{{ $fee->id }}">
                <div class="col-sm-5">
                    <input type="text" name="fees[{{ $i }}][role_name]" class="form-control form-control-sm"
                        value="{{ $fee->role_name }}" placeholder="e.g. Principal, Senior Architect" required>
                </div>
                <div class="col-sm-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">$</span>
                        <input type="number" name="fees[{{ $i }}][hourly_rate]" class="form-control"
                            value="{{ $fee->hourly_rate }}" placeholder="0.00" step="0.01" min="0" required>
                        <span class="input-group-text">/hr</span>
                    </div>
                </div>
                <div class="col-sm-3">
                    <input type="date" name="fees[{{ $i }}][effective_date]" class="form-control form-control-sm"
                        value="{{ $fee->effective_date?->format('Y-m-d') }}">
                </div>
                <div class="col-sm-1">
                    <button type="button" class="btn btn-sm btn-light text-danger" onclick="removeFeeRow(this)" title="Remove">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
            @empty
            {{-- Default empty row if no fees exist yet --}}
            <div class="row g-2 mb-2 fee-row align-items-center">
                <div class="col-sm-5">
                    <input type="text" name="fees[0][role_name]" class="form-control form-control-sm"
                        placeholder="e.g. Principal">
                </div>
                <div class="col-sm-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">$</span>
                        <input type="number" name="fees[0][hourly_rate]" class="form-control"
                            placeholder="0.00" step="0.01" min="0">
                        <span class="input-group-text">/hr</span>
                    </div>
                </div>
                <div class="col-sm-3">
                    <input type="date" name="fees[0][effective_date]" class="form-control form-control-sm">
                </div>
                <div class="col-sm-1">
                    <button type="button" class="btn btn-sm btn-light text-danger" onclick="removeFeeRow(this)">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
            @endforelse
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addFeeRow()">
                <i class="bi bi-plus-sm me-1"></i> Add Rate
            </button>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-check-lg me-1"></i> Save All Rates
            </button>
        </div>
    </form>

    @if($fees->count() > 0)
    <hr class="mt-4">
    <div style="font-size:0.75rem; color:#6b7280;">
        <strong>{{ $fees->count() }}</strong> rate{{ $fees->count() === 1 ? '' : 's' }} configured.
        These rates are available in the Proposal Fee Worksheet for automatic rate resolution.
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
let feeIdx = {{ max($fees->count(), 1) }};

function addFeeRow() {
    const c = document.getElementById('feesContainer');
    c.insertAdjacentHTML('beforeend', `
        <div class="row g-2 mb-2 fee-row align-items-center">
            <div class="col-sm-5">
                <input type="text" name="fees[${feeIdx}][role_name]" class="form-control form-control-sm"
                    placeholder="e.g. Principal">
            </div>
            <div class="col-sm-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text">$</span>
                    <input type="number" name="fees[${feeIdx}][hourly_rate]" class="form-control"
                        placeholder="0.00" step="0.01" min="0">
                    <span class="input-group-text">/hr</span>
                </div>
            </div>
            <div class="col-sm-3">
                <input type="date" name="fees[${feeIdx}][effective_date]" class="form-control form-control-sm">
            </div>
            <div class="col-sm-1">
                <button type="button" class="btn btn-sm btn-light text-danger" onclick="removeFeeRow(this)">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </div>
    `);
    feeIdx++;
}

function removeFeeRow(btn) {
    const rows = document.querySelectorAll('.fee-row');
    if (rows.length > 1) {
        btn.closest('.fee-row').remove();
    }
}
</script>
@endpush
