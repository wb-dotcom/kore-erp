@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-700" style="font-size:1rem;">Users</h4>
        <div style="font-size:0.75rem; color:#6b7280;">{{ $users->total() }} total</div>
    </div>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> New User
    </a>
</div>

@if(session('success'))
<div class="alert alert-success py-2 mb-3" style="font-size:0.8rem;">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger py-2 mb-3" style="font-size:0.8rem;">{{ session('error') }}</div>
@endif

<div class="kore-card p-0">
    <table class="table kore-table mb-0">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Department</th>
                <th>Hired</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:28px; height:28px; border-radius:50%; background:#4c8bf5; color:#fff;
                            display:flex; align-items:center; justify-content:center; font-size:0.7rem; font-weight:700; flex-shrink:0;">
                            {{ strtoupper(substr($user->first_name,0,1).substr($user->last_name,0,1)) }}
                        </div>
                        <a href="{{ route('admin.users.show', $user) }}" class="text-decoration-none fw-600"
                            style="color:#4c8bf5; font-size:0.82rem;">
                            {{ $user->full_name }}
                        </a>
                    </div>
                </td>
                <td style="font-size:0.78rem; color:#6b7280;">{{ $user->email }}</td>
                <td>
                    <span class="badge badge-draft">{{ $user->role?->name ?? '—' }}</span>
                </td>
                <td style="font-size:0.78rem; color:#6b7280;">{{ $user->department ?? '—' }}</td>
                <td style="font-size:0.78rem; color:#6b7280;">{{ $user->hire_date?->format('M d, Y') ?? '—' }}</td>
                <td>
                    @if($user->is_active)
                    <span class="badge badge-active">Active</span>
                    @else
                    <span class="badge badge-rejected">Inactive</span>
                    @endif
                </td>
                <td class="text-end">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" style="font-size:0.8rem;">
                            <li><a class="dropdown-item" href="{{ route('admin.users.show', $user) }}">
                                <i class="bi bi-eye me-2"></i>View
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.users.edit', $user) }}">
                                <i class="bi bi-pencil me-2"></i>Edit
                            </a></li>
                            @if($user->id !== auth()->id())
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                    onsubmit="return confirm('Deactivate {{ $user->full_name }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-person-x me-2"></i>Deactivate
                                    </button>
                                </form>
                            </li>
                            @endif
                        </ul>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center py-4" style="color:#9ca3af; font-size:0.8rem;">No users found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($users->hasPages())
<div class="mt-3">{{ $users->links() }}</div>
@endif

@endsection
