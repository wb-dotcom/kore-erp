@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0 fw-700" style="font-size:1rem;">Edit — {{ $user->full_name }}</h4>
</div>

@if($errors->any())
<div class="alert alert-danger py-2" style="font-size:0.8rem;">
    <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form action="{{ route('admin.users.update', $user) }}" method="POST">
    @csrf @method('PUT')
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="kore-card">
                <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">User Details</div>
                <div class="row g-3">
                    <div class="col-sm-5">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control form-control-sm"
                            value="{{ old('first_name', $user->first_name) }}" required>
                    </div>
                    <div class="col-sm-5">
                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control form-control-sm"
                            value="{{ old('last_name', $user->last_name) }}" required>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label">Active</label>
                        <div class="form-check mt-1">
                            <input type="checkbox" name="is_active" id="isActive" class="form-check-input"
                                value="1" {{ $user->is_active ? 'checked':'' }}>
                            <label class="form-check-label" for="isActive" style="font-size:0.8rem;">Active</label>
                        </div>
                    </div>
                    <div class="col-sm-8">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control form-control-sm"
                            value="{{ old('email', $user->email) }}" required>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role_id" class="form-select form-select-sm" required>
                            @foreach($roles as $r)
                            <option value="{{ $r->id }}" {{ old('role_id', $user->role_id) == $r->id ? 'selected':'' }}>{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">New Password <span style="color:#9ca3af; font-weight:400;">(leave blank to keep)</span></label>
                        <input type="password" name="password" class="form-control form-control-sm">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control form-control-sm">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" class="form-control form-control-sm"
                            value="{{ old('department', $user->department) }}">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control form-control-sm"
                            value="{{ old('phone', $user->phone) }}">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Hire Date</label>
                        <input type="date" name="hire_date" class="form-control form-control-sm"
                            value="{{ old('hire_date', $user->hire_date?->format('Y-m-d')) }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-check-lg me-1"></i> Save Changes
        </button>
        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
    </div>
</form>

@endsection
