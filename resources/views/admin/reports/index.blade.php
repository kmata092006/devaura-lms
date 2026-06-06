@extends('layouts.admin')

@section('title', 'Reports')
@section('page-title', 'Reports')

@section('content')
    <div class="report-hero">
        <div>
            <span class="report-eyebrow">Admin Reports</span>
            <h2>{{ $reportTitle }}</h2>
            <p>Generate, filter, review, and print Library Management System records.</p>
        </div>

        <button type="button" class="action-button" onclick="window.print()">
            Print Report
        </button>
    </div>

    <form method="GET" action="{{ route('admin.reports.index') }}" class="report-filter-card">
        <div class="report-filter-group">
            <label for="report_type">Report Type</label>
            <select name="report_type" id="report_type">
                @foreach ($reportTypes as $key => $label)
                    <option value="{{ $key }}" {{ $selectedReport === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="report-filter-group" data-filter="search">
            <label for="search">Search</label>
            <input
                type="text"
                name="search"
                id="search"
                value="{{ $filters['search'] }}"
                placeholder="Search report records"
            >
        </div>

        <div class="report-filter-group" data-filter="role">
            <label for="role">Role</label>
            <select name="role" id="role" data-selected-role="{{ $filters['role'] }}">
                <option value="">All Roles</option>
            </select>
        </div>

        <div class="report-filter-group" data-filter="status">
            <label for="status">Status</label>
            <select name="status" id="status">
                <option value="">All Statuses</option>
                @foreach ($options['statuses'] as $status)
                    <option value="{{ $status }}" {{ $filters['status'] === $status ? 'selected' : '' }}>
                        {{ $status }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="report-filter-group" data-filter="genre">
            <label for="genre_id">Genre</label>
            <select name="genre_id" id="genre_id">
                <option value="">All Genres</option>
                @foreach ($options['genres'] as $genre)
                    <option value="{{ $genre->genre_id }}" {{ (string) $filters['genre_id'] === (string) $genre->genre_id ? 'selected' : '' }}>
                        {{ $genre->genre_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="report-filter-group" data-filter="author">
            <label for="author_id">Author</label>
            <select name="author_id" id="author_id">
                <option value="">All Authors</option>
                @foreach ($options['authors'] as $author)
                    <option value="{{ $author->author_id }}" {{ (string) $filters['author_id'] === (string) $author->author_id ? 'selected' : '' }}>
                        {{ $author->author_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="report-filter-group" data-filter="publisher">
            <label for="publisher_id">Publisher</label>
            <select name="publisher_id" id="publisher_id">
                <option value="">All Publishers</option>
                @foreach ($options['publishers'] as $publisher)
                    <option value="{{ $publisher->publisher_id }}" {{ (string) $filters['publisher_id'] === (string) $publisher->publisher_id ? 'selected' : '' }}>
                        {{ $publisher->publisher_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="report-filter-group" data-filter="date">
            <label for="date_from">Date From</label>
            <input
                type="date"
                name="date_from"
                id="date_from"
                value="{{ $filters['date_from'] }}"
            >
        </div>

        <div class="report-filter-group" data-filter="date">
            <label for="date_to">Date To</label>
            <input
                type="date"
                name="date_to"
                id="date_to"
                value="{{ $filters['date_to'] }}"
            >
        </div>

        <div class="report-filter-actions">
            <button type="submit">Generate</button>
            <a href="{{ route('admin.reports.index') }}" class="secondary-button">Reset</a>
        </div>
    </form>

    <div class="report-print-area">
        <div class="report-print-header">
            <h2>{{ $reportTitle }}</h2>
            <p>Generated on {{ now()->format('F d, Y h:i A') }}</p>
        </div>

        <div class="table-card report-table-card">
            <table class="data-table report-table">
                <thead>
                    <tr>
                        @foreach ($columns as $label)
                            <th>{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            @foreach ($columns as $key => $label)
                                @php
                                    $value = $row->{$key} ?? '-';
                                @endphp

                                <td>
                                    @if (str_contains($key, 'status'))
                                        <span class="report-status-badge">
                                            {{ $value }}
                                        </span>
                                    @else
                                        {{ $value }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) }}" class="empty-table">
                                No report records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrapper report-pagination">
            {{ $rows->links() }}
        </div>
    </div>

    <script>
        const reportTypeSelect = document.getElementById('report_type');
        const roleSelect = document.getElementById('role');

        const visibleFilters = {
            attendance_logs: ['search', 'role', 'status', 'date'],
            book_status: ['search', 'status', 'date', 'genre', 'author', 'publisher'],
            library_personnel: ['search', 'role'],
            library_members: ['search', 'role'],
            library_visitors: ['search', 'date'],
            book_genre: ['search'],
            book_author: ['search'],
            book_publisher: ['search', 'date'],
            book_list: ['search', 'status', 'genre', 'author', 'publisher'],
        };

        const roleGroups = @json($options['roleGroups']);

        function formatRoleLabel(role) {
            return role
                .split(' ')
                .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
        }

        function updateRoleOptions(resetRole = false) {
            const selectedReport = reportTypeSelect.value;
            const selectedRole = resetRole ? '' : roleSelect.getAttribute('data-selected-role');
            const roles = roleGroups[selectedReport] || [];

            roleSelect.innerHTML = '';

            const allOption = document.createElement('option');
            allOption.value = '';
            allOption.textContent = 'All Roles';
            roleSelect.appendChild(allOption);

            roles.forEach((role) => {
                const option = document.createElement('option');
                option.value = role;
                option.textContent = formatRoleLabel(role);

                if (selectedRole === role) {
                    option.selected = true;
                }

                roleSelect.appendChild(option);
            });

            const selectedStillExists = roles.includes(selectedRole);

            if (!selectedStillExists) {
                roleSelect.value = '';
            }
        }

        function updateVisibleReportFilters(resetRole = false) {
            const selectedReport = reportTypeSelect.value;
            const allowedFilters = visibleFilters[selectedReport] || ['search'];

            document.querySelectorAll('[data-filter]').forEach((filter) => {
                const filterName = filter.getAttribute('data-filter');

                if (allowedFilters.includes(filterName)) {
                    filter.style.display = '';
                } else {
                    filter.style.display = 'none';
                }
            });

            updateRoleOptions(resetRole);
        }

        reportTypeSelect.addEventListener('change', () => {
            roleSelect.setAttribute('data-selected-role', '');
            updateVisibleReportFilters(true);
        });

        updateVisibleReportFilters(false);
    </script>
@endsection