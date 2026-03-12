@extends('layouts.app')

@section('content')

<div class="mb-4">
    <h4 class="mb-0 fw-700" style="font-size:1rem;">Administration</h4>
    <div style="font-size:0.75rem; color:#6b7280;">System management &amp; configuration</div>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.users.index') }}" class="text-decoration-none">
            <div class="kore-card text-center py-3">
                <i class="bi bi-people fs-3 mb-1" style="color:#4c8bf5;"></i>
                <div class="fw-600" style="font-size:0.85rem;">User Management</div>
                <div style="font-size:0.72rem; color:#9ca3af;">{{ $stats['users'] }} active users</div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.approval-settings') }}" class="text-decoration-none">
            <div class="kore-card text-center py-3">
                <i class="bi bi-check2-square fs-3 mb-1" style="color:#f59e0b;"></i>
                <div class="fw-600" style="font-size:0.85rem;">Approval Settings</div>
                <div style="font-size:0.72rem; color:#9ca3af;">Configure approvers</div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.pto-policies') }}" class="text-decoration-none">
            <div class="kore-card text-center py-3">
                <i class="bi bi-calendar-heart fs-3 mb-1" style="color:#22c55e;"></i>
                <div class="fw-600" style="font-size:0.85rem;">PTO Policies</div>
                <div style="font-size:0.72rem; color:#9ca3af;">Annual leave settings</div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.system-settings') }}" class="text-decoration-none">
            <div class="kore-card text-center py-3">
                <i class="bi bi-gear fs-3 mb-1" style="color:#6b7280;"></i>
                <div class="fw-600" style="font-size:0.85rem;">System Settings</div>
                <div style="font-size:0.72rem; color:#9ca3af;">App configuration</div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.holidays.index') }}" class="text-decoration-none">
            <div class="kore-card text-center py-3">
                <i class="bi bi-calendar-event fs-3 mb-1" style="color:#ef4444;"></i>
                <div class="fw-600" style="font-size:0.85rem;">Holidays</div>
                <div style="font-size:0.72rem; color:#9ca3af;">Public holidays</div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.sectors.index') }}" class="text-decoration-none">
            <div class="kore-card text-center py-3">
                <i class="bi bi-tags fs-3 mb-1" style="color:#8b5cf6;"></i>
                <div class="fw-600" style="font-size:0.85rem;">Lookup Tables</div>
                <div style="font-size:0.72rem; color:#9ca3af;">Sectors, project types, statuses &amp; more</div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.activity-log') }}" class="text-decoration-none">
            <div class="kore-card text-center py-3">
                <i class="bi bi-journal-text fs-3 mb-1" style="color:#374151;"></i>
                <div class="fw-600" style="font-size:0.85rem;">Activity Log</div>
                <div style="font-size:0.72rem; color:#9ca3af;">System audit trail</div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.fee-schedules.index') }}" class="text-decoration-none">
            <div class="kore-card text-center py-3">
                <i class="bi bi-cash-stack fs-3 mb-1" style="color:#f59e0b;"></i>
                <div class="fw-600" style="font-size:0.85rem;">Fee Schedules</div>
                <div style="font-size:0.72rem; color:#9ca3af;">Named billing rate tables</div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('admin.templates') }}" class="text-decoration-none">
            <div class="kore-card text-center py-3">
                <i class="bi bi-layers fs-3 mb-1" style="color:#10b981;"></i>
                <div class="fw-600" style="font-size:0.85rem;">Project Templates</div>
                <div style="font-size:0.72rem; color:#9ca3af;">Reusable WBS structures</div>
            </div>
        </a>
    </div>
</div>

{{-- Recent Activity --}}
<div class="kore-card">
    <div class="fw-600 mb-3" style="font-size:0.8rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">
        Recent Activity
    </div>
    @forelse($recentActivity as $log)
    <div class="d-flex align-items-start gap-2 mb-2 pb-2" style="border-bottom:1px solid #f9fafb;">
        <div style="width:28px; height:28px; border-radius:50%; background:#f3f4f6;
            display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <i class="bi bi-person" style="font-size:0.75rem; color:#6b7280;"></i>
        </div>
        <div style="flex:1; min-width:0;">
            <div style="font-size:0.8rem; color:#374151;">
                <strong>{{ $log->user?->full_name ?? 'System' }}</strong>
                {{ $log->action }}
                @if($log->details) — <em>{{ $log->details }}</em> @endif
            </div>
            <div style="font-size:0.72rem; color:#9ca3af;">{{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</div>
        </div>
    </div>
    @empty
    <div style="font-size:0.8rem; color:#9ca3af; text-align:center; padding:16px 0;">No activity yet.</div>
    @endforelse
    <a href="{{ route('admin.activity-log') }}" class="d-block text-center mt-2"
        style="font-size:0.78rem; color:#4c8bf5; text-decoration:none;">
        View full log →
    </a>
</div>

@endsection
