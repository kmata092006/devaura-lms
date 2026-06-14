@extends('layouts.admin')

@section('title', 'Reports')
@section('page-title', 'Reports')

@section('content')
    {{-- Identifies the selected report and exposes print output. --}}
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

    @if ($errors->any())
        <div class="alert error report-filter-alert" role="alert">
            <strong>Unable to apply the selected date range.</strong>
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Filters report data; visible fields change with the selected report type. --}}
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

        <div class="report-filter-group report-date-filter" data-filter="date">
            <label for="date_from" id="date-from-label">Date From</label>
            <input
                type="date"
                name="date_from"
                id="date_from"
                value="{{ old('date_from', $filters['date_from']) }}"
                max="{{ old('date_to', $filters['date_to']) ?: now()->toDateString() }}"
                aria-describedby="date-range-help date-from-error"
                class="{{ $errors->has('date_from') ? 'is-invalid' : '' }}"
            >
            @error('date_from')
                <small id="date-from-error" class="report-filter-error">{{ $message }}</small>
            @enderror
        </div>

        <div class="report-filter-group report-date-filter" data-filter="date">
            <label for="date_to" id="date-to-label">Date To</label>
            <input
                type="date"
                name="date_to"
                id="date_to"
                value="{{ old('date_to', $filters['date_to']) }}"
                min="{{ old('date_from', $filters['date_from']) }}"
                max="{{ now()->toDateString() }}"
                aria-describedby="date-range-help date-to-error"
                class="{{ $errors->has('date_to') ? 'is-invalid' : '' }}"
            >
            @error('date_to')
                <small id="date-to-error" class="report-filter-error">{{ $message }}</small>
            @enderror
        </div>

        <div class="report-date-help" data-filter="date" id="date-range-help">
            <strong>Date range</strong>
            <span>Choose any historical period up to today. Leave both fields blank to include all dates.</span>
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

        <div class="system-records-shell report-records-shell">
            <div class="system-records-meta">
                <div>
                    <h3>{{ $reportTitle }}</h3>
                    <p>{{ $rows->total() }} {{ \Illuminate\Support\Str::plural('record', $rows->total()) }} generated</p>
                </div>
            </div>

            <div class="system-record-list">
                @forelse ($rows as $row)
                    @php
                        // Use the first configured column as the report card title.
                        $firstColumnKey = array_key_first($columns);
                        $firstColumnLabel = $columns[$firstColumnKey] ?? 'Record';
                        $firstColumnValue = $row->{$firstColumnKey} ?? '-';
                    @endphp

                    <article class="system-record-card">
                        <header class="system-record-header">
                            <div class="system-record-identity">
                                <div class="system-record-avatar is-violet" aria-hidden="true">RP</div>
                                <div class="system-record-copy">
                                    <span class="system-record-eyebrow">{{ $firstColumnLabel }}</span>
                                    <h3>{{ $firstColumnValue }}</h3>
                                    <p>{{ $reportTitle }}</p>
                                </div>
                            </div>
                        </header>

                        <div class="system-record-body report-record-grid">
                            @foreach ($columns as $key => $label)
                                @continue($key === $firstColumnKey)

                                @php
                                    $value = $row->{$key} ?? '-';
                                @endphp

                                <div class="system-record-field {{ str_contains($key, 'status') ? 'is-muted' : '' }}">
                                    <span>{{ $label }}</span>

                                    @if (str_contains($key, 'status'))
                                        <strong>
                                            <span class="report-status-badge">{{ $value }}</span>
                                        </strong>
                                    @else
                                        <strong>{{ $value }}</strong>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <div class="system-record-empty">
                        <strong>No report records found</strong>
                        Adjust the selected report filters and generate again.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="pagination-wrapper report-pagination">
            {{ $rows->links() }}
        </div>
    </div>

    <script>
        const reportTypeSelect = document.getElementById('report_type');
        const roleSelect = document.getElementById('role');
        const dateFromInput = document.getElementById('date_from');
        const dateToInput = document.getElementById('date_to');
        const dateFromLabel = document.getElementById('date-from-label');
        const dateToLabel = document.getElementById('date-to-label');
        const dateRangeHelp = document.getElementById('date-range-help');
        const today = @json(now()->toDateString());

        const dateFilterContext = {
            attendance_logs: {
                label: 'Attendance Date',
                help: 'Filter attendance records by their log date.',
            },
            book_status: {
                label: 'Borrow Date',
                help: 'Filter book status records by the latest transaction borrow date.',
            },
            library_visitors: {
                label: 'Last Visit Date',
                help: 'Filter visitors by the date of their latest recorded visit.',
            },
            book_publisher: {
                label: 'Publication Date',
                help: 'Filter publishers by the recorded publication date.',
            },
        };

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

        // Converts stored role values into readable dropdown labels.
        function formatRoleLabel(role) {
            return role
                .split(' ')
                .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
        }

        // Rebuilds role options to match the selected report type.
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

        // Shows only the filters that apply to the selected report.
        function updateVisibleReportFilters(resetRole = false) {
            const selectedReport = reportTypeSelect.value;
            const allowedFilters = visibleFilters[selectedReport] || ['search'];

            document.querySelectorAll('[data-filter]').forEach((filter) => {
                const filterName = filter.getAttribute('data-filter');
                const isVisible = allowedFilters.includes(filterName);

                filter.style.display = isVisible ? '' : 'none';

                filter.querySelectorAll('input, select').forEach((control) => {
                    control.disabled = !isVisible;
                });
            });

            const dateContext = dateFilterContext[selectedReport] || {
                label: 'Date',
                help: 'Choose any historical period up to today.',
            };

            dateFromLabel.textContent = `${dateContext.label} From`;
            dateToLabel.textContent = `${dateContext.label} To`;
            dateRangeHelp.innerHTML = `<strong>Date range</strong><span>${dateContext.help} Leave both fields blank to include all dates.</span>`;

            updateRoleOptions(resetRole);
        }

        // Keeps both date fields within a valid historical range.
        function syncDateRange(changedField = null) {
            dateFromInput.max = dateToInput.value || today;
            dateToInput.min = dateFromInput.value || '';
            dateToInput.max = today;

            if (
                changedField
                && dateFromInput.value
                && dateToInput.value
                && dateFromInput.value > dateToInput.value
            ) {
                if (changedField === 'from') {
                    dateToInput.value = dateFromInput.value;
                } else {
                    dateFromInput.value = dateToInput.value;
                }

                dateFromInput.max = dateToInput.value || today;
                dateToInput.min = dateFromInput.value || '';
            }
        }

        dateFromInput.addEventListener('change', () => syncDateRange('from'));
        dateToInput.addEventListener('change', () => syncDateRange('to'));

        reportTypeSelect.addEventListener('change', () => {
            roleSelect.setAttribute('data-selected-role', '');
            updateVisibleReportFilters(true);
        });

        syncDateRange();
        updateVisibleReportFilters(false);
    </script>
@endsection
