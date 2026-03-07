@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0 fw-700" style="font-size:1rem;">Activity Log</h4>
</div>

<div class="kore-card mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-sm-4">
            <label class="form-label" style="font-size:0.75rem; font-weight:600;">Search</label>
            <input type="text" name="search" class="form-control form-control-sm"
                placeholder="Action description..." value="{{ request('search') }}">
        </div>
        <div class="col-sm-3">
            <label class="form-label" style="font-size:0.75rem; font-weight:600;">User</label>
            <select name="user_id" class="form-select form-select-sm">
                <option value="">All Users</option>
                @foreach($users as $u)
                <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected':'' }}>
                    {{ $u->full_name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-auto">
            <button type="submit" class="btn btn-sm btn-primary">Filter</button>
            <a href="{{ route('admin.activity-log') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
        </div>
    </form>
</div>

<div class="kore-card p-0">
    <table class="table kore-table mb-0">
        <thead>
            <tr>
                <th>Time</th>
                <th>User</th>
                <th>Action</th>
                <th>Subject</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td style="font-size:0.75rem; color:#9ca3af; white-space:nowrap;">
                    {{ $log->created_at->format('M d, Y g:i A') }}
                </td>
                <td style="font-size:0.8rem;">{{ $log->user?->full_name ?? 'System' }}</td>
                <td style="font-size:0.8rem; color:#374151;">{{ $log->action }}</td>
                <td style="font-size:0.78rem; color:#6b7280;">{{ $log->details ?? "—" }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center py-4" style="color:#9ca3af; font-size:0.8rem;">
                    No activity found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($logs->hasPages())
<div class="mt-3">{{ $logs->links() }}</div>
@endif

@endsection
