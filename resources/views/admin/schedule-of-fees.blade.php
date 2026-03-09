@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0 fw-700" style="font-size:1rem;">Schedule of Fees</h4>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

<div class="kore-card">
    <p style="font-size:0.82rem; color:#6b7280; margin-bottom:20px;">
        Define standard billing rates used when generating invoices and proposals.
    </p>

    <form action="{{ route('admin.schedule-of-fees.save') }}" method="POST">
        @csrf
        <div id="feesContainer">
            <div class="row g-2 mb-2 fee-row">
                <div class="col-sm-5">
                    <input type="text" name="fees[0][role]" class="form-control form-control-sm"
                        placeholder="Role / service (e.g. Principal, Design)">
                </div>
                <div class="col-sm-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">$</span>
                        <input type="number" name="fees[0][rate]" class="form-control"
                            placeholder="Hourly rate" step="0.01" min="0">
                        <span class="input-group-text">/hr</span>
                    </div>
                </div>
                <div class="col-sm-auto">
                    <button type="button" class="btn btn-sm btn-light text-danger" onclick="removeFeeRow(this)">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary mb-3" onclick="addFeeRow()">
            <i class="bi bi-plus-sm me-1"></i> Add Rate
        </button>
        <div>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-check-lg me-1"></i> Save Fees
            </button>
        </div>
    </form>
</div>

@endsection
@push('scripts')
<script>
let feeIdx = 1;
function addFeeRow() {
    const c = document.getElementById('feesContainer');
    c.insertAdjacentHTML('beforeend', `<div class="row g-2 mb-2 fee-row">
        <div class="col-sm-5"><input type="text" name="fees[${feeIdx}][role]" class="form-control form-control-sm" placeholder="Role / service"></div>
        <div class="col-sm-3"><div class="input-group input-group-sm"><span class="input-group-text">$</span><input type="number" name="fees[${feeIdx}][rate]" class="form-control" placeholder="Hourly rate" step="0.01" min="0"><span class="input-group-text">/hr</span></div></div>
        <div class="col-sm-auto"><button type="button" class="btn btn-sm btn-light text-danger" onclick="removeFeeRow(this)"><i class="bi bi-x"></i></button></div>
    </div>`);
    feeIdx++;
}
function removeFeeRow(btn) {
    const rows = document.querySelectorAll('.fee-row');
    if (rows.length > 1) btn.closest('.fee-row').remove();
}
</script>
@endpush
