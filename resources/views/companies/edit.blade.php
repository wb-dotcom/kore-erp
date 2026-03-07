@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('companies.show', $company) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0 fw-700" style="font-size:1rem;">Edit — {{ $company->name }}</h4>
</div>

@if($errors->any())
<div class="alert alert-danger py-2" style="font-size:0.8rem;">
    <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form action="{{ route('companies.update', $company) }}" method="POST">
    @csrf @method('PUT')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="kore-card mb-4">
                <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">
                    Company Details
                </div>
                <div class="row g-3">
                    <div class="col-sm-8">
                        <label class="form-label">Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm"
                            value="{{ old('name', $company->name) }}" required>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Active</label>
                        <div class="form-check mt-1">
                            <input type="checkbox" name="is_active" id="isActive" class="form-check-input"
                                value="1" {{ old('is_active', $company->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive" style="font-size:0.8rem;">Active</label>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Sector</label>
                        <select name="sector_id" class="form-select form-select-sm">
                            <option value="">— None —</option>
                            @foreach($sectors as $s)
                            <option value="{{ $s->id }}" {{ old('sector_id', $company->sector_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Region</label>
                        <select name="region_id" class="form-select form-select-sm">
                            <option value="">— None —</option>
                            @foreach($regions as $r)
                            <option value="{{ $r->id }}" {{ old('region_id', $company->region_id) == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control form-control-sm"
                            value="{{ old('phone', $company->phone) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Website</label>
                        <input type="url" name="website" class="form-control form-control-sm"
                            value="{{ old('website', $company->website) }}">
                    </div>
                </div>
            </div>

            <div class="kore-card">
                <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">
                    Address
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Address Line 1</label>
                        <input type="text" name="address_line1" class="form-control form-control-sm"
                            value="{{ old('address_line1', $company->address_line1) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address Line 2</label>
                        <input type="text" name="address_line2" class="form-control form-control-sm"
                            value="{{ old('address_line2', $company->address_line2) }}">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control form-control-sm"
                            value="{{ old('city', $company->city) }}">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">State</label>
                        <input type="text" name="state" class="form-control form-control-sm"
                            value="{{ old('state', $company->state) }}">
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">ZIP</label>
                        <input type="text" name="zip" class="form-control form-control-sm"
                            value="{{ old('zip', $company->zip) }}">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Country</label>
                        <input type="text" name="country" class="form-control form-control-sm"
                            value="{{ old('country', $company->country) }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-check-lg me-1"></i> Save Changes
        </button>
        <a href="{{ route('companies.show', $company) }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
    </div>
</form>

@endsection
