@extends('layouts.admin')

@section('title', 'Admin Dashboard | UBNHS Library Management System')
@section('page-title', 'Admin Dashboard')

@section('content')
    <div class="dashboard-welcome-card">
        <div>
            <span class="dashboard-eyebrow">Library Administration Portal</span>
            <h2>Welcome, {{ session('first_name', 'System') }} {{ session('last_name', 'Admin') }}.</h2>
            <p>Select a module below to manage daily library operations.</p>
        </div>

        <div class="dashboard-text-logo">
            UBNHS
        </div>
    </div>

    <div class="dashboard-module-grid">
        {{-- GROUP 1: ALL ROLES (Admin, Staff, Student Staff) --}}
        <a href="{{ route('admin.borrow.index') }}" class="dashboard-module-card">
            <span class="module-icon">⇢</span>
            <h3>Borrow Book</h3>
            <p>Create borrow transactions for registered library members.</p>
            <span class="dashboard-status available">Available</span>
        </a>

        <a href="{{ route('admin.return.index') }}" class="dashboard-module-card">
            <span class="module-icon">⇠</span>
            <h3>Return Book</h3>
            <p>Process returned, overdue, lost, and damaged book transactions.</p>
            <span class="dashboard-status available">Available</span>
        </a>

        <a href="{{ route('admin.book-list.index') }}" class="dashboard-module-card">
            <span class="module-icon">⌕</span>
            <h3>Book List / Search</h3>
            <p>View and search all books by title, ISBN, author, publisher, genre, and status.</p>
            <span class="dashboard-status available">Available</span>
        </a>

        <a href="{{ route('admin.attendance.index') }}" class="dashboard-module-card">
            <span class="module-icon">◷</span>
            <h3>Attendance Logs</h3>
            <p>Monitor time-in and time-out records for personnel, members, and visitors.</p>
            <span class="dashboard-status available">Available</span>
        </a>

        <a href="{{ route('admin.departments.index') }}" class="dashboard-module-card">
            <span class="module-icon">▦</span>
            <h3>Departments</h3>
            <p>Manage department records assigned to library personnel.</p>
            <span class="dashboard-status available">Available</span>
        </a>

        <a href="{{ route('admin.members.index') }}" class="dashboard-module-card">
            <span class="module-icon">👥</span>
            <h3>Library Members</h3>
            <p>Manage student and faculty library members and details.</p>
            <span class="dashboard-status available">Available</span>
        </a>

        {{-- GROUP 2: ADMIN & STAFF ONLY --}}
        @if (in_array(session('personnel_role'), ['admin', 'staff']))
            <a href="{{ route('admin.audit-trail.index') }}" class="dashboard-module-card">
                <span class="module-icon">◎</span>
                <h3>Audit Trail</h3>
                <p>Track login activity, record changes, transactions, and system actions.</p>
                <span class="dashboard-status available">Available</span>
            </a>
        @endif

        {{-- GROUP 3: STRICTLY ADMIN ONLY --}}
        @if (session('personnel_role') === 'admin')
            <a href="{{ route('admin.books.index') }}" class="dashboard-module-card">
                <span class="module-icon">📚</span>
                <h3>Book Management</h3>
                <p>Manage books, authors, publishers, genres, ISBN, and book statuses.</p>
                <span class="dashboard-status available">Available</span>
            </a>

            <a href="{{ route('admin.reports.index') }}" class="dashboard-module-card">
                <span class="module-icon">▤</span>
                <h3>Reports</h3>
                <p>Generate attendance, book status, personnel, member, visitor, and book reports.</p>
                <span class="dashboard-status available">Available</span>
            </a>

            <a href="{{ route('admin.personnel.index') }}" class="dashboard-module-card">
                <span class="module-icon">☷</span>
                <h3>Library Personnel</h3>
                <p>Manage admin, staff, and student staff accounts.</p>
                <span class="dashboard-status available">Available</span>
            </a>
        @endif
    </div>
@endsection