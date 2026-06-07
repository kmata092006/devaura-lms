<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReportService
{
    private array $bookStatuses = [
        'Available',
        'Borrowed',
        'Reserved',
        'Lost',
        'Damaged',
        'Under Repair',
    ];

    private array $borrowStatuses = [
        'Borrowed',
        'Returned',
        'Overdue',
        'Lost',
    ];

    /**
     * Builds the selected report dataset, metadata, and filter options.
     */
    public function getReportPageData(array $requestFilters): array
    {
        $reportTypes = $this->reportTypes();

        $selectedReport = $requestFilters['report_type'] ?? 'attendance_logs';

        if (!array_key_exists($selectedReport, $reportTypes)) {
            $selectedReport = 'attendance_logs';
        }

        $filters = $this->normalizeFilters($requestFilters, $selectedReport);

        [$columns, $rows] = match ($selectedReport) {
            'book_status' => $this->bookStatusReport($filters),
            'library_personnel' => $this->libraryPersonnelReport($filters),
            'library_members' => $this->libraryMembersReport($filters),
            'library_visitors' => $this->libraryVisitorsReport($filters),
            'book_genre' => $this->bookGenreReport($filters),
            'book_author' => $this->bookAuthorReport($filters),
            'book_publisher' => $this->bookPublisherReport($filters),
            'book_list' => $this->bookListReport($filters),
            default => $this->attendanceLogsReport($filters),
        };

        return [
            'reportTypes' => $reportTypes,
            'selectedReport' => $selectedReport,
            'reportTitle' => $reportTypes[$selectedReport],
            'filters' => $filters,
            'columns' => $columns,
            'rows' => $rows,
            'options' => $this->filterOptions(),
        ];
    }

    /**
     * Normalizes request filters and removes invalid role selections.
     */
    private function normalizeFilters(array $filters, string $selectedReport): array
    {
        $role = trim($filters['role'] ?? '') ?: null;

        if (!$this->isRoleAllowedForReport($selectedReport, $role)) {
            $role = null;
        }

        return [
            'report_type' => $selectedReport,
            'search' => trim($filters['search'] ?? '') ?: null,
            'role' => $role,
            'status' => trim($filters['status'] ?? '') ?: null,
            'genre_id' => trim($filters['genre_id'] ?? '') ?: null,
            'author_id' => trim($filters['author_id'] ?? '') ?: null,
            'publisher_id' => trim($filters['publisher_id'] ?? '') ?: null,
            'date_from' => trim($filters['date_from'] ?? '') ?: null,
            'date_to' => trim($filters['date_to'] ?? '') ?: null,
        ];
    }

    /**
     * Checks whether a role filter belongs to the selected report type.
     */
    private function isRoleAllowedForReport(string $selectedReport, ?string $role): bool
    {
        if (!$role) {
            return true;
        }

        $roleGroups = [
            'attendance_logs' => [
                'admin',
                'staff',
                'student staff',
                'student',
                'faculty',
                'visitor',
            ],
            'library_personnel' => [
                'admin',
                'staff',
                'student staff',
            ],
            'library_members' => [
                'student',
                'faculty',
            ],
        ];

        if (!array_key_exists($selectedReport, $roleGroups)) {
            return false;
        }

        return in_array($role, $roleGroups[$selectedReport], true);
    }

    /**
     * Defines the report types shown in the reports module.
     */
    private function reportTypes(): array
    {
        return [
            'attendance_logs' => 'Attendance Logs',
            'book_status' => 'Book Status',
            'library_personnel' => 'Library Personnel',
            'library_members' => 'Library Members',
            'library_visitors' => 'Library Visitors',
            'book_genre' => 'Book Genre',
            'book_author' => 'Book Author',
            'book_publisher' => 'Book Publisher',
            'book_list' => 'Book List',
        ];
    }

    /**
     * Builds dropdown options shared by report filters.
     */
    private function filterOptions(): array
    {
        return [
            'roleGroups' => [
                'attendance_logs' => [
                    'admin',
                    'staff',
                    'student staff',
                    'student',
                    'faculty',
                    'visitor',
                ],
                'library_personnel' => [
                    'admin',
                    'staff',
                    'student staff',
                ],
                'library_members' => [
                    'student',
                    'faculty',
                ],
            ],
            'statuses' => [
                'Active',
                'Completed',
                'Available',
                'Borrowed',
                'Returned',
                'Overdue',
                'Reserved',
                'Lost',
                'Damaged',
                'Under Repair',
            ],
            'genres' => DB::table('book_genre')
                ->select('genre_id', 'genre_name')
                ->orderBy('genre_name')
                ->get(),
            'authors' => DB::table('book_author as author')
                ->select(
                    'author.author_id',
                    DB::raw($this->fullNameSql('author', 'Unnamed Author') . ' as author_name')
                )
                ->orderBy('author_name')
                ->get(),
            'publishers' => DB::table('book_publisher as publisher')
                ->select(
                    'publisher.publisher_id',
                    DB::raw($this->fullNameSql('publisher', 'Unnamed Publisher') . ' as publisher_name')
                )
                ->orderBy('publisher_name')
                ->get(),
        ];
    }

    /**
     * Builds the attendance logs report with person names and active status.
     */
    private function attendanceLogsReport(array $filters): array
    {
        $personnelName = $this->fullNameSql('personnel', '-');
        $memberName = $this->fullNameSql('member', '-');
        $visitorName = $this->fullNameSql('visitor', '-');

        $columns = [
            'log_id' => 'Log ID',
            'person_type' => 'Person Type',
            'role' => 'Role',
            'name' => 'Name',
            'reference_number' => 'Reference',
            'log_date' => 'Date',
            'time_in' => 'Time In',
            'time_out' => 'Time Out',
            'record_status' => 'Status',
        ];

        $query = DB::table('attendance_log')
            ->leftJoin('library_personnel as personnel', 'attendance_log.personnel_id', '=', 'personnel.personnel_id')
            ->leftJoin('library_member as member', 'attendance_log.member_id', '=', 'member.member_id')
            ->leftJoin('library_visitor as visitor', 'attendance_log.visitor_id', '=', 'visitor.visitor_id')
            ->select(
                'attendance_log.log_id',
                DB::raw("
                    CASE
                        WHEN attendance_log.personnel_id IS NOT NULL THEN 'Personnel'
                        WHEN attendance_log.member_id IS NOT NULL THEN 'Member'
                        ELSE 'Visitor'
                    END as person_type
                "),
                'attendance_log.role',
                DB::raw("
                    CASE
                        WHEN attendance_log.personnel_id IS NOT NULL THEN {$personnelName}
                        WHEN attendance_log.member_id IS NOT NULL THEN {$memberName}
                        ELSE {$visitorName}
                    END as name
                "),
                DB::raw("COALESCE(member.reference_number, '-') as reference_number"),
                'attendance_log.log_date',
                'attendance_log.time_in',
                DB::raw("COALESCE(TIME_FORMAT(attendance_log.time_out, '%H:%i:%s'), '-') as time_out"),
                DB::raw("
                    CASE
                        WHEN attendance_log.time_out IS NULL THEN 'Active'
                        ELSE 'Completed'
                    END as record_status
                ")
            );

        if ($filters['search']) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search, $personnelName, $memberName, $visitorName) {
                $q->where('attendance_log.log_id', 'like', "%{$search}%")
                    ->orWhere('attendance_log.role', 'like', "%{$search}%")
                    ->orWhere('attendance_log.log_date', 'like', "%{$search}%")
                    ->orWhere('attendance_log.time_in', 'like', "%{$search}%")
                    ->orWhere('attendance_log.time_out', 'like', "%{$search}%")
                    ->orWhere('member.reference_number', 'like', "%{$search}%")
                    ->orWhereRaw("{$personnelName} LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("{$memberName} LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("{$visitorName} LIKE ?", ["%{$search}%"]);
            });
        }

        if ($filters['role']) {
            $query->where('attendance_log.role', $filters['role']);
        }

        if ($filters['status'] === 'Active') {
            $query->whereNull('attendance_log.time_out');
        }

        if ($filters['status'] === 'Completed') {
            $query->whereNotNull('attendance_log.time_out');
        }

        $this->applyDateRange($query, 'attendance_log.log_date', $filters);

        return [
            $columns,
            $query
                ->orderByDesc('attendance_log.log_date')
                ->orderByDesc('attendance_log.time_in')
                ->paginate(10)
                ->withQueryString(),
        ];
    }

    /**
     * Builds the book status report with latest borrow transaction details.
     */
    private function bookStatusReport(array $filters): array
    {
        $authorName = $this->fullNameSql('author', '-');
        $publisherName = $this->fullNameSql('publisher', '-');
        $memberName = $this->fullNameSql('member', '-');

        $columns = [
            'book_id' => 'Book ID',
            'book_title' => 'Title',
            'isbn' => 'ISBN',
            'genre_name' => 'Genre',
            'author_name' => 'Author',
            'publisher_name' => 'Publisher',
            'book_status' => 'Current Book Status',
            'latest_transaction_status' => 'Latest Transaction Status',
            'borrower_name' => 'Latest Borrower',
            'borrow_date' => 'Borrow Date',
            'due_date' => 'Due Date',
            'return_date' => 'Return Date',
        ];

        $latestTransaction = DB::table('borrow_transaction')
            ->select('book_id', DB::raw('MAX(transaction_id) as latest_transaction_id'))
            ->groupBy('book_id');

        $query = DB::table('book')
            ->leftJoin('book_genre as genre', 'book.genre_id', '=', 'genre.genre_id')
            ->leftJoin('book_author as author', 'book.author_id', '=', 'author.author_id')
            ->leftJoin('book_publisher as publisher', 'book.publisher_id', '=', 'publisher.publisher_id')
            ->leftJoinSub($latestTransaction, 'latest', function ($join) {
                $join->on('book.book_id', '=', 'latest.book_id');
            })
            ->leftJoin('borrow_transaction as transaction', 'transaction.transaction_id', '=', 'latest.latest_transaction_id')
            ->leftJoin('library_member as member', 'transaction.member_id', '=', 'member.member_id')
            ->select(
                'book.book_id',
                'book.book_title',
                DB::raw("COALESCE(book.isbn, '-') as isbn"),
                DB::raw("COALESCE(genre.genre_name, '-') as genre_name"),
                DB::raw("{$authorName} as author_name"),
                DB::raw("{$publisherName} as publisher_name"),
                'book.book_status',
                DB::raw("COALESCE(transaction.borrow_status, 'No Transaction') as latest_transaction_status"),
                DB::raw("{$memberName} as borrower_name"),
                DB::raw("COALESCE(CAST(transaction.borrow_date AS CHAR), '-') as borrow_date"),
                DB::raw("COALESCE(CAST(transaction.due_date AS CHAR), '-') as due_date"),
                DB::raw("COALESCE(CAST(transaction.return_date AS CHAR), '-') as return_date")
            );

        if ($filters['search']) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search, $authorName, $publisherName, $memberName) {
                $q->where('book.book_id', 'like', "%{$search}%")
                    ->orWhere('book.book_title', 'like', "%{$search}%")
                    ->orWhere('book.isbn', 'like', "%{$search}%")
                    ->orWhere('book.book_status', 'like', "%{$search}%")
                    ->orWhere('transaction.borrow_status', 'like', "%{$search}%")
                    ->orWhere('genre.genre_name', 'like', "%{$search}%")
                    ->orWhereRaw("{$authorName} LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("{$publisherName} LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("{$memberName} LIKE ?", ["%{$search}%"]);
            });
        }

        if ($filters['status']) {
            $query->where(function ($q) use ($filters) {
                $q->where('book.book_status', $filters['status'])
                    ->orWhere('transaction.borrow_status', $filters['status']);
            });
        }

        if ($filters['genre_id']) {
            $query->where('book.genre_id', $filters['genre_id']);
        }

        if ($filters['author_id']) {
            $query->where('book.author_id', $filters['author_id']);
        }

        if ($filters['publisher_id']) {
            $query->where('book.publisher_id', $filters['publisher_id']);
        }

        $this->applyDateRange($query, 'transaction.borrow_date', $filters);

        return [
            $columns,
            $query
                ->orderBy('book.book_title')
                ->paginate(10)
                ->withQueryString(),
        ];
    }

    /**
     * Builds the personnel report grouped by role and name.
     */
    private function libraryPersonnelReport(array $filters): array
    {
        $personnelName = $this->fullNameSql('personnel', '-');

        $columns = [
            'personnel_id' => 'Personnel ID',
            'username' => 'Username',
            'personnel_role' => 'Role',
            'name' => 'Name',
            'department_name' => 'Department',
            'shift_day' => 'Shift Day',
            'shift_period' => 'Shift Period',
        ];

        $query = DB::table('library_personnel as personnel')
            ->leftJoin('department', 'personnel.department_id', '=', 'department.department_id')
            ->select(
                'personnel.personnel_id',
                'personnel.username',
                'personnel.personnel_role',
                DB::raw("{$personnelName} as name"),
                DB::raw("COALESCE(department.department_name, '-') as department_name"),
                DB::raw("COALESCE(personnel.shift_day, '-') as shift_day"),
                DB::raw("COALESCE(personnel.shift_period, '-') as shift_period")
            );

        if ($filters['search']) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search, $personnelName) {
                $q->where('personnel.personnel_id', 'like', "%{$search}%")
                    ->orWhere('personnel.username', 'like', "%{$search}%")
                    ->orWhere('personnel.personnel_role', 'like', "%{$search}%")
                    ->orWhereRaw("{$personnelName} LIKE ?", ["%{$search}%"])
                    ->orWhere('department.department_name', 'like', "%{$search}%");
            });
        }

        if ($filters['role']) {
            $query->where('personnel.personnel_role', $filters['role']);
        }

        return [
            $columns,
            $query
                ->orderBy('personnel.personnel_role')
                ->orderBy('personnel.last_name')
                ->paginate(10)
                ->withQueryString(),
        ];
    }

    /**
     * Builds the library members report with role and reference data.
     */
    private function libraryMembersReport(array $filters): array
    {
        $memberName = $this->fullNameSql('member', '-');

        $columns = [
            'member_id' => 'Member ID',
            'reference_number' => 'Reference Number',
            'member_role' => 'Role',
            'name' => 'Name',
        ];

        $query = DB::table('library_member as member')
            ->select(
                'member.member_id',
                'member.reference_number',
                'member.member_role',
                DB::raw("{$memberName} as name")
            );

        if ($filters['search']) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search, $memberName) {
                $q->where('member.member_id', 'like', "%{$search}%")
                    ->orWhere('member.reference_number', 'like', "%{$search}%")
                    ->orWhere('member.member_role', 'like', "%{$search}%")
                    ->orWhereRaw("{$memberName} LIKE ?", ["%{$search}%"]);
            });
        }

        if ($filters['role']) {
            $query->where('member.member_role', $filters['role']);
        }

        return [
            $columns,
            $query
                ->orderBy('member.member_role')
                ->orderBy('member.last_name')
                ->paginate(10)
                ->withQueryString(),
        ];
    }

    /**
     * Builds the visitor report with visit totals and latest visit date.
     */
    private function libraryVisitorsReport(array $filters): array
    {
        $visitorName = $this->fullNameSql('visitor', '-');

        $columns = [
            'visitor_id' => 'Visitor ID',
            'name' => 'Name',
            'total_visits' => 'Total Visits',
            'last_visit_date' => 'Last Visit Date',
        ];

        $visitStats = DB::table('attendance_log')
            ->select(
                'visitor_id',
                DB::raw('COUNT(*) as total_visits'),
                DB::raw('MAX(log_date) as last_visit_date')
            )
            ->whereNotNull('visitor_id')
            ->groupBy('visitor_id');

        $query = DB::table('library_visitor as visitor')
            ->leftJoinSub($visitStats, 'stats', function ($join) {
                $join->on('visitor.visitor_id', '=', 'stats.visitor_id');
            })
            ->select(
                'visitor.visitor_id',
                DB::raw("{$visitorName} as name"),
                DB::raw("COALESCE(stats.total_visits, 0) as total_visits"),
                DB::raw("COALESCE(CAST(stats.last_visit_date AS CHAR), '-') as last_visit_date")
            );

        if ($filters['search']) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search, $visitorName) {
                $q->where('visitor.visitor_id', 'like', "%{$search}%")
                    ->orWhereRaw("{$visitorName} LIKE ?", ["%{$search}%"]);
            });
        }

        $this->applyDateRange($query, 'stats.last_visit_date', $filters);

        return [
            $columns,
            $query
                ->orderByDesc('stats.last_visit_date')
                ->paginate(10)
                ->withQueryString(),
        ];
    }

    /**
     * Builds the genre summary report with book counts.
     */
    private function bookGenreReport(array $filters): array
    {
        $columns = [
            'genre_id' => 'Genre ID',
            'genre_name' => 'Genre',
            'total_books' => 'Total Books',
        ];

        $query = DB::table('book_genre as genre')
            ->leftJoin('book', 'genre.genre_id', '=', 'book.genre_id')
            ->select(
                'genre.genre_id',
                'genre.genre_name',
                DB::raw('COUNT(book.book_id) as total_books')
            )
            ->groupBy('genre.genre_id', 'genre.genre_name');

        if ($filters['search']) {
            $query->where('genre.genre_name', 'like', "%{$filters['search']}%");
        }

        return [
            $columns,
            $query
                ->orderBy('genre.genre_name')
                ->paginate(10)
                ->withQueryString(),
        ];
    }

    /**
     * Builds the author summary report with book counts.
     */
    private function bookAuthorReport(array $filters): array
    {
        $authorName = $this->fullNameSql('author', 'Unnamed Author');

        $columns = [
            'author_id' => 'Author ID',
            'author_name' => 'Author',
            'total_books' => 'Total Books',
        ];

        $query = DB::table('book_author as author')
            ->leftJoin('book', 'author.author_id', '=', 'book.author_id')
            ->select(
                'author.author_id',
                DB::raw("{$authorName} as author_name"),
                DB::raw('COUNT(book.book_id) as total_books')
            )
            ->groupBy(
                'author.author_id',
                'author.first_name',
                'author.last_name',
                'author.m.i.'
            );

        if ($filters['search']) {
            $query->whereRaw("{$authorName} LIKE ?", ["%{$filters['search']}%"]);
        }

        return [
            $columns,
            $query
                ->orderBy('author_name')
                ->paginate(10)
                ->withQueryString(),
        ];
    }

    /**
     * Builds the publisher summary report with publication dates and counts.
     */
    private function bookPublisherReport(array $filters): array
    {
        $publisherName = $this->fullNameSql('publisher', 'Unnamed Publisher');

        $columns = [
            'publisher_id' => 'Publisher ID',
            'publisher_name' => 'Publisher',
            'date_published' => 'Date Published',
            'total_books' => 'Total Books',
        ];

        $query = DB::table('book_publisher as publisher')
            ->leftJoin('book', 'publisher.publisher_id', '=', 'book.publisher_id')
            ->select(
                'publisher.publisher_id',
                DB::raw("{$publisherName} as publisher_name"),
                DB::raw("COALESCE(CAST(publisher.date_published AS CHAR), '-') as date_published"),
                DB::raw('COUNT(book.book_id) as total_books')
            )
            ->groupBy(
                'publisher.publisher_id',
                'publisher.first_name',
                'publisher.last_name',
                'publisher.m.i.',
                'publisher.date_published'
            );

        if ($filters['search']) {
            $query->whereRaw("{$publisherName} LIKE ?", ["%{$filters['search']}%"]);
        }

        $this->applyDateRange($query, 'publisher.date_published', $filters);

        return [
            $columns,
            $query
                ->orderBy('publisher_name')
                ->paginate(10)
                ->withQueryString(),
        ];
    }

    /**
     * Builds the detailed book catalog report.
     */
    private function bookListReport(array $filters): array
    {
        $authorName = $this->fullNameSql('author', '-');
        $publisherName = $this->fullNameSql('publisher', '-');

        $columns = [
            'book_id' => 'Book ID',
            'book_title' => 'Title',
            'isbn' => 'ISBN',
            'genre_name' => 'Genre',
            'author_name' => 'Author',
            'publisher_name' => 'Publisher',
            'date_published' => 'Date Published',
            'book_status' => 'Status',
        ];

        $query = DB::table('book')
            ->leftJoin('book_genre as genre', 'book.genre_id', '=', 'genre.genre_id')
            ->leftJoin('book_author as author', 'book.author_id', '=', 'author.author_id')
            ->leftJoin('book_publisher as publisher', 'book.publisher_id', '=', 'publisher.publisher_id')
            ->select(
                'book.book_id',
                'book.book_title',
                DB::raw("COALESCE(book.isbn, '-') as isbn"),
                DB::raw("COALESCE(genre.genre_name, '-') as genre_name"),
                DB::raw("{$authorName} as author_name"),
                DB::raw("{$publisherName} as publisher_name"),
                DB::raw("COALESCE(CAST(publisher.date_published AS CHAR), '-') as date_published"),
                'book.book_status'
            );

        if ($filters['search']) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search, $authorName, $publisherName) {
                $q->where('book.book_id', 'like', "%{$search}%")
                    ->orWhere('book.book_title', 'like', "%{$search}%")
                    ->orWhere('book.isbn', 'like', "%{$search}%")
                    ->orWhere('book.book_status', 'like', "%{$search}%")
                    ->orWhere('genre.genre_name', 'like', "%{$search}%")
                    ->orWhereRaw("{$authorName} LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("{$publisherName} LIKE ?", ["%{$search}%"]);
            });
        }

        if ($filters['status'] && in_array($filters['status'], $this->bookStatuses, true)) {
            $query->where('book.book_status', $filters['status']);
        }

        if ($filters['genre_id']) {
            $query->where('book.genre_id', $filters['genre_id']);
        }

        if ($filters['author_id']) {
            $query->where('book.author_id', $filters['author_id']);
        }

        if ($filters['publisher_id']) {
            $query->where('book.publisher_id', $filters['publisher_id']);
        }

        return [
            $columns,
            $query
                ->orderBy('book.book_title')
                ->paginate(10)
                ->withQueryString(),
        ];
    }

    /**
     * Applies optional start and end date filters to a query.
     */
    private function applyDateRange($query, string $column, array $filters): void
    {
        if ($filters['date_from']) {
            $query->whereDate($column, '>=', $filters['date_from']);
        }

        if ($filters['date_to']) {
            $query->whereDate($column, '<=', $filters['date_to']);
        }
    }

    /**
     * Builds a reusable SQL expression for formatted names.
     */
    private function fullNameSql(string $alias, string $fallback = '-'): string
    {
        $nameExpression = "
            TRIM(
                CONCAT(
                    COALESCE({$alias}.first_name, ''),
                    ' ',
                    CASE
                        WHEN {$alias}.`m.i.` IS NOT NULL AND {$alias}.`m.i.` <> ''
                        THEN CONCAT({$alias}.`m.i.`, '. ')
                        ELSE ''
                    END,
                    COALESCE({$alias}.last_name, '')
                )
            )
        ";

        return "COALESCE(NULLIF({$nameExpression}, ''), '{$fallback}')";
    }
}
