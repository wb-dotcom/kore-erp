@extends('layouts.app')
@section('title', 'New Fee Schedule')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.fee-schedules.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">New Fee Schedule</h4>
        <div style="font-size:0.72rem; color:#6b7280;">Create a named rate table for proposals</div>
    </div>
</div>

<div class="kore-card" style="max-width:600px;">
    <form action="{{ route('admin.fee-schedules.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label" style="font-size:0.82rem; font-weight:600;">Schedule Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror"
                value="{{ old('name') }}" placeholder="e.g. Standard 2024, Government Rate" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label" style="font-size:0.82rem; font-weight:600;">Description</label>
            <textarea name="description" class="form-control form-control-sm" rows="2"
                placeholder="Optional description of when this schedule applies...">{{ old('description') }}</textarea>
        </div>
        <div class="d-flex gap-4 mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                    {{ old('is_active', '1') ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active" style="font-size:0.82rem;">Active</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="is_default"
                    {{ old('is_default') ? 'checked' : '' }}>
                <label class="form-check-label" for="is_default" style="font-size:0.82rem;">
                    Default schedule
                    <small class="text-muted">(auto-selected on new proposals)</small>
                </label>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-check-lg me-1"></i> Create & Add Rates
            </button>
            <a href="{{ route('admin.fee-schedules.index') }}" class="btn btn-secondary btn-sm">Cancel</a>
        </div>
    </form>
</div>

@endsection
