@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('contacts.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0 fw-700" style="font-size:1rem;">New Contact</h4>
</div>

@if($errors->any())
<div class="alert alert-danger py-2" style="font-size:0.8rem;">
    <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form action="{{ route('contacts.store') }}" method="POST">
    @csrf
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="kore-card">
                <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">
                    Contact Details
                </div>
                <div class="row g-3">
                    <div class="col-sm-5">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control form-control-sm"
                            value="{{ old('first_name') }}" required>
                    </div>
                    <div class="col-sm-5">
                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control form-control-sm"
                            value="{{ old('last_name') }}" required>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">Active</label>
                        <div class="form-check mt-1">
                            <input type="checkbox" name="is_active" id="isActive" class="form-check-input"
                                value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive" style="font-size:0.8rem;">Active</label>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Company</label>
                        <select name="company_id" class="form-select form-select-sm">
                            <option value="">— Select company —</option>
                            @foreach($companies as $c)
                            <option value="{{ $c->id }}" {{ old('company_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Contact Type</label>
                        <select name="contact_type_id" class="form-select form-select-sm">
                            <option value="">— Select —</option>
                            @foreach($contactTypes as $t)
                            <option value="{{ $t->id }}" {{ old('contact_type_id') == $t->id ? 'selected' : '' }}>
                                {{ $t->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Job Title</label>
                        <input type="text" name="title" class="form-control form-control-sm"
                            value="{{ old('title') }}" placeholder="e.g. Director">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control form-control-sm"
                            value="{{ old('email') }}">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Business Phone</label>
                        <input type="text" name="business_phone" class="form-control form-control-sm"
                            value="{{ old('business_phone') }}">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Mobile Phone</label>
                        <input type="text" name="mobile_phone" class="form-control form-control-sm"
                            value="{{ old('mobile_phone') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="3"
                            placeholder="Any relevant notes...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-check-lg me-1"></i> Save Contact
        </button>
        <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
    </div>
</form>

@endsection
