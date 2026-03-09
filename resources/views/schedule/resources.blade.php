@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">Resource Dashboard</h4>
        <div style="font-size:0.75rem; color:#6b7280;">Workload overview by team member</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('schedule.project') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-diagram-3 me-1"></i> By Project
        </a>
        <a href="{{ route('schedule.employee') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-people me-1"></i> By Employee
        </a>
    </div>
</div>

<div class="kore-card p-0">
    <table class="table kore-table mb-0">
        <thead>
            <tr>
                <th>Team Member</th>
                <th>Role / Dept</th>
                <th class="text-center">Open Tasks</th>
                <th style="width:40%;">Workload</th>
            </tr>
        </thead>
        <tbody>
            @php $maxTasks = $users->max('open_tasks') ?: 1; @endphp
            @forelse($users as $user)
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:28px; height:28px; border-radius:50%; background:#4c8bf5; color:#fff;
                            display:flex; align-items:center; justify-content:center; font-size:0.7rem; font-weight:700; flex-shrink:0;">
                            {{ strtoupper(substr($user->first_name,0,1).substr($user->last_name,0,1)) }}
                        </div>
                        <span class="fw-600" style="font-size:0.82rem;">{{ $user->full_name }}</span>
                    </div>
                </td>
                <td style="font-size:0.78rem; color:#6b7280;">
                    {{ $user->department ?? $user->role?->name ?? '—' }}
                </td>
                <td class="text-center fw-600" style="font-size:0.85rem;
                    color:{{ $user->open_tasks > 5 ? '#ef4444' : ($user->open_tasks > 2 ? '#f59e0b' : '#374151') }};">
                    {{ $user->open_tasks }}
                </td>
                <td>
                    @php $pct = $maxTasks ? round($user->open_tasks / $maxTasks * 100) : 0; @endphp
                    <div class="d-flex align-items-center gap-2">
                        <div class="progress flex-grow-1" style="height:8px;">
                            <div class="progress-bar"
                                style="width:{{ $pct }}%; background:{{ $pct > 80 ? '#ef4444' : ($pct > 50 ? '#f59e0b' : '#4c8bf5') }};">
                            </div>
                        </div>
                        <span style="font-size:0.72rem; color:#9ca3af; width:30px; text-align:right;">{{ $pct }}%</span>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center py-5" style="color:#9ca3af;">
                    No active team members found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
