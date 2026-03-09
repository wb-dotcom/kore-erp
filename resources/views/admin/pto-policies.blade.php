@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0 fw-700" style="font-size:1rem;">PTO Policies</h4>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

@foreach($users as $user)
<div class="kore-card mb-3">
    <div class="d-flex justify-content-between align-items-start">
        <div class="d-flex align-items-center gap-2 mb-2">
            <div style="width:32px; height:32px; border-radius:50%; background:#4c8bf5; color:#fff;
                display:flex; align-items:center; justify-content:center; font-size:0.75rem; font-weight:700;">
                {{ strtoupper(substr($user->first_name,0,1).substr($user->last_name,0,1)) }}
            </div>
            <div>
                <div class="fw-600" style="font-size:0.85rem;">{{ $user->full_name }}</div>
                <div style="font-size:0.72rem; color:#9ca3af;">{{ $user->role?->name }}</div>
            </div>
        </div>
    </div>
    <form action="{{ route('admin.pto-policies.save', $user) }}" method="POST">
        @csrf
        <div class="row g-3">
            <div class="col-sm-3">
                <label class="form-label" style="font-size:0.75rem;">Annual PTO Hours</label>
                <input type="number" name="annual_pto_hours" class="form-control form-control-sm"
                    step="0.5" min="0" value="{{ $user->ptoPolicy?->annual_pto_hours ?? 0 }}">
            </div>
            <div class="col-sm-3">
                <label class="form-label" style="font-size:0.75rem;">Carry-Over Hours</label>
                <input type="number" name="carry_over_hours" class="form-control form-control-sm"
                    step="0.5" min="0" value="{{ $user->ptoPolicy?->carry_over_hours ?? 0 }}">
            </div>
            <div class="col-sm-3">
                <label class="form-label" style="font-size:0.75rem;">Effective Date</label>
                <input type="date" name="effective_date" class="form-control form-control-sm"
                    value="{{ $user->ptoPolicy?->effective_date?->format('Y-m-d') }}">
            </div>
            <div class="col-sm-auto d-flex align-items-end">
                <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
            </div>
        </div>
    </form>
</div>
@endforeach

@endsection
