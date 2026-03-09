@extends('layouts.app')

@section('content')

<div class="mb-4">
    <h4 class="mb-0 fw-700" style="font-size:1rem;">My Profile</h4>
    <div style="font-size:0.75rem; color:#6b7280;">{{ $user->role?->name }}</div>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

<div class="row g-4">
    <div class="col-lg-4">
        <div class="kore-card text-center mb-4">
            <div style="width:64px; height:64px; border-radius:50%; background:#4c8bf5; color:#fff;
                display:flex; align-items:center; justify-content:center; font-size:1.4rem; font-weight:700; margin:0 auto 12px;">
                {{ strtoupper(substr($user->first_name,0,1).substr($user->last_name,0,1)) }}
            </div>
            <div class="fw-600" style="font-size:1rem;">{{ $user->full_name }}</div>
            <div style="font-size:0.8rem; color:#6b7280;">{{ $user->role?->name }}</div>
            @if($user->department)
            <div style="font-size:0.78rem; color:#9ca3af;">{{ $user->department }}</div>
            @endif
            <hr style="border-color:#f3f4f6;">
            <div style="font-size:0.82rem; color:#6b7280; line-height:2; text-align:left;">
                <div><i class="bi bi-envelope me-2"></i>{{ $user->email }}</div>
                @if($user->phone)<div><i class="bi bi-telephone me-2"></i>{{ $user->phone }}</div>@endif
                @if($user->hire_date)<div><i class="bi bi-calendar me-2"></i>Hired {{ $user->hire_date->format('M d, Y') }}</div>@endif
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <form action="{{ route('profile.update') }}" method="POST">
            @csrf @method('PUT')
            <div class="kore-card mb-4">
                <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">Personal Info</div>
                <div class="row g-3">
                    <div class="col-sm-5">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control form-control-sm"
                            value="{{ old('first_name', $user->first_name) }}" required>
                    </div>
                    <div class="col-sm-5">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control form-control-sm"
                            value="{{ old('last_name', $user->last_name) }}" required>
                    </div>
                    <div class="col-sm-5">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control form-control-sm"
                            value="{{ old('phone', $user->phone) }}">
                    </div>
                </div>
            </div>

            <div class="kore-card mb-4">
                <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">
                    Change Password <span style="font-weight:400; color:#9ca3af;">(leave blank to keep current)</span>
                </div>
                <div class="row g-3">
                    <div class="col-sm-5">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control form-control-sm">
                    </div>
                    <div class="col-sm-5">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control form-control-sm">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-check-lg me-1"></i> Save Changes
            </button>
        </form>
    </div>
</div>

@endsection
