<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard') | Library Management System</title>
    <link rel="stylesheet" href="{{ asset('css/lms.css') }}">
</head>
<body class="admin-body">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <h2>LMS</h2>
            <p>Admin Panel</p>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="#">Attendance Logs</a>
            <a href="{{ route('admin.members.index') }}" class="{{ request()->routeIs('admin.members.*') ? 'active' : '' }}">Library Members</a>
            <a href="{{ route('admin.books.index') }}" class="{{ request()->routeIs('admin.books.*') ? 'active' : '' }}">Book Management</a>
            <a href="{{ route('admin.borrow.index') }}" class="{{ request()->routeIs('admin.borrow.*') ? 'active' : '' }}">Borrow Book</a>
            <a href="{{ route('admin.return.index') }}" class="{{ request()->routeIs('admin.return.*') ? 'active' : '' }}">Return Book</a>
            <a href="{{ route('admin.book-list.index') }}" class="{{ request()->routeIs('admin.book-list.*') ? 'active' : '' }}">Book List / Search</a>
            <a href="#">Reports</a>
        </nav>

        <form method="POST" action="{{ route('logout') }}" class="logout-form">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <div>
                <h1>@yield('page-title', 'Admin Dashboard')</h1>
                <p>Library Management System</p>
            </div>

            <div class="admin-user">
                <span>{{ session('full_name') }}</span>
                <small>{{ ucfirst(session('personnel_role')) }}</small>
            </div>
        </header>

        <section class="admin-content">
            @yield('content')
        </section>
    </main>
</body>
</html>