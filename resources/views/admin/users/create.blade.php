@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0 fw-700" style="font-size:1rem;">New User</h4>
</div>

@if($errors->any())
<div class="alert alert-danger py-2" style="font-size:0.8rem;">
    <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form action="{{ route('admin.users.store') }}" method="POST">
    @csrf
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="kore-card">
                <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">User Details</div>
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
                                value="1" checked>
                            <label class="form-check-label" for="isActive" style="font-size:0.8rem;">Active</label>
                        </div>
                    </div>
                    <div class="col-sm-8">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control form-control-sm"
                            value="{{ old('email') }}" required>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role_id" class="form-select form-select-sm" required>
                            <option value="">— Select —</option>
                            @foreach($roles as $r)
                            <option value="{{ $r->id }}" {{ old('role_id') == $r->id ? 'selected':'' }}>{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" class="form-control form-control-sm"
                            value="{{ old('department') }}">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control form-control-sm"
                            value="{{ old('phone') }}">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Hire Date</label>
                        <input type="date" name="hire_date" class="form-control form-control-sm"
                            value="{{ old('hire_date') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-check-lg me-1"></i> Create User
        </button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
    </div>
</form>

@endsection
