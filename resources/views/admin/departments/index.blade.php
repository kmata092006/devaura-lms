@extends('layouts.admin')

@section('title', 'Departments')
@section('page-title', 'Departments')

@section('content')
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

    <form method="GET" action="{{ route('admin.departments.index') }}" class="department-filter-card">
        <div class="department-search-field">
            <label for="search">Search Department</label>
            <input
                type="text"
                name="search"
                id="search"
                value="{{ request('search') }}"
                placeholder="Search by department ID or department name"
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

    <div class="department-table-card">
        <table class="department-table">
            <thead>
                <tr>
                    <th>Department Name</th>
                    <th>Personnel Count</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($departments as $department)
                    <tr>
                        <td class="department-name-cell">{{ $department->department_name }}</td>
                        <td><span class="department-count-pill">{{ $department->personnel_count }}</span></td>
                        <td>
                            <div class="department-row-actions">
                                <a href="{{ route('admin.departments.edit', $department->department_id) }}" class="department-edit-button">Edit</a>
                                <form method="POST" action="{{ route('admin.departments.destroy', $department->department_id) }}" onsubmit="return confirm('Delete this department?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="department-delete-button">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="department-empty-cell">No departments found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $departments->links() }}
    </div>
@endsection