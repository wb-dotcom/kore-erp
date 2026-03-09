@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h4 class="mb-0 fw-700" style="font-size:1rem;">{{ $user->full_name }}</h4>
            <div style="font-size:0.75rem; color:#6b7280;">
                {{ $user->role?->name }}
                @if($user->department) · {{ $user->department }} @endif
                @if(!$user->is_active) · <span class="text-danger">Inactive</span> @endif
            </div>
        </div>
    </div>
    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-primary">
        <i class="bi bi-pencil me-1"></i> Edit
    </a>
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
            @if(!$user->is_active)
            <span class="badge badge-rejected mt-1">Inactive</span>
            @else
            <span class="badge badge-active mt-1">Active</span>
            @endif
            <hr style="border-color:#f3f4f6;">
            <div style="font-size:0.82rem; color:#6b7280; line-height:2; text-align:left;">
                @if($user->email)
                <div><i class="bi bi-envelope me-2"></i>{{ $user->email }}</div>
                @endif
                @if($user->phone)
                <div><i class="bi bi-telephone me-2"></i>{{ $user->phone }}</div>
                @endif
                @if($user->hire_date)
                <div><i class="bi bi-calendar me-2"></i>Hired {{ $user->hire_date->format('M d, Y') }}</div>
                @endif
            </div>
        </div>

        <div class="kore-card">
            <div class="fw-600 mb-2" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">PTO Policy</div>
            @if($user->ptoPolicy)
            <div style="font-size:0.82rem; color:#374151; line-height:2;">
                <div>Annual: <strong>{{ $user->ptoPolicy->annual_pto_hours }} hrs</strong></div>
                <div>Carry-over: <strong>{{ $user->ptoPolicy->carry_over_hours }} hrs</strong></div>
            </div>
            @else
            <div style="font-size:0.8rem; color:#9ca3af;">No PTO policy set.</div>
            @endif
            <a href="{{ route('admin.pto-policies') }}" class="btn btn-sm btn-outline-secondary mt-2" style="font-size:0.75rem;">
                Manage PTO Policies
            </a>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="kore-card">
            <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">Actions</div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-pencil me-1"></i> Edit User
                </a>
                @if($user->id !== auth()->id())
                <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                    onsubmit="return confirm('Deactivate {{ $user->full_name }}?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-person-x me-1"></i> Deactivate
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
