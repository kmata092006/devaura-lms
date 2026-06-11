<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'UBNHS Library Management System')</title>
    <link rel="stylesheet" href="{{ asset('css/lms.css') }}?v={{ filemtime(public_path('css/lms.css')) }}">
</head>
<body class="ubnhs-admin-body">
    @php
        $roleName = session('personnel_role');
        $roleService = app(\App\Services\RoleService::class);
    @endphp

    <div class="admin-shell">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="sidebar-text-logo">UBNHS</div>
                <div><h1>UBNHS Library</h1><p>Management System</p></div>
            </div>

            <nav class="sidebar-nav">
                @if ($roleService->hasPermission($roleName, 'Dashboard'))
                    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <span class="nav-icon">⌂</span>Dashboard</a>
                @endif

                @if ($roleService->hasPermission($roleName, 'Attendance Logs'))
                    <a href="{{ route('admin.attendance.index') }}" class="{{ request()->routeIs('admin.attendance.*') ? 'active' : '' }}">
                        <span class="nav-icon">◷</span>Attendance Logs</a>
                @endif

                @if ($roleService->hasPermission($roleName, 'Departments'))
                    <a href="{{ route('admin.departments.index') }}" class="{{ request()->routeIs('admin.departments.*') ? 'active' : '' }}">
                        <span class="nav-icon">▦</span>Departments</a>
                @endif

                @if ($roleService->hasPermission($roleName, 'Library Personnel'))
                    <a href="{{ route('admin.personnel.index') }}" class="{{ request()->routeIs('admin.personnel.*') ? 'active' : '' }}">
                        <span class="nav-icon">☷</span>Library Personnel</a>
                @endif

                @if ($roleService->hasPermission($roleName, 'Library Members'))
                    <a href="{{ route('admin.members.index') }}" class="{{ request()->routeIs('admin.members.*') ? 'active' : '' }}">
                        <span class="nav-icon">◉</span>Library Members</a>
                @endif

                @if ($roleService->hasPermission($roleName, 'Book Management'))
                    <a href="{{ route('admin.books.index') }}" class="{{ request()->routeIs('admin.books.*') ? 'active' : '' }}">
                        <span class="nav-icon">▣</span>Book Management</a>
                @endif

                @if ($roleService->hasPermission($roleName, 'Borrow Book'))
                    <a href="{{ route('admin.borrow.index') }}" class="{{ request()->routeIs('admin.borrow.*') ? 'active' : '' }}">
                        <span class="nav-icon">⇢</span>Borrow Book</a>
                @endif

                @if ($roleService->hasPermission($roleName, 'Return Book'))
                    <a href="{{ route('admin.return.index') }}" class="{{ request()->routeIs('admin.return.*') ? 'active' : '' }}">
                        <span class="nav-icon">⇠</span>Return Book</a>
                @endif

                @if ($roleService->hasPermission($roleName, 'Book List / Search'))
                    <a href="{{ route('admin.book-list.index') }}" class="{{ request()->routeIs('admin.book-list.*') ? 'active' : '' }}">
                        <span class="nav-icon">⌕</span>Book List / Search</a>
                @endif

                @if ($roleService->hasPermission($roleName, 'Report'))
                    <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                        <span class="nav-icon">▤</span>Reports</a>
                @endif

                @if ($roleService->hasPermission($roleName, 'Audit Trail'))
                    <a href="{{ route('admin.audit-trail.index') }}" class="{{ request()->routeIs('admin.audit-trail.*') ? 'active' : '' }}">
                        <span class="nav-icon">◎</span>Audit Trail</a>
                @endif

                @if ($roleService->hasPermission($roleName, 'Role Permission'))
                    <a href="{{ route('admin.roles.index') }}" class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                        <span class="nav-icon">⚙</span>Role Permissions</a>
                @endif
            </nav>

            <form method="POST" action="{{ route('logout') }}" class="logout-form">
                @csrf
                <button type="submit">Logout</button>
            </form>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <div>
                    <p class="header-eyebrow">Upper Bicutan National High School</p>
                    <h2>@yield('page-title', 'Dashboard')</h2>
                    <p>UBNHS Library Management System</p>
                </div>
                <div class="admin-user-card">
                    <div class="admin-user-avatar">{{ strtoupper(substr(session('first_name', 'S'), 0, 1)) }}{{ strtoupper(substr(session('last_name', 'A'), 0, 1)) }}</div>
                    <div>
                        <strong>{{ session('first_name', 'System') }} {{ session('last_name', 'Admin') }}</strong>
                        <span>{{ ucwords(session('personnel_role', 'Admin')) }}</span>
                    </div>
                </div>
            </header>
            <section class="admin-content">@yield('content')</section>
        </main>
    </div>
</body>
</html>