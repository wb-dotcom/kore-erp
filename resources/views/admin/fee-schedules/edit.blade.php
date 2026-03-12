@extends('layouts.app')
@section('title', 'Edit Fee Schedule')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.fee-schedules.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">{{ $feeSchedule->name }}</h4>
        <div style="font-size:0.72rem; color:#6b7280;">Edit fee schedule &amp; rates</div>
    </div>
    @if($feeSchedule->is_default)
    <span class="badge bg-primary ms-1">Default</span>
    @endif
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

<form action="{{ route('admin.fee-schedules.update', $feeSchedule) }}" method="POST">
    @csrf @method('PUT')

    <div class="row g-4">

        {{-- Left: Schedule details --}}
        <div class="col-lg-4">
            <div class="kore-card">
                <div class="fw-600 mb-3" style="font-size:0.82rem; color:#6b7280; text-transform:uppercase; letter-spacing:.04em;">
                    Schedule Info
                </div>

                <div class="mb-3">
                    <label class="form-label" style="font-size:0.82rem; font-weight:600;">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control form-control-sm"
                        value="{{ old('name', $feeSchedule->name) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="font-size:0.82rem; font-weight:600;">Description</label>
                    <textarea name="description" class="form-control form-control-sm" rows="3">{{ old('description', $feeSchedule->description) }}</textarea>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                        {{ old('is_active', $feeSchedule->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active" style="font-size:0.82rem;">Active</label>
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="is_default" value="1" id="is_default"
                        {{ old('is_default', $feeSchedule->is_default) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_default" style="font-size:0.82rem;">
                        Default for new proposals
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-check-lg me-1"></i> Save All Changes
                </button>
            </div>
        </div>

        {{-- Right: Rates table --}}
        <div class="col-lg-8">
            <div class="kore-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="fw-600" style="font-size:0.85rem;">Role Rates</div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addRateRow()">
                        <i class="bi bi-plus-sm me-1"></i> Add Role
                    </button>
                </div>

                <div class="row g-2 mb-2" style="font-size:0.7rem; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.4px;">
                    <div class="col-7">Role / Function</div>
                    <div class="col-4">Hourly Rate</div>
                    <div class="col-1"></div>
                </div>

                <div id="ratesContainer">
                    @forelse($rates as $i => $rate)
                    <div class="row g-2 mb-2 rate-row align-items-center">
                        <input type="hidden" name="rates[{{ $i }}][id]" value="{{ $rate->id }}">
                        <div class="col-7">
                            <input type="text" name="rates[{{ $i }}][role_name]" class="form-control form-control-sm"
                                value="{{ $rate->role_name }}" placeholder="e.g. Principal, Senior Architect" required>
                        </div>
                        <div class="col-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">$</span>
                                <input type="number" name="rates[{{ $i }}][hourly_rate]" class="form-control"
                                    value="{{ $rate->hourly_rate }}" placeholder="0.00" step="0.01" min="0" required>
                                <span class="input-group-text">/hr</span>
                            </div>
                        </div>
                        <div class="col-1">
                            <button type="button" class="btn btn-sm btn-light text-danger" onclick="removeRow(this)">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="row g-2 mb-2 rate-row align-items-center">
                        <div class="col-7">
                            <input type="text" name="rates[0][role_name]" class="form-control form-control-sm"
                                placeholder="e.g. Principal">
                        </div>
                        <div class="col-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">$</span>
                                <input type="number" name="rates[0][hourly_rate]" class="form-control"
                                    placeholder="0.00" step="0.01" min="0">
                                <span class="input-group-text">/hr</span>
                            </div>
                        </div>
                        <div class="col-1">
                            <button type="button" class="btn btn-sm btn-light text-danger" onclick="removeRow(this)">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                    @endforelse
                </div>

                <div class="mt-3 pt-2 border-top" style="font-size:0.75rem; color:#6b7280;">
                    <i class="bi bi-info-circle me-1"></i>
                    These rates are available in proposals that use this schedule. When a proposal uses hourly billing, select a role here to auto-populate the rate.
                </div>
            </div>
        </div>

    </div>
</form>

@endsection

@push('scripts')
<script>
let rateIdx = {{ max($rates->count(), 1) }};

function addRateRow() {
    const c = document.getElementById('ratesContainer');
    c.insertAdjacentHTML('beforeend', `
        <div class="row g-2 mb-2 rate-row align-items-center">
            <div class="col-7">
                <input type="text" name="rates[${rateIdx}][role_name]" class="form-control form-control-sm"
                    placeholder="e.g. Principal">
            </div>
            <div class="col-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text">$</span>
                    <input type="number" name="rates[${rateIdx}][hourly_rate]" class="form-control"
                        placeholder="0.00" step="0.01" min="0">
                    <span class="input-group-text">/hr</span>
                </div>
            </div>
            <div class="col-1">
                <button type="button" class="btn btn-sm btn-light text-danger" onclick="removeRow(this)">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </div>
    `);
    rateIdx++;
}

function removeRow(btn) {
    const rows = document.querySelectorAll('.rate-row');
    if (rows.length > 1) btn.closest('.rate-row').remove();
}
</script>
@endpush
