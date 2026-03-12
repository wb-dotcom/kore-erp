@extends('layouts.app')
@section('title', 'Fee Schedules')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h4 class="mb-0 fw-700" style="font-size:1rem;">Fee Schedules</h4>
            <div style="font-size:0.72rem; color:#6b7280;">Named rate tables — select one per proposal</div>
        </div>
    </div>
    <a href="{{ route('admin.fee-schedules.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> New Schedule
    </a>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

@forelse($schedules as $schedule)
<div class="kore-card mb-3 d-flex align-items-center justify-content-between gap-3">
    <div style="flex:1;">
        <div class="d-flex align-items-center gap-2">
            <span class="fw-600" style="font-size:0.9rem;">{{ $schedule->name }}</span>
            @if($schedule->is_default)
            <span class="badge bg-primary" style="font-size:0.65rem;">Default</span>
            @endif
            @if(! $schedule->is_active)
            <span class="badge bg-secondary" style="font-size:0.65rem;">Inactive</span>
            @endif
        </div>
        @if($schedule->description)
        <div style="font-size:0.78rem; color:#6b7280; margin-top:2px;">{{ $schedule->description }}</div>
        @endif
        <div style="font-size:0.72rem; color:#9ca3af; margin-top:4px;">
            <i class="bi bi-list-ul me-1"></i>{{ $schedule->rates_count }} role rate{{ $schedule->rates_count !== 1 ? 's' : '' }}
            &bull; <i class="bi bi-file-earmark-text me-1"></i>{{ $schedule->proposals_count ?? 0 }} proposals
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.fee-schedules.edit', $schedule) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-pencil me-1"></i> Edit / Rates
        </a>
        <form action="{{ route('admin.fee-schedules.destroy', $schedule) }}" method="POST"
            onsubmit="return confirm('Delete schedule \'{{ addslashes($schedule->name) }}\'? This cannot be undone.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                <i class="bi bi-trash"></i>
            </button>
        </form>
    </div>
</div>
@empty
<div class="kore-card text-center py-5" style="color:#9ca3af;">
    <i class="bi bi-cash-stack fs-2 d-block mb-2"></i>
    No fee schedules yet.
    <div class="mt-2">
        <a href="{{ route('admin.fee-schedules.create') }}" class="btn btn-primary btn-sm">Create your first schedule</a>
    </div>
</div>
@endforelse

@endsection
