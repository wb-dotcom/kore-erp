<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} — {{ config('app.name', 'Kore ERP') }}</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* ── Base ────────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            font-size: 0.875rem;
            background: #f5f6fa;
            color: #1a1d23;
            overflow-x: hidden;
        }

        /* ── Sidebar ─────────────────────────────────────────────── */
        #sidebar {
            width: 240px;
            min-height: 100vh;
            background: #1a1d23;
            color: #a9b0be;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            z-index: 1000;
            transition: width 0.25s ease;
        }
        #sidebar.collapsed { width: 60px; }

        .sidebar-logo {
            padding: 20px 20px 16px;
            border-bottom: 1px solid #2d3139;
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 64px;
        }
        .sidebar-logo .logo-text {
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.5px;
            white-space: nowrap;
        }
        .sidebar-logo .logo-text span { color: #4c8bf5; }
        #sidebar.collapsed .logo-text { display: none; }

        .sidebar-user {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #2d3139;
        }
        .sidebar-user .avatar {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: #4c8bf5;
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 600; font-size: 0.8rem;
            flex-shrink: 0;
        }
        .sidebar-user .user-info { overflow: hidden; }
        .sidebar-user .user-name { font-size: 0.8rem; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sidebar-user .user-role { font-size: 0.7rem; color: #6b7280; white-space: nowrap; }
        #sidebar.collapsed .user-info { display: none; }

        .sidebar-nav { flex: 1; padding: 8px 0; overflow-y: auto; }

        .nav-section-label {
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #4b5563;
            padding: 12px 20px 4px;
            white-space: nowrap;
        }
        #sidebar.collapsed .nav-section-label { opacity: 0; }

        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 20px;
            color: #9ca3af;
            text-decoration: none;
            border-radius: 0;
            transition: background 0.15s, color 0.15s;
            white-space: nowrap;
            overflow: hidden;
        }
        .sidebar-nav .nav-link i { font-size: 1rem; flex-shrink: 0; width: 20px; text-align: center; }
        .sidebar-nav .nav-link span { font-size: 0.8rem; }
        .sidebar-nav .nav-link:hover,
        .sidebar-nav .nav-link.active {
            background: #2d3139;
            color: #fff;
        }
        .sidebar-nav .nav-link.active { border-left: 3px solid #4c8bf5; }
        #sidebar.collapsed .nav-link span { display: none; }
        #sidebar.collapsed .nav-link { padding: 10px; justify-content: center; }

        .sidebar-footer {
            padding: 12px 16px;
            border-top: 1px solid #2d3139;
        }
        .sidebar-footer .nav-link {
            display: flex; align-items: center; gap: 10px;
            color: #9ca3af; text-decoration: none;
            padding: 6px 4px;
            font-size: 0.8rem;
        }
        .sidebar-footer .nav-link:hover { color: #fff; }
        #sidebar.collapsed .sidebar-footer span { display: none; }

        /* ── Main Content ────────────────────────────────────────── */
        #main-wrapper {
            margin-left: 240px;
            transition: margin-left 0.25s ease;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        body.sidebar-collapsed #main-wrapper { margin-left: 60px; }

        /* ── Top Header ──────────────────────────────────────────── */
        #top-header {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 0 24px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 900;
        }
        #top-header .page-title { font-size: 1.1rem; font-weight: 600; color: #1a1d23; }
        #top-header .header-right { display: flex; align-items: center; gap: 16px; }

        .toggle-sidebar-btn {
            background: none; border: none; padding: 6px; cursor: pointer;
            color: #6b7280; border-radius: 6px;
        }
        .toggle-sidebar-btn:hover { background: #f3f4f6; color: #1a1d23; }

        /* Search bar */
        .header-search {
            position: relative;
        }
        .header-search input {
            background: #f5f6fa;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 6px 12px 6px 34px;
            font-size: 0.8rem;
            width: 240px;
            outline: none;
        }
        .header-search input:focus { border-color: #4c8bf5; }
        .header-search i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #9ca3af; }

        /* ── Page Content ────────────────────────────────────────── */
        #page-content { padding: 24px; flex: 1; }

        /* ── Common Component Styles ─────────────────────────────── */
        .kore-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .kore-card-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f3f4f6;
        }
        .kore-card-header h5 { font-size: 0.9rem; font-weight: 600; margin: 0; color: #1a1d23; }

        /* Stat cards */
        .stat-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        .stat-card .stat-value { font-size: 1.75rem; font-weight: 700; color: #1a1d23; }
        .stat-card .stat-label { font-size: 0.75rem; color: #6b7280; margin-top: 4px; }
        .stat-card .stat-icon { font-size: 1.5rem; margin-bottom: 8px; }

        /* Badges */
        .badge-approved  { background: #d1fae5; color: #065f46; }
        .badge-pending   { background: #fef3c7; color: #92400e; }
        .badge-rejected  { background: #fee2e2; color: #991b1b; }
        .badge-active    { background: #dbeafe; color: #1e40af; }
        .badge-overdue   { background: #fee2e2; color: #991b1b; }
        .badge-completed { background: #d1fae5; color: #065f46; }
        .badge-draft     { background: #f3f4f6; color: #374151; }
        .badge-sent      { background: #dbeafe; color: #1e40af; }
        .badge-paid      { background: #d1fae5; color: #065f46; }

        /* Dashboard tab pills */
        .dashboard-tabs { margin-bottom: 24px; }
        .dashboard-tabs .nav-link {
            color: #6b7280; font-size: 0.8rem; font-weight: 500;
            padding: 8px 18px; border-radius: 8px; border: 1px solid transparent;
        }
        .dashboard-tabs .nav-link.active {
            background: #4c8bf5; color: #fff; border-color: #4c8bf5;
        }
        .dashboard-tabs .nav-link:hover:not(.active) {
            background: #f3f4f6; color: #1a1d23;
        }

        /* Alert banners */
        .kore-alert { border-left: 4px solid; padding: 12px 16px; border-radius: 0 8px 8px 0; margin-bottom: 16px; font-size: 0.8rem; }
        .kore-alert-warning { background: #fffbeb; border-color: #f59e0b; color: #92400e; }
        .kore-alert-danger  { background: #fef2f2; border-color: #ef4444; color: #991b1b; }
        .kore-alert-info    { background: #eff6ff; border-color: #3b82f6; color: #1e40af; }
        .kore-alert-success { background: #f0fdf4; border-color: #22c55e; color: #166534; }

        /* Tables */
        .kore-table { font-size: 0.8rem; }
        .kore-table th { font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; background: #f9fafb; }
        .kore-table td, .kore-table th { padding: 10px 12px; vertical-align: middle; }
        .kore-table tbody tr:hover { background: #f9fafb; }

        /* Responsive */
        @media (max-width: 768px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.mobile-open { transform: translateX(0); }
            #main-wrapper { margin-left: 0 !important; }
        }
    </style>

    @stack('styles')
</head>
<body class="{{ session('sidebar_collapsed') ? 'sidebar-collapsed' : '' }}">

<!-- ─────────────── SIDEBAR ─────────────────────────────────── -->
<nav id="sidebar" class="{{ session('sidebar_collapsed') ? 'collapsed' : '' }}">

    <!-- Logo -->
    <div class="sidebar-logo">
        <div class="logo-text">KORE <span>ERP</span></div>
    </div>

    <!-- User Info -->
    <div class="sidebar-user">
        <div class="avatar">
            {{ strtoupper(substr(auth()->user()->first_name ?? 'U', 0, 1)) }}{{ strtoupper(substr(auth()->user()->last_name ?? '', 0, 1)) }}
        </div>
        <div class="user-info">
            <div class="user-name">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</div>
            <div class="user-role">{{ auth()->user()->role->name ?? 'Employee' }}</div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="sidebar-nav">

        <div class="nav-section-label">Main</div>

        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard*') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('proposals.index') }}" class="nav-link {{ request()->routeIs('proposals*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-text"></i>
            <span>Proposals</span>
        </a>

        <a href="{{ route('projects.index') }}" class="nav-link {{ request()->routeIs('projects*') ? 'active' : '' }}">
            <i class="bi bi-folder2-open"></i>
            <span>Projects</span>
        </a>

        <a href="{{ route('contacts.index') }}" class="nav-link {{ request()->routeIs('contacts*') ? 'active' : '' }}">
            <i class="bi bi-person-lines-fill"></i>
            <span>Contacts</span>
        </a>

        <div class="nav-section-label">Time & Tasks</div>

        <a href="{{ route('timesheet.index') }}" class="nav-link {{ request()->routeIs('timesheet*') ? 'active' : '' }}">
            <i class="bi bi-clock"></i>
            <span>Timesheet</span>
        </a>

        <a href="{{ route('tasks.mine') }}" class="nav-link {{ request()->routeIs('tasks*') ? 'active' : '' }}">
            <i class="bi bi-check2-square"></i>
            <span>My Tasks</span>
        </a>

        <a href="{{ route('approvals.index') }}" class="nav-link {{ request()->routeIs('approvals*') ? 'active' : '' }}">
            <i class="bi bi-shield-check"></i>
            <span>Approval Center</span>
        </a>

        <a href="{{ route('schedule.project') }}" class="nav-link {{ request()->routeIs('schedule*') ? 'active' : '' }}">
            <i class="bi bi-calendar3-range"></i>
            <span>Project Schedule</span>
        </a>

        <div class="nav-section-label">Finance</div>

        <a href="{{ route('invoices.index') }}" class="nav-link {{ request()->routeIs('invoices*') ? 'active' : '' }}">
            <i class="bi bi-receipt"></i>
            <span>Invoicing</span>
        </a>

        @if(auth()->user()->role->name === 'Admin')
        <div class="nav-section-label">System</div>
        <a href="{{ route('admin.index') }}" class="nav-link {{ request()->routeIs('admin*') ? 'active' : '' }}">
            <i class="bi bi-gear-fill"></i>
            <span>Administration</span>
        </a>
        @endif

    </div>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <a href="{{ route('profile') }}" class="nav-link">
            <i class="bi bi-person-circle"></i>
            <span>My Profile</span>
        </a>
        <form action="{{ route('logout') }}" method="POST" class="mt-1">
            @csrf
            <button type="submit" class="nav-link w-100 text-start bg-transparent border-0">
                <i class="bi bi-box-arrow-left"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>
</nav>

<!-- ─────────────── MAIN WRAPPER ──────────────────────────────── -->
<div id="main-wrapper">

    <!-- Top Header -->
    <header id="top-header">
        <div class="d-flex align-items-center gap-3">
            <button class="toggle-sidebar-btn" id="toggleSidebar" title="Toggle sidebar">
                <i class="bi bi-list fs-5"></i>
            </button>
            <div class="page-title">{{ $title ?? 'Dashboard' }}</div>
        </div>

        <div class="header-right">
            <!-- Search -->
            <div class="header-search d-none d-md-block">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Search..." id="globalSearch">
            </div>

            <!-- Notifications bell placeholder -->
            <button class="btn btn-sm btn-light position-relative" title="Notifications">
                <i class="bi bi-bell"></i>
            </button>

            <!-- Quick user menu -->
            <div class="dropdown">
                <button class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown">
                    {{ auth()->user()->first_name ?? 'User' }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('profile') }}"><i class="bi bi-person me-2"></i>Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-box-arrow-left me-2"></i>Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <div class="px-4 pt-3">
        @if(session('success'))
        <div class="kore-alert kore-alert-success d-flex align-items-center justify-content-between">
            <span><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</span>
            <button type="button" class="btn-close btn-close-sm" onclick="this.parentElement.remove()"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="kore-alert kore-alert-danger d-flex align-items-center justify-content-between">
            <span><i class="bi bi-x-circle me-2"></i>{{ session('error') }}</span>
            <button type="button" class="btn-close btn-close-sm" onclick="this.parentElement.remove()"></button>
        </div>
        @endif
        @if($errors->any())
        <div class="kore-alert kore-alert-danger">
            <i class="bi bi-exclamation-circle me-2"></i>
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- Page Content -->
    <main id="page-content">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="text-center py-3 border-top" style="font-size:0.72rem; color:#9ca3af; background:#fff;">
        &copy; {{ date('Y') }} {{ config('app.name', 'Kore ERP') }} &mdash; v{{ config('kore.version', '1.0.0') }}
    </footer>

</div><!-- /#main-wrapper -->

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ── Sidebar toggle ────────────────────────────────────────────────
const sidebar     = document.getElementById('sidebar');
const mainWrapper = document.getElementById('main-wrapper');
const toggleBtn   = document.getElementById('toggleSidebar');
const body        = document.body;

toggleBtn?.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    body.classList.toggle('sidebar-collapsed');
    // Persist in session via AJAX
    fetch('/api/toggle-sidebar', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } });
});

// ── Auto-dismiss flash messages ───────────────────────────────────
setTimeout(() => {
    document.querySelectorAll('.kore-alert').forEach(el => {
        el.style.transition = 'opacity 0.4s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    });
}, 4000);

// ── CSRF for all AJAX requests ────────────────────────────────────
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
if (window.axios) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}
</script>

@stack('scripts')

</body>
</html>
