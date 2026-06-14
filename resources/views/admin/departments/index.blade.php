@extends('layouts.admin')

@section('title', 'Departments')
@section('page-title', 'Departments')

@section('content')
    {{-- Provides department management actions and navigation. --}}
    <div class="page-actions department-header-card">
        <div>
            <h2>Department Records</h2>
            <p>Manage departments assigned to library personnel.</p>
        </div>

        <a href="{{ route('admin.departments.create') }}" class="action-button">
            Add Department
        </a>
    </div>

    @if (session('success'))
        <div class="alert success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert error">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Filters department records by name. --}}
    <form method="GET" action="{{ route('admin.departments.index') }}" class="department-filter-card">
        <div class="department-search-field">
            <label for="search">Search Department</label>
            <input
                type="text"
                name="search"
                id="search"
                value="{{ request('search') }}"
                placeholder="Search by department name"
            >
        </div>

        <div class="department-filter-actions">
            <button type="submit" class="department-search-button">
                Search
            </button>

            <a href="{{ route('admin.departments.index') }}" class="department-reset-button">
                Reset
            </a>
        </div>
    </form>

    <div class="system-records-shell department-records-shell">
        <div class="system-records-meta">
            <div>
                <h3>Department Records</h3>
                <p>{{ $departments->total() }} {{ \Illuminate\Support\Str::plural('department', $departments->total()) }} found</p>
            </div>
        </div>

        <div class="system-record-list">
            @forelse ($departments as $department)
                <article class="system-record-card">
                    <header class="system-record-header">
                        <div class="system-record-identity">
                            <div class="system-record-avatar is-violet" aria-hidden="true">DP</div>
                            <div class="system-record-copy">
                                <span class="system-record-eyebrow">Department</span>
                                <h3>{{ $department->department_name }}</h3>
                                <p>Library personnel assignment group</p>
                            </div>
                        </div>

                        <div class="system-record-badges">
                            <span class="system-record-badge">
                                {{ $department->personnel_count }} {{ \Illuminate\Support\Str::plural('personnel', $department->personnel_count) }}
                            </span>
                        </div>
                    </header>

                    <div class="system-record-body department-record-grid">
                        <div class="system-record-field is-primary">
                            <span>Department Name</span>
                            <strong>{{ $department->department_name }}</strong>
                        </div>

                        <div class="system-record-field">
                            <span>Personnel Count</span>
                            <strong>{{ $department->personnel_count }}</strong>
                        </div>

                        <div class="system-record-actions-panel">
                            <span class="system-record-section-label">Actions</span>
                            {{-- Department deletion is still checked server-side for assigned personnel. --}}
                            <div class="system-record-actions department-row-actions">
                                <a href="{{ route('admin.departments.edit', $department->department_id) }}" class="department-edit-button">Edit</a>
                                <form method="POST" action="{{ route('admin.departments.destroy', $department->department_id) }}" onsubmit="return confirm('Delete this department?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="department-delete-button">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="system-record-empty">
                    <strong>No departments found</strong>
                    Add a department or adjust the search term.
                </div>
            @endforelse
        </div>
    </div>

    <div class="pagination-wrapper">
        {{ $departments->links() }}
    </div>
@endsection
