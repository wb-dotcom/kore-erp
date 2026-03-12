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
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 0.875rem;
            background: #f2f4f8;
            color: #1a1d23;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Sidebar ─────────────────────────────────────────────── */
        #sidebar {
            width: 248px;
            height: 100vh;
            background: linear-gradient(180deg, #16181f 0%, #1a1d25 100%);
            color: #a9b0be;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            z-index: 1000;
            transition: width 0.28s cubic-bezier(0.4,0,0.2,1);
            overflow: hidden;
        }
        #sidebar.collapsed { width: 64px; }

        .sidebar-logo {
            padding: 20px 18px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 66px;
        }
        .sidebar-logo .logo-mark {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, #4c8bf5 0%, #6c63ff 100%);
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            font-size: 0.75rem; font-weight: 800; color: #fff; letter-spacing: -0.5px;
            box-shadow: 0 4px 12px rgba(76,139,245,0.35);
        }
        .sidebar-logo .logo-text {
            font-size: 1.15rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.5px;
            white-space: nowrap;
        }
        .sidebar-logo .logo-text span { color: #4c8bf5; }
        #sidebar.collapsed .logo-text { display: none; }

        .sidebar-user {
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            margin: 8px 10px;
            border-radius: 10px;
            background: rgba(255,255,255,0.04);
        }
        .sidebar-user .avatar {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4c8bf5, #6c63ff);
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.78rem;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(76,139,245,0.3);
        }
        .sidebar-user .user-info { overflow: hidden; }
        .sidebar-user .user-name { font-size: 0.8rem; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sidebar-user .user-role { font-size: 0.68rem; color: #6b7280; white-space: nowrap; }
        #sidebar.collapsed .sidebar-user { margin: 8px auto; width: 44px; justify-content: center; padding: 8px; }
        #sidebar.collapsed .user-info { display: none; }

        .sidebar-nav { flex: 1; padding: 6px 0; overflow-y: auto; overflow-x: hidden; min-height: 0; }
        .sidebar-nav::-webkit-scrollbar { width: 3px; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 2px; }

        .nav-section-label {
            font-size: 0.63rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #3f4553;
            padding: 14px 18px 4px;
            white-space: nowrap;
        }
        #sidebar.collapsed .nav-section-label { opacity: 0; height: 0; padding: 0; overflow: hidden; }

        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px 8px 14px;
            margin: 1px 8px;
            color: #8b92a4;
            text-decoration: none;
            border-radius: 9px;
            transition: background 0.15s ease, color 0.15s ease, transform 0.1s ease;
            white-space: nowrap;
            overflow: hidden;
        }
        .sidebar-nav .nav-link .nav-icon {
            width: 30px; height: 30px;
            border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            font-size: 0.9rem;
            transition: background 0.15s ease;
        }
        .sidebar-nav .nav-link span { font-size: 0.8rem; font-weight: 500; }
        .sidebar-nav .nav-link:hover {
            background: rgba(255,255,255,0.07);
            color: #e0e4ee;
        }
        .sidebar-nav .nav-link:hover .nav-icon { background: rgba(76,139,245,0.15); color: #6dabf7; }
        .sidebar-nav .nav-link.active {
            background: rgba(76,139,245,0.14);
            color: #fff;
        }
        .sidebar-nav .nav-link.active .nav-icon { background: rgba(76,139,245,0.22); color: #74b3ff; }
        #sidebar.collapsed .nav-link span { display: none; }
        #sidebar.collapsed .nav-link { padding: 7px; margin: 1px auto; width: 44px; justify-content: center; }
        #sidebar.collapsed .nav-icon { width: 32px; height: 32px; }

        .sidebar-footer {
            padding: 10px 10px;
            border-top: 1px solid rgba(255,255,255,0.06);
        }
        .sidebar-footer .nav-link {
            display: flex; align-items: center; gap: 10px;
            color: #8b92a4; text-decoration: none;
            padding: 7px 10px 7px 14px;
            margin: 1px 0;
            font-size: 0.8rem;
            border-radius: 9px;
            transition: background 0.15s, color 0.15s;
        }
        .sidebar-footer .nav-link .nav-icon {
            width: 28px; height: 28px;
            border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem;
        }
        .sidebar-footer .nav-link:hover { background: rgba(255,255,255,0.07); color: #fff; }
        #sidebar.collapsed .sidebar-footer span { display: none; }
        #sidebar.collapsed .sidebar-footer .nav-link { padding: 7px; width: 44px; justify-content: center; margin: 1px auto; }

        /* ── Main Content ────────────────────────────────────────── */
        #main-wrapper {
            margin-left: 248px;
            transition: margin-left 0.28s cubic-bezier(0.4,0,0.2,1);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        body.sidebar-collapsed #main-wrapper { margin-left: 64px; }

        /* ── Top Header ──────────────────────────────────────────── */
        #top-header {
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(0,0,0,0.06);
            padding: 0 28px;
            height: 66px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 900;
        }
        #top-header .page-title { font-size: 1.05rem; font-weight: 600; color: #1a1d23; letter-spacing: -0.3px; }
        #top-header .header-right { display: flex; align-items: center; gap: 10px; }

        .toggle-sidebar-btn {
            background: none; border: none; padding: 7px 8px; cursor: pointer;
            color: #6b7280; border-radius: 9px;
            transition: background 0.15s, color 0.15s;
        }
        .toggle-sidebar-btn:hover { background: #f0f2f5; color: #1a1d23; }

        /* Search bar */
        .header-search { position: relative; }
        .header-search input {
            background: #f5f6fa;
            border: 1.5px solid transparent;
            border-radius: 20px;
            padding: 7px 14px 7px 36px;
            font-size: 0.8rem;
            width: 220px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s, width 0.3s;
        }
        .header-search input:focus {
            border-color: #4c8bf5;
            box-shadow: 0 0 0 3px rgba(76,139,245,0.12);
            width: 260px;
        }
        .header-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 0.85rem; }

        /* Header action buttons */
        .header-icon-btn {
            width: 36px; height: 36px;
            display: flex; align-items: center; justify-content: center;
            background: #f5f6fa;
            border: none; border-radius: 10px;
            color: #6b7280; cursor: pointer;
            transition: background 0.15s, color 0.15s, transform 0.1s;
            font-size: 1rem;
        }
        .header-icon-btn:hover { background: #e9ecf0; color: #1a1d23; transform: scale(1.05); }

        /* ── Page Content ────────────────────────────────────────── */
        #page-content { padding: 28px; flex: 1; }

        /* ── Cards ───────────────────────────────────────────────── */
        .kore-card {
            background: #fff;
            border-radius: 16px;
            padding: 22px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
            border: 1px solid rgba(0,0,0,0.04);
        }
        .kore-card-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid #f3f4f6;
        }
        .kore-card-header h5 { font-size: 0.875rem; font-weight: 600; margin: 0; color: #1a1d23; letter-spacing: -0.2px; }

        /* ── Stat cards ──────────────────────────────────────────── */
        .stat-card {
            background: #fff;
            border-radius: 18px;
            padding: 22px 22px 18px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.08), 0 12px 28px rgba(0,0,0,0.08);
        }
        .stat-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .stat-icon-wrap {
            width: 44px; height: 44px;
            border-radius: 13px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
        }
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1d23;
            line-height: 1;
            letter-spacing: -1px;
        }
        .stat-card .stat-label {
            font-size: 0.75rem;
            color: #8b92a4;
            margin-top: 5px;
            font-weight: 500;
        }
        .stat-card .stat-sublabel {
            font-size: 0.7rem;
            color: #b4bac6;
            margin-top: 2px;
        }
        /* Decorative corner accent */
        .stat-card::after {
            content: '';
            position: absolute;
            top: -20px; right: -20px;
            width: 80px; height: 80px;
            border-radius: 50%;
            opacity: 0.06;
        }
        .stat-card.accent-blue::after  { background: #4c8bf5; }
        .stat-card.accent-green::after { background: #22c55e; }
        .stat-card.accent-amber::after { background: #f59e0b; }
        .stat-card.accent-red::after   { background: #ef4444; }
        .stat-card.accent-purple::after{ background: #8b5cf6; }
        .stat-card.accent-cyan::after  { background: #06b6d4; }

        /* ── Badges ──────────────────────────────────────────────── */
        .badge {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 4px 9px;
            border-radius: 20px;
            letter-spacing: 0.1px;
        }
        .badge-approved  { background: #d1fae5; color: #065f46; }
        .badge-pending   { background: #fef3c7; color: #92400e; }
        .badge-rejected  { background: #fee2e2; color: #991b1b; }
        .badge-active    { background: #dbeafe; color: #1e40af; }
        .badge-overdue   { background: #fee2e2; color: #991b1b; }
        .badge-completed { background: #d1fae5; color: #065f46; }
        .badge-draft     { background: #f3f4f6; color: #374151; }
        .badge-sent      { background: #dbeafe; color: #1e40af; }
        .badge-paid      { background: #d1fae5; color: #065f46; }

        /* ── Dashboard tab pills ─────────────────────────────────── */
        .dashboard-tabs { margin-bottom: 24px; }
        .dashboard-tabs .nav {
            background: #fff;
            padding: 5px;
            border-radius: 14px;
            display: inline-flex;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 2px 10px rgba(0,0,0,0.04);
            border: 1px solid rgba(0,0,0,0.04);
        }
        .dashboard-tabs .nav-link {
            color: #6b7280; font-size: 0.8rem; font-weight: 500;
            padding: 8px 18px; border-radius: 10px; border: none;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        }
        .dashboard-tabs .nav-link.active {
            background: #4c8bf5;
            color: #fff;
            box-shadow: 0 2px 8px rgba(76,139,245,0.35);
        }
        .dashboard-tabs .nav-link:hover:not(.active) {
            background: #f3f4f6; color: #1a1d23;
        }

        /* ── Alert banners ───────────────────────────────────────── */
        .kore-alert {
            border-left: 4px solid; padding: 12px 16px;
            border-radius: 0 12px 12px 0; margin-bottom: 16px; font-size: 0.8rem;
            animation: slideInAlert 0.3s ease;
        }
        @keyframes slideInAlert {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .kore-alert-warning { background: #fffbeb; border-color: #f59e0b; color: #92400e; }
        .kore-alert-danger  { background: #fef2f2; border-color: #ef4444; color: #991b1b; }
        .kore-alert-info    { background: #eff6ff; border-color: #3b82f6; color: #1e40af; }
        .kore-alert-success { background: #f0fdf4; border-color: #22c55e; color: #166534; }

        /* ── Tables ──────────────────────────────────────────────── */
        .kore-table { font-size: 0.8rem; border-collapse: separate; border-spacing: 0; }
        .kore-table th {
            font-weight: 600; font-size: 0.7rem;
            text-transform: uppercase; letter-spacing: 0.6px;
            color: #9ca3af; background: #f9fafb;
            padding: 10px 14px;
        }
        .kore-table th:first-child { border-radius: 8px 0 0 8px; }
        .kore-table th:last-child  { border-radius: 0 8px 8px 0; }
        .kore-table td { padding: 11px 14px; vertical-align: middle; border-bottom: 1px solid #f8f9fa; }
        .kore-table tbody tr:last-child td { border-bottom: none; }
        .kore-table tbody tr {
            transition: background 0.12s ease;
        }
        .kore-table tbody tr:hover td { background: #f9fafb; }

        /* ── Buttons ─────────────────────────────────────────────── */
        .btn { border-radius: 9px; font-size: 0.8rem; font-weight: 500; transition: all 0.15s ease; }
        .btn-sm { padding: 6px 14px; border-radius: 8px; }
        .btn-primary { background: #4c8bf5; border-color: #4c8bf5; box-shadow: 0 2px 6px rgba(76,139,245,0.3); }
        .btn-primary:hover { background: #3a7ae4; border-color: #3a7ae4; transform: translateY(-1px); box-shadow: 0 4px 10px rgba(76,139,245,0.35); }
        .btn-outline-primary { color: #4c8bf5; border-color: #4c8bf5; }
        .btn-outline-primary:hover { background: #4c8bf5; color: #fff; box-shadow: 0 2px 6px rgba(76,139,245,0.25); }
        .btn-outline-secondary { border-color: #e5e7eb; color: #6b7280; }
        .btn-outline-secondary:hover { background: #f3f4f6; color: #374151; border-color: #d1d5db; }

        /* ── Progress bars ───────────────────────────────────────── */
        .progress { border-radius: 6px; background: #f3f4f6; overflow: hidden; }
        .progress-bar { transition: width 0.6s cubic-bezier(0.4,0,0.2,1); border-radius: 6px; }

        /* ── Filter bar ──────────────────────────────────────────── */
        .filter-bar {
            display: flex; align-items: center; gap: 8px;
            flex-wrap: wrap; margin-bottom: 20px;
        }
        .filter-bar .filter-select {
            background: #fff; border: 1.5px solid #e5e7eb;
            border-radius: 9px; padding: 7px 12px; font-size: 0.78rem;
            color: #374151; cursor: pointer; outline: none;
            transition: border-color 0.2s;
        }
        .filter-bar .filter-select:focus { border-color: #4c8bf5; }
        .filter-pill {
            padding: 6px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 500;
            border: 1.5px solid #e5e7eb; background: #fff; color: #6b7280;
            cursor: pointer; transition: all 0.15s; white-space: nowrap;
        }
        .filter-pill:hover, .filter-pill.active {
            background: #4c8bf5; border-color: #4c8bf5; color: #fff;
        }
        .filter-pill.active-all { background: #1a1d23; border-color: #1a1d23; color: #fff; }

        /* ── Chart container ─────────────────────────────────────── */
        .chart-container { position: relative; }
        .chart-legend-custom { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 14px; justify-content: center; }
        .chart-legend-item { display: flex; align-items: center; gap: 6px; font-size: 0.75rem; color: #6b7280; }
        .chart-legend-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }

        /* ── Mini stat trend ─────────────────────────────────────── */
        .trend-badge {
            font-size: 0.65rem; font-weight: 600;
            padding: 3px 7px; border-radius: 20px;
            display: inline-flex; align-items: center; gap: 2px;
        }
        .trend-up   { background: #d1fae5; color: #065f46; }
        .trend-down { background: #fee2e2; color: #991b1b; }
        .trend-flat { background: #f3f4f6; color: #6b7280; }

        /* ── Dropdown menus ──────────────────────────────────────── */
        .dropdown-menu {
            border-radius: 14px;
            border: 1px solid rgba(0,0,0,0.08);
            box-shadow: 0 4px 6px rgba(0,0,0,0.04), 0 12px 30px rgba(0,0,0,0.1);
            padding: 6px;
            font-size: 0.82rem;
        }
        .dropdown-item { border-radius: 8px; padding: 7px 12px; }
        .dropdown-item:hover { background: #f5f6fa; }

        /* ── Empty states ────────────────────────────────────────── */
        .empty-state {
            text-align: center; padding: 36px 20px;
            color: #9ca3af;
        }
        .empty-state i { font-size: 2.5rem; opacity: 0.4; margin-bottom: 10px; display: block; }
        .empty-state p { font-size: 0.82rem; margin: 0; }

        /* ── Responsive ──────────────────────────────────────────── */
        @media (max-width: 768px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.mobile-open { transform: translateX(0); }
            #main-wrapper { margin-left: 0 !important; }
            #page-content { padding: 16px; }
            .stat-card .stat-value { font-size: 1.6rem; }
        }

        /* ── Utilities ───────────────────────────────────────────── */
        .text-truncate-2 {
            display: -webkit-box; -webkit-line-clamp: 2;
            -webkit-box-orient: vertical; overflow: hidden;
        }
        .fw-500 { font-weight: 500; }
        .fw-600 { font-weight: 600; }
        .gap-8 { gap: 8px; }
    </style>

    @stack('styles')
</head>
<body class="{{ session('sidebar_collapsed') ? 'sidebar-collapsed' : '' }}">

<!-- ─────────────── SIDEBAR ─────────────────────────────────── -->
<nav id="sidebar" class="{{ session('sidebar_collapsed') ? 'collapsed' : '' }}">

    <!-- Logo -->
    <div class="sidebar-logo">
        <div class="logo-mark">K</div>
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
            <span class="nav-icon"><i class="bi bi-grid-1x2-fill"></i></span>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('proposals.index') }}" class="nav-link {{ request()->routeIs('proposals*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="bi bi-file-earmark-text"></i></span>
            <span>Proposals</span>
        </a>

        <a href="{{ route('projects.index') }}" class="nav-link {{ request()->routeIs('projects*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="bi bi-folder2-open"></i></span>
            <span>Projects</span>
        </a>

        <a href="{{ route('contacts.index') }}" class="nav-link {{ request()->routeIs('contacts*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="bi bi-person-lines-fill"></i></span>
            <span>Contacts</span>
        </a>

        <div class="nav-section-label">Time & Tasks</div>

        <a href="{{ route('timesheet.index') }}" class="nav-link {{ request()->routeIs('timesheet*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="bi bi-clock"></i></span>
            <span>Timesheet</span>
        </a>

        <a href="{{ route('tasks.mine') }}" class="nav-link {{ request()->routeIs('tasks*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="bi bi-check2-square"></i></span>
            <span>My Tasks</span>
        </a>

        <a href="{{ route('workload.index') }}" class="nav-link {{ request()->routeIs('workload*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="bi bi-bar-chart-steps"></i></span>
            <span>My Pipeline</span>
        </a>

        <a href="{{ route('approvals.index') }}" class="nav-link {{ request()->routeIs('approvals*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="bi bi-shield-check"></i></span>
            <span>Approval Center</span>
        </a>

        <a href="{{ route('schedule.project') }}" class="nav-link {{ request()->routeIs('schedule*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="bi bi-calendar3-range"></i></span>
            <span>Project Schedule</span>
        </a>

        <a href="{{ route('events.index') }}" class="nav-link {{ request()->routeIs('events*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="bi bi-calendar-event"></i></span>
            <span>Calendar</span>
        </a>

        <div class="nav-section-label">Finance</div>

        <a href="{{ route('invoices.index') }}" class="nav-link {{ request()->routeIs('invoices*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="bi bi-receipt"></i></span>
            <span>Invoicing</span>
        </a>

        <div class="nav-section-label">AI Assistant</div>
        <a href="{{ route('ai.index') }}" class="nav-link {{ request()->routeIs('ai*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="bi bi-robot"></i></span>
            <span>Kore AI</span>
        </a>

        @if(auth()->user()->role->name === 'Admin')
        <div class="nav-section-label">System</div>
        <a href="{{ route('admin.index') }}" class="nav-link {{ request()->routeIs('admin*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="bi bi-gear-fill"></i></span>
            <span>Administration</span>
        </a>
        @endif

    </div>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <a href="{{ route('profile') }}" class="nav-link">
            <span class="nav-icon"><i class="bi bi-person-circle"></i></span>
            <span>My Profile</span>
        </a>
        <form action="{{ route('logout') }}" method="POST" class="mt-1">
            @csrf
            <button type="submit" class="nav-link w-100 text-start bg-transparent border-0">
                <span class="nav-icon"><i class="bi bi-box-arrow-left"></i></span>
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
            <button class="header-icon-btn" title="Notifications">
                <i class="bi bi-bell"></i>
            </button>

            <!-- Quick user menu -->
            <div class="dropdown">
                <button class="d-flex align-items-center gap-2 border-0 bg-transparent p-0 cursor-pointer" data-bs-toggle="dropdown" style="cursor:pointer;">
                    <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#4c8bf5,#6c63ff);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.75rem;box-shadow:0 2px 8px rgba(76,139,245,0.3);">
                        {{ strtoupper(substr(auth()->user()->first_name ?? 'U', 0, 1)) }}
                    </div>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li class="px-3 py-2 border-bottom mb-1">
                        <div style="font-weight:600;font-size:0.82rem;color:#1a1d23;">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</div>
                        <div style="font-size:0.72rem;color:#9ca3af;">{{ auth()->user()->role->name ?? 'Employee' }}</div>
                    </li>
                    <li><a class="dropdown-item" href="{{ route('profile') }}"><i class="bi bi-person me-2 text-primary"></i>Profile</a></li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-box-arrow-left me-2"></i>Sign Out
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
