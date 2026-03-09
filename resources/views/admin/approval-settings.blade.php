@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0 fw-700" style="font-size:1rem;">Approval Settings</h4>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

<div class="kore-card">
    <p style="font-size:0.82rem; color:#6b7280; margin-bottom:20px;">
        Configure who is responsible for approving each request type.
    </p>

    <form action="{{ route('admin.approval-settings.save') }}" method="POST">
        @csrf
        @php
            $types = ['timesheet' => 'Timesheets', 'time_off' => 'Time-Off Requests', 'remote_work' => 'Remote Work', 'expense' => 'Expenses'];
        @endphp

        @foreach($types as $typeKey => $typeLabel)
        @php $current = $settings[$typeKey]?->first(); @endphp
        <div class="row g-3 align-items-center mb-3 pb-3" style="border-bottom:1px solid #f3f4f6;">
            <input type="hidden" name="settings[{{ $loop->index }}][type]" value="{{ $typeKey }}">
            <div class="col-sm-4">
                <div class="fw-600" style="font-size:0.85rem;">{{ $typeLabel }}</div>
                <div style="font-size:0.72rem; color:#9ca3af;">Approver for {{ strtolower($typeLabel) }}</div>
            </div>
            <div class="col-sm-5">
                <select name="settings[{{ $loop->index }}][approver]" class="form-select form-select-sm" required>
                    <option value="">— Select approver —</option>
                    @foreach($approvers as $a)
                    <option value="{{ $a->id }}"
                        {{ $current?->approver_user_id == $a->id ? 'selected' : '' }}>
                        {{ $a->full_name }} ({{ $a->role?->name }})
                    </option>
                    @endforeach
                </select>
            </div>
        </div>
        @endforeach

        <button type="submit" class="btn btn-primary btn-sm mt-2">
            <i class="bi bi-check-lg me-1"></i> Save Settings
        </button>
    </form>
</div>

@endsection
