<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} — {{ config('app.name', 'Kore ERP') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ── CSS variables ───────────────────────────────────── */
        :root {
            --sidebar-w: 240px;
            --header-h: 58px;
            --c-bg:      #f7f8fc;
            --c-surface: #ffffff;
            --c-border:  #e8ecf1;
            --c-t1: #0f1117;
            --c-t2: #374151;
            --c-t3: #6b7280;
            --c-t4: #9ca3af;
            --c-accent:       #4c8bf5;
            --c-accent-hover: #3a7ae4;
            --c-accent-light: #eff6ff;
            --shadow-xs: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.06), 0 2px 8px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 14px rgba(0,0,0,0.07), 0 2px 5px rgba(0,0,0,0.04);
            --shadow-lg: 0 10px 36px rgba(0,0,0,0.11), 0 3px 10px rgba(0,0,0,0.06);
            --r-sm: 8px;
            --r-md: 12px;
            --r-lg: 16px;
            --r-xl: 20px;
        }

        /* ── Reset & base ────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { font-size: 15px; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 15px;
            line-height: 1.6;
            background: var(--c-bg);
            color: var(--c-t1);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow-x: hidden;
        }

        /* ── Top header ──────────────────────────────────────── */
        #top-header {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: var(--header-h);
            background: var(--c-surface);
            border-bottom: 1px solid var(--c-border);
            display: flex; align-items: center;
            padding: 0 22px; gap: 12px;
            z-index: 500;
        }

        .hamburger-btn {
            width: 34px; height: 34px;
            display: flex; align-items: center; justify-content: center;
            background: none; border: none; border-radius: var(--r-sm);
            color: var(--c-t3); cursor: pointer;
            transition: background 0.12s, color 0.12s; flex-shrink: 0;
        }
        .hamburger-btn:hover { background: #f2f4f8; color: var(--c-t1); }
        .hamburger-btn i { font-size: 20px; }

        /* ── Header logo ─────────────────────────────────────── */
        .hdr-logo-wrap {
            display: flex; align-items: center; flex-shrink: 0;
            position: relative; width: 36px; height: 36px;
        }
        .hdr-logo-img {
            width: 36px; height: 36px;
            border-radius: 50%;
            object-fit: cover; flex-shrink: 0;
            display: block;
        }
        .hdr-logo-fallback {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, #f5a623 0%, #7b52e8 55%, #2563eb 100%);
            display: none; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 800; color: #fff;
            letter-spacing: -0.5px; flex-shrink: 0;
        }

        /* ── Divider ─────────────────────────────────────────── */
        .hdr-divider {
            width: 1px; height: 22px;
            background: var(--c-border); flex-shrink: 0;
        }

        /* ── Dashboard home link ─────────────────────────────── */
        .hdr-home-link {
            display: inline-flex; align-items: center; gap: 7px;
            text-decoration: none; flex: 1;
            color: var(--c-t1); font-size: 15px; font-weight: 600;
            padding: 5px 10px; border-radius: var(--r-sm);
            transition: background 0.12s, color 0.12s;
            letter-spacing: -0.2px;
        }
        .hdr-home-link i { font-size: 17px; color: var(--c-accent); }
        .hdr-home-link:hover { background: #f2f4f8; color: var(--c-accent); }

        /* ── Header shortcuts bar ────────────────────────────── */
        /* Future: user-configurable quick-access buttons        */
        .hdr-shortcuts {
            display: flex; align-items: center; gap: 4px; flex-shrink: 0;
        }

        .hdr-search { position: relative; }
        .hdr-search input {
            background: var(--c-bg);
            border: 1.5px solid transparent;
            border-radius: 22px;
            padding: 7px 14px 7px 34px;
            font-size: 13.5px; width: 200px; outline: none;
            color: var(--c-t1);
            transition: border-color 0.2s, width 0.3s, background 0.2s;
        }
        .hdr-search input::placeholder { color: var(--c-t4); }
        .hdr-search input:focus {
            border-color: var(--c-accent);
            background: #fff; width: 244px;
            box-shadow: 0 0 0 3px rgba(76,139,245,0.1);
        }
        .hdr-search i {
            position: absolute; left: 11px; top: 50%;
            transform: translateY(-50%); color: var(--c-t4);
            font-size: 13px; pointer-events: none;
        }

        .hdr-icon-btn {
            width: 34px; height: 34px;
            display: flex; align-items: center; justify-content: center;
            background: none; border: none; border-radius: var(--r-sm);
            color: var(--c-t3); cursor: pointer; font-size: 16px;
            transition: background 0.12s, color 0.12s;
        }
        .hdr-icon-btn:hover { background: #f2f4f8; color: var(--c-t1); }

        /* ── Kore AI header button ───────────────────────────── */
        .hdr-ai-btn {
            display: flex; align-items: center; gap: 7px;
            padding: 6px 14px 6px 10px;
            background: linear-gradient(135deg, #6c63ff 0%, #4c8bf5 100%);
            border: none; border-radius: 22px;
            color: #fff; font-size: 13px; font-weight: 600;
            cursor: pointer; white-space: nowrap;
            box-shadow: 0 2px 8px rgba(108,99,255,0.35);
            transition: box-shadow 0.2s ease, transform 0.15s ease, opacity 0.15s;
            text-decoration: none;
        }
        .hdr-ai-btn:hover {
            color: #fff;
            box-shadow: 0 4px 16px rgba(108,99,255,0.45);
            transform: translateY(-1px);
        }
        .hdr-ai-btn i { font-size: 15px; }

        .hdr-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: var(--c-accent);
            color: #fff; font-size: 12px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            border: none; cursor: pointer;
            transition: box-shadow 0.15s;
        }
        .hdr-avatar:hover { box-shadow: 0 2px 10px rgba(76,139,245,0.35); }

        /* ── Nav Drawer — slides from top ────────────────────── */
        #navBackdrop {
            position: fixed; inset: 0;
            background: rgba(10, 12, 20, 0.42);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 1100;
            opacity: 0; pointer-events: none;
            transition: opacity 0.32s ease;
        }
        #navBackdrop.open { opacity: 1; pointer-events: auto; }

        #navDrawer {
            position: fixed;
            top: 0; left: 0; right: 0;
            background: var(--c-surface);
            z-index: 1200;
            transform: translateY(-100%);
            transition: transform 0.38s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 0 0 var(--r-xl) var(--r-xl);
            box-shadow: var(--shadow-lg);
            max-height: 82vh;
            overflow-y: auto;
        }
        #navDrawer.open { transform: translateY(0); }

        .drawer-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 15px 24px;
            border-bottom: 1px solid var(--c-border);
        }
        .drawer-header-left { display: flex; align-items: center; gap: 10px; }
        .drawer-close {
            width: 32px; height: 32px;
            display: flex; align-items: center; justify-content: center;
            background: #f2f4f8; border: none; border-radius: 50%;
            color: var(--c-t3); cursor: pointer; font-size: 14px;
            transition: background 0.12s, color 0.12s;
        }
        .drawer-close:hover { background: #e5e8ef; color: var(--c-t1); }

        .drawer-body {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(195px, 1fr));
            gap: 0 8px;
            padding: 16px 24px 24px;
        }

        .drawer-section { margin-bottom: 6px; }
        .drawer-section-label {
            font-size: 10.5px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1px;
            color: var(--c-t4); padding: 10px 10px 5px;
        }
        .drawer-link {
            display: flex; align-items: center; gap: 12px;
            padding: 9px 10px; border-radius: var(--r-md);
            color: var(--c-t2); text-decoration: none;
            font-size: 14px; font-weight: 500;
            transition: background 0.12s, color 0.12s;
        }
        .drawer-link:hover { background: var(--c-bg); color: var(--c-t1); }
        .drawer-link.active { color: var(--c-accent); }
        .drawer-link-icon {
            width: 34px; height: 34px; border-radius: 9px;
            background: #f2f4f8;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px; color: var(--c-t3); flex-shrink: 0;
            transition: background 0.12s, color 0.12s;
        }
        .drawer-link:hover .drawer-link-icon,
        .drawer-link.active .drawer-link-icon {
            background: var(--c-accent-light);
            color: var(--c-accent);
        }

        /* ── Main wrapper ────────────────────────────────────── */
        #main-wrapper {
            padding-top: var(--header-h);
            min-height: 100vh;
            display: flex; flex-direction: column;
        }

        #page-content { padding: 28px; flex: 1; }

        footer {
            padding: 14px 28px;
            border-top: 1px solid var(--c-border);
            font-size: 12px; color: var(--c-t4);
            background: var(--c-surface);
        }

        /* ── Cards ───────────────────────────────────────────── */
        .kore-card {
            background: var(--c-surface);
            border-radius: var(--r-lg);
            padding: 22px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0,0,0,0.045);
        }
        .kore-card-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 18px; padding-bottom: 14px;
            border-bottom: 1px solid #f0f2f6;
        }
        .kore-card-header h5 {
            font-size: 14.5px; font-weight: 600;
            margin: 0; color: var(--c-t1); letter-spacing: -0.2px;
        }

        /* ── Stat cards ──────────────────────────────────────── */
        .stat-card {
            background: var(--c-surface);
            border-radius: var(--r-lg);
            padding: 22px 22px 18px;
            display: flex; flex-direction: column;
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0,0,0,0.045);
            transition: box-shadow 0.2s ease, transform 0.2s ease;
            position: relative; overflow: hidden;
        }
        .stat-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
        .stat-card-top {
            display: flex; align-items: flex-start; justify-content: space-between;
            margin-bottom: 14px;
        }
        .stat-icon-wrap {
            width: 42px; height: 42px; border-radius: 11px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
        }
        .stat-card .stat-value {
            font-size: 30px; font-weight: 800;
            color: var(--c-t1); line-height: 1;
            letter-spacing: -1.5px; margin-bottom: 4px;
        }
        .stat-card .stat-label { font-size: 13px; color: var(--c-t3); font-weight: 500; }
        .stat-card .stat-sublabel { font-size: 11.5px; color: var(--c-t4); margin-top: 3px; }
        .stat-card::after {
            content: ''; position: absolute;
            top: -24px; right: -24px;
            width: 90px; height: 90px;
            border-radius: 50%; opacity: 0.05;
        }
        .stat-card.accent-blue::after   { background: #4c8bf5; }
        .stat-card.accent-green::after  { background: #22c55e; }
        .stat-card.accent-amber::after  { background: #f59e0b; }
        .stat-card.accent-red::after    { background: #ef4444; }
        .stat-card.accent-purple::after { background: #8b5cf6; }
        .stat-card.accent-cyan::after   { background: #06b6d4; }

        /* ── Badges ──────────────────────────────────────────── */
        .badge {
            font-size: 11.5px; font-weight: 600;
            padding: 3px 10px; border-radius: 20px;
            letter-spacing: 0.1px;
        }
        .badge-approved  { background: #dcfce7; color: #15803d; }
        .badge-pending   { background: #fef9c3; color: #854d0e; }
        .badge-rejected  { background: #fee2e2; color: #dc2626; }
        .badge-active    { background: #dbeafe; color: #1d4ed8; }
        .badge-overdue   { background: #fee2e2; color: #dc2626; }
        .badge-completed { background: #dcfce7; color: #15803d; }
        .badge-draft     { background: #f3f4f6; color: #374151; }
        .badge-sent      { background: #dbeafe; color: #1d4ed8; }
        .badge-paid      { background: #dcfce7; color: #15803d; }

        /* ── Dashboard tabs ──────────────────────────────────── */
        .dashboard-tabs { margin-bottom: 26px; }
        .dashboard-tabs .nav {
            background: var(--c-surface); padding: 4px;
            border-radius: var(--r-md); display: inline-flex;
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0,0,0,0.045); gap: 2px;
        }
        .dashboard-tabs .nav-link {
            color: var(--c-t3); font-size: 13.5px; font-weight: 500;
            padding: 7px 17px; border-radius: 9px; border: none;
            transition: background 0.18s, color 0.18s;
        }
        .dashboard-tabs .nav-link.active {
            background: var(--c-accent); color: #fff;
            box-shadow: 0 2px 8px rgba(76,139,245,0.3);
        }
        .dashboard-tabs .nav-link:hover:not(.active) { background: #f2f4f8; color: var(--c-t1); }

        /* ── Tables ──────────────────────────────────────────── */
        .kore-table { font-size: 13.5px; border-collapse: separate; border-spacing: 0; width: 100%; }
        .kore-table thead th {
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.7px;
            color: var(--c-t4); background: #f7f8fc;
            padding: 10px 16px; border-bottom: 1px solid var(--c-border);
        }
        .kore-table thead th:first-child { border-radius: 8px 0 0 0; }
        .kore-table thead th:last-child  { border-radius: 0 8px 0 0; }
        .kore-table tbody td {
            padding: 12px 16px; vertical-align: middle;
            border-bottom: 1px solid #f4f5f9; font-size: 13.5px;
        }
        .kore-table tbody tr:last-child td { border-bottom: none; }
        .kore-table tbody tr { transition: background 0.1s; }
        .kore-table tbody tr:hover td { background: #f9fafb; }

        /* ── Alerts ──────────────────────────────────────────── */
        .kore-alert {
            border-left: 4px solid; padding: 12px 16px;
            border-radius: 0 var(--r-md) var(--r-md) 0;
            margin-bottom: 14px; font-size: 13.5px;
            animation: alertIn 0.28s ease;
        }
        @keyframes alertIn {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: none; }
        }
        .kore-alert-success { background: #f0fdf4; border-color: #22c55e; color: #15803d; }
        .kore-alert-danger  { background: #fef2f2; border-color: #ef4444; color: #dc2626; }
        .kore-alert-warning { background: #fffbeb; border-color: #f59e0b; color: #92400e; }
        .kore-alert-info    { background: #eff6ff; border-color: #4c8bf5; color: #1d4ed8; }

        /* ── Buttons ─────────────────────────────────────────── */
        .btn { font-size: 13.5px; font-weight: 500; border-radius: var(--r-sm); transition: all 0.15s ease; }
        .btn-sm { padding: 6px 14px; font-size: 12.5px; border-radius: 7px; }
        .btn-primary { background: var(--c-accent); border-color: var(--c-accent); box-shadow: 0 1px 4px rgba(76,139,245,0.25); }
        .btn-primary:hover { background: var(--c-accent-hover); border-color: var(--c-accent-hover); transform: translateY(-1px); box-shadow: 0 3px 10px rgba(76,139,245,0.3); }
        .btn-outline-primary { color: var(--c-accent); border-color: var(--c-accent); }
        .btn-outline-primary:hover { background: var(--c-accent); color: #fff; }
        .btn-outline-secondary { border-color: var(--c-border); color: var(--c-t3); }
        .btn-outline-secondary:hover { background: #f2f4f8; color: var(--c-t1); border-color: #d1d5db; }

        /* ── Filters ─────────────────────────────────────────── */
        .filter-bar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
        .filter-pill {
            padding: 5px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 500;
            border: 1.5px solid var(--c-border); background: var(--c-surface);
            color: var(--c-t3); cursor: pointer; transition: all 0.14s; white-space: nowrap;
        }
        .filter-pill:hover, .filter-pill.active { background: var(--c-accent); border-color: var(--c-accent); color: #fff; }
        .filter-pill.active-all { background: var(--c-t1); border-color: var(--c-t1); color: #fff; }
        .filter-select {
            background: var(--c-surface); border: 1.5px solid var(--c-border);
            border-radius: var(--r-sm); padding: 7px 12px; font-size: 13px;
            color: var(--c-t2); cursor: pointer; outline: none;
        }
        .filter-select:focus { border-color: var(--c-accent); }

        /* ── Trend badges ────────────────────────────────────── */
        .trend-badge {
            font-size: 11.5px; font-weight: 600;
            padding: 2px 8px; border-radius: 20px;
            display: inline-flex; align-items: center; gap: 2px;
        }
        .trend-up   { background: #dcfce7; color: #15803d; }
        .trend-down { background: #fee2e2; color: #dc2626; }
        .trend-flat { background: #f3f5f8; color: var(--c-t3); }

        /* ── Chart helpers ───────────────────────────────────── */
        .chart-legend-custom { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 14px; justify-content: center; }
        .chart-legend-item   { display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--c-t3); }
        .chart-legend-dot    { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }

        /* ── Misc ────────────────────────────────────────────── */
        .dropdown-menu {
            border-radius: 14px; border: 1px solid rgba(0,0,0,0.07);
            box-shadow: var(--shadow-lg); padding: 6px; font-size: 13.5px;
        }
        .dropdown-item { border-radius: var(--r-sm); padding: 8px 14px; }
        .dropdown-item:hover { background: #f2f4f8; }
        .progress { border-radius: 8px; background: #eef0f5; }
        .progress-bar { border-radius: 8px; transition: width 0.7s cubic-bezier(0.4,0,0.2,1); }
        .empty-state { text-align: center; padding: 40px 20px; color: var(--c-t4); }
        .empty-state i { font-size: 2.2rem; opacity: 0.3; display: block; margin-bottom: 10px; }
        .empty-state p { font-size: 13.5px; margin: 0; }
        .fw-500 { font-weight: 500; }
        .fw-600 { font-weight: 600; }

        /* ── Responsive ──────────────────────────────────────── */
        @media (max-width: 640px) {
            #page-content { padding: 16px; }
            .stat-card .stat-value { font-size: 24px; }
            .kore-card { padding: 16px; }
            .drawer-body { grid-template-columns: repeat(2, 1fr); padding: 14px 16px 20px; }
        }
    </style>

    @stack('styles')
</head>
<body>

<!-- ── Nav Drawer — slides from top ──────────────────────────────── -->
<div id="navBackdrop" onclick="closeNavDrawer()"></div>
<div id="navDrawer">
    <div class="drawer-header">
        <div class="drawer-header-left">
            <img src="{{ asset('images/logo.png') }}" alt="Kore ERP" style="width:30px;height:30px;border-radius:50%;object-fit:cover;">
            <span class="brand-name">KORE <em>ERP</em></span>
        </div>
        <button class="drawer-close" onclick="closeNavDrawer()">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="drawer-body">

        <div class="drawer-section">
            <div class="drawer-section-label">Main</div>
            <a href="{{ route('dashboard') }}" class="drawer-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" onclick="closeNavDrawer()">
                <span class="drawer-link-icon"><i class="bi bi-grid-1x2-fill"></i></span> Dashboard
            </a>
            <a href="{{ route('proposals.index') }}" class="drawer-link {{ request()->routeIs('proposals*') ? 'active' : '' }}" onclick="closeNavDrawer()">
                <span class="drawer-link-icon"><i class="bi bi-file-earmark-text"></i></span> Proposals
            </a>
            <a href="{{ route('projects.index') }}" class="drawer-link {{ request()->routeIs('projects*') ? 'active' : '' }}" onclick="closeNavDrawer()">
                <span class="drawer-link-icon"><i class="bi bi-folder2-open"></i></span> Projects
            </a>
            <a href="{{ route('contacts.index') }}" class="drawer-link {{ request()->routeIs('contacts*') ? 'active' : '' }}" onclick="closeNavDrawer()">
                <span class="drawer-link-icon"><i class="bi bi-person-lines-fill"></i></span> Contacts
            </a>
        </div>

        <div class="drawer-section">
            <div class="drawer-section-label">Time &amp; Tasks</div>
            <a href="{{ route('timesheet.index') }}" class="drawer-link {{ request()->routeIs('timesheet*') ? 'active' : '' }}" onclick="closeNavDrawer()">
                <span class="drawer-link-icon"><i class="bi bi-clock"></i></span> Timesheet
            </a>
            <a href="{{ route('tasks.mine') }}" class="drawer-link {{ request()->routeIs('tasks*') ? 'active' : '' }}" onclick="closeNavDrawer()">
                <span class="drawer-link-icon"><i class="bi bi-check2-square"></i></span> My Tasks
            </a>
            <a href="{{ route('workload.index') }}" class="drawer-link {{ request()->routeIs('workload*') ? 'active' : '' }}" onclick="closeNavDrawer()">
                <span class="drawer-link-icon"><i class="bi bi-bar-chart-steps"></i></span> My Pipeline
            </a>
            <a href="{{ route('approvals.index') }}" class="drawer-link {{ request()->routeIs('approvals*') ? 'active' : '' }}" onclick="closeNavDrawer()">
                <span class="drawer-link-icon"><i class="bi bi-shield-check"></i></span> Approval Center
            </a>
        </div>

        <div class="drawer-section">
            <div class="drawer-section-label">Schedule &amp; Finance</div>
            <a href="{{ route('schedule.project') }}" class="drawer-link {{ request()->routeIs('schedule*') ? 'active' : '' }}" onclick="closeNavDrawer()">
                <span class="drawer-link-icon"><i class="bi bi-calendar3-range"></i></span> Project Schedule
            </a>
            <a href="{{ route('events.index') }}" class="drawer-link {{ request()->routeIs('events*') ? 'active' : '' }}" onclick="closeNavDrawer()">
                <span class="drawer-link-icon"><i class="bi bi-calendar-event"></i></span> Calendar
            </a>
            <a href="{{ route('invoices.index') }}" class="drawer-link {{ request()->routeIs('invoices*') ? 'active' : '' }}" onclick="closeNavDrawer()">
                <span class="drawer-link-icon"><i class="bi bi-receipt"></i></span> Invoicing
            </a>
            <a href="{{ route('ai.index') }}" class="drawer-link {{ request()->routeIs('ai*') ? 'active' : '' }}" onclick="closeNavDrawer()">
                <span class="drawer-link-icon"><i class="bi bi-robot"></i></span> Kore AI
            </a>
            @if(auth()->user()->role->name === 'Admin')
            <a href="{{ route('admin.index') }}" class="drawer-link {{ request()->routeIs('admin*') ? 'active' : '' }}" onclick="closeNavDrawer()">
                <span class="drawer-link-icon"><i class="bi bi-gear-fill"></i></span> Administration
            </a>
            @endif
        </div>

        <div class="drawer-section">
            <div class="drawer-section-label">Account</div>
            <a href="{{ route('profile') }}" class="drawer-link" onclick="closeNavDrawer()">
                <span class="drawer-link-icon"><i class="bi bi-person-circle"></i></span> My Profile
            </a>
            <div class="drawer-link" style="cursor:pointer;" onclick="document.getElementById('drawerLogout').submit()">
                <span class="drawer-link-icon"><i class="bi bi-box-arrow-left"></i></span> Sign Out
            </div>
            <form id="drawerLogout" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
        </div>

    </div>
</div>

<!-- ── Main content ───────────────────────────────────────────────── -->
<div id="main-wrapper">

    <header id="top-header">

        {{-- K5 Logo --}}
        <span class="hdr-logo-wrap">
            <img src="{{ asset('images/logo.png') }}" alt="Kore ERP" class="hdr-logo-img"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
            <span class="hdr-logo-fallback">K5</span>
        </span>

        <div class="hdr-divider"></div>

        {{-- Hamburger --}}
        <button class="hamburger-btn" onclick="openNavDrawer()" title="Navigation">
            <i class="bi bi-list"></i>
        </button>

        {{-- Dashboard home link (always visible) --}}
        <a href="{{ route('dashboard') }}" class="hdr-home-link" title="Dashboard">
            <i class="bi bi-house-fill"></i>
            <span class="d-none d-sm-inline">Dashboard</span>
        </a>

        {{-- ── Quick shortcuts bar (user-configurable in future) ── --}}
        <div class="hdr-shortcuts">

            <div class="hdr-search d-none d-md-block">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Search…" id="globalSearch">
            </div>

            <button class="hdr-icon-btn d-md-none" title="Search">
                <i class="bi bi-search"></i>
            </button>

            {{-- Shortcut: Kore AI --}}
            <a href="{{ route('ai.index') }}" class="hdr-ai-btn d-none d-sm-flex {{ request()->routeIs('ai*') ? 'opacity-75' : '' }}">
                <i class="bi bi-robot"></i> Kore AI
            </a>

            <button class="hdr-icon-btn" title="Notifications">
                <i class="bi bi-bell"></i>
            </button>

        </div>

        <div class="dropdown">
            <button class="hdr-avatar" data-bs-toggle="dropdown" title="{{ auth()->user()->first_name }}">
                {{ strtoupper(substr(auth()->user()->first_name ?? 'U', 0, 1)) }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end" style="min-width:200px;">
                <li class="px-3 py-2 border-bottom mb-1">
                    <div style="font-weight:600;font-size:13.5px;color:var(--c-t1);">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</div>
                    <div style="font-size:11.5px;color:var(--c-t4);">{{ auth()->user()->role->name ?? 'Employee' }}</div>
                </li>
                <li><a class="dropdown-item" href="{{ route('profile') }}"><i class="bi bi-person me-2" style="color:var(--c-accent);"></i>Profile</a></li>
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
    </header>

    <!-- Flash messages -->
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
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
        @endif
    </div>

    <main id="page-content">
        @yield('content')
    </main>

    <footer>
        &copy; {{ date('Y') }} {{ config('app.name', 'Kore ERP') }} &mdash; v{{ config('kore.version', '1.0.0') }}
    </footer>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Nav Drawer (slides from top) ──────────────────────────────────
function openNavDrawer() {
    document.getElementById('navDrawer').classList.add('open');
    document.getElementById('navBackdrop').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeNavDrawer() {
    document.getElementById('navDrawer').classList.remove('open');
    document.getElementById('navBackdrop').classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeNavDrawer(); });

// ── Auto-dismiss flash alerts ─────────────────────────────────────
setTimeout(() => {
    document.querySelectorAll('.kore-alert').forEach(el => {
        el.style.transition = 'opacity 0.4s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    });
}, 5000);

// ── CSRF for axios ────────────────────────────────────────────────
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
if (window.axios) window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
</script>

@stack('scripts')
</body>
</html>
