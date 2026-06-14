@extends('layouts.admin')

@section('title', 'Return Book')
@section('page-title', 'Return Book')

@section('content')
    {{-- Identifies the return module and links back to borrowing. --}}
    <div class="return-page-header">
        <div class="return-page-heading">
            <span class="return-eyebrow">Book Management</span>
            <h2>Return Book Transaction</h2>
        </div>

        <a href="{{ route('admin.borrow.index') }}" class="return-borrow-link">
            <span aria-hidden="true">&larr;</span>
            Go to Borrow Book
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

    {{-- Summarizes active, overdue, and due-today return work. --}}
    <section class="return-overview" aria-label="Return queue overview">
        <div class="return-overview-copy">
            <span class="return-overview-icon" aria-hidden="true">&#9432;</span>
            <div>
                <h3>Book Status</h3>
            </div>
        </div>

        <div class="return-summary-grid">
            <div class="return-summary-item">
                <span>Active</span>
                <strong>{{ $returnSummary['active'] }}</strong>
            </div>
            <div class="return-summary-item return-summary-overdue">
                <span>Overdue</span>
                <strong>{{ $returnSummary['overdue'] }}</strong>
            </div>
            <div class="return-summary-item return-summary-due">
                <span>Due Today</span>
                <strong>{{ $returnSummary['due_today'] }}</strong>
            </div>
        </div>
    </section>

    {{-- Explains the result of each return-processing action. --}}
    <section class="return-outcome-guide" aria-label="Return processing guide">
        <div class="return-guide-heading">
            <h3>Processing outcomes</h3>
            <p>Use Return Book for a normal return. Lost and Damaged outcomes close the transaction and keep the book unavailable.</p>
        </div>

        <div class="return-guide-items">
            <div class="return-guide-item return-guide-normal">
                <span class="return-guide-dot" aria-hidden="true"></span>
                <div>
                    <strong>Normal Return</strong>
                    <small>Transaction: Returned &middot; Book: Available</small>
                </div>
            </div>

            <div class="return-guide-item return-guide-lost">
                <span class="return-guide-dot" aria-hidden="true"></span>
                <div>
                    <strong>Lost Book</strong>
                    <small>Transaction: Lost &middot; Book: Lost and unavailable</small>
                </div>
            </div>

            <div class="return-guide-item return-guide-damaged">
                <span class="return-guide-dot" aria-hidden="true"></span>
                <div>
                    <strong>Damaged Book</strong>
                    <small>Transaction: Returned &middot; Book: Damaged and unavailable</small>
                </div>
            </div>
        </div>
    </section>

    {{-- Lists transactions that can still be returned, lost, or damaged. --}}
    <section class="return-queue-section">
        <div class="return-queue-heading">
            <div>
                <h2>Active Borrowed Transactions</h2>
                <p>
                    {{ $activeTransactions->total() }}
                    {{ \Illuminate\Support\Str::plural('transaction', $activeTransactions->total()) }} ready for review
                </p>
            </div>

            <form method="GET" action="{{ route('admin.return.index') }}" class="return-search-form">
                <label for="return-search" class="sr-only">Search active transactions</label>
                <div class="return-search-field">
                    <input
                        id="return-search"
                        type="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search member, title, author, ISBN, genre, or status"
                    >
                </div>

                <button type="submit" class="return-search-button">Search</button>

                @if (request()->filled('search'))
                    <a href="{{ route('admin.return.index') }}" class="return-reset-button">Reset</a>
                @endif
            </form>
        </div>

        <div class="return-record-list">
            @forelse ($activeTransactions as $transaction)
                @php
                    // Prepare display names, dates, and overdue counts for each card.
                    $memberName = trim(implode(' ', array_filter([
                        $transaction->member_first_name,
                        $transaction->member_middle_initial
                            ? rtrim($transaction->member_middle_initial, '.') . '.'
                            : null,
                        $transaction->member_last_name,
                    ])));

                    $authorName = trim(implode(' ', array_filter([
                        $transaction->author_first_name,
                        $transaction->author_middle_initial
                            ? rtrim($transaction->author_middle_initial, '.') . '.'
                            : null,
                        $transaction->author_last_name,
                    ])));

                    $borrowDate = \Illuminate\Support\Carbon::parse($transaction->borrow_date);
                    $dueDate = \Illuminate\Support\Carbon::parse($transaction->due_date);
                    $isOverdue = $transaction->borrow_status === 'Overdue';
                    $daysOverdue = $isOverdue ? (int) $dueDate->diffInDays(now()->startOfDay()) : 0;
                @endphp

                <article class="return-record-card {{ $isOverdue ? 'is-overdue' : '' }}">
                    <header class="return-record-header">
                        <div class="return-member-block">
                            <div class="return-member-avatar" aria-hidden="true">
                                {{ strtoupper(substr($transaction->member_first_name, 0, 1) . substr($transaction->member_last_name, 0, 1)) }}
                            </div>
                            <div>
                                <span class="return-record-label">Borrower</span>
                                <h3>{{ $memberName }}</h3>
                                <span class="return-role-badge">{{ ucfirst($transaction->member_role) }}</span>
                            </div>
                        </div>

                        <div class="return-record-statuses">
                            <span class="status-label status-{{ str_replace(' ', '-', strtolower($transaction->borrow_status)) }}">
                                {{ $transaction->borrow_status }}
                            </span>
                            <span class="status-label status-{{ str_replace(' ', '-', strtolower($transaction->book_status)) }}">
                                Book: {{ $transaction->book_status }}
                            </span>
                        </div>
                    </header>

                    <div class="return-record-body">
                        <section class="return-details-panel">
                            <span class="return-record-label">Book Details</span>
                            <h4>{{ $transaction->book_title }}</h4>

                            <dl class="return-detail-list">
                                <div>
                                    <dt>Primary Author</dt>
                                    <dd>{{ $authorName !== '' ? $authorName : 'Not available' }}</dd>
                                </div>
                                <div>
                                    <dt>ISBN</dt>
                                    <dd>{{ $transaction->isbn ?: 'Not available' }}</dd>
                                </div>
                                <div>
                                    <dt>Genre</dt>
                                    <dd>{{ $transaction->genre_name ?: 'Not available' }}</dd>
                                </div>
                            </dl>
                        </section>

                        <section class="return-timeline-panel">
                            <span class="return-record-label">Return Timeline</span>

                            <div class="return-timeline">
                                <div class="return-timeline-item">
                                    <span>Borrow Date</span>
                                    <strong>{{ $borrowDate->format('M d, Y') }}</strong>
                                </div>
                                <div class="return-timeline-divider" aria-hidden="true"></div>
                                <div class="return-timeline-item {{ $isOverdue ? 'is-late' : '' }}">
                                    <span>Due Date</span>
                                    <strong>{{ $dueDate->format('M d, Y') }}</strong>
                                    @if ($isOverdue)
                                        <small>{{ $daysOverdue }} {{ \Illuminate\Support\Str::plural('day', $daysOverdue) }} overdue</small>
                                    @else
                                    @endif
                                </div>
                            </div>
                        </section>

                        <section class="return-processing-panel">
                            {{-- Submits the selected return outcome for this transaction. --}}
                            <form method="POST" class="return-processing-form">
                                @csrf

                                <div class="return-date-field return-date-auto-field">
                                    <span class="return-record-label">Return Date</span>

                                    <div class="return-timeline-item return-timeline-auto" role="note" aria-label="Automatic return date">
                                        <span>Set Automatically</span>
                                        <strong>{{ now()->format('M d, Y') }}</strong>
                                        <small>The current date will be saved when an action is confirmed.</small>
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    formaction="{{ route('admin.return.return-book', $transaction->transaction_id) }}"
                                    class="return-primary-action"
                                    onclick="return confirm('Return this book now? The current date will be recorded automatically.');"
                                >
                                    Return Book
                                </button>

                                <div class="return-exception-actions">
                                    <div>
                                        <button
                                            type="submit"
                                            formaction="{{ route('admin.return.mark-lost', $transaction->transaction_id) }}"
                                            class="return-exception-button return-lost-action"
                                            onclick="return confirm('Mark this book as lost? The transaction will close, the current date will be recorded, and the book cannot be borrowed again.');"
                                        >
                                            Mark as Lost
                                        </button>

                                        <button
                                            type="submit"
                                            formaction="{{ route('admin.return.mark-damaged', $transaction->transaction_id) }}"
                                            class="return-exception-button return-damaged-action"
                                            onclick="return confirm('Mark this book as damaged? The transaction will close, the current date will be recorded, and the book cannot be borrowed again.');"
                                        >
                                            Mark as Damaged
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </section>
                    </div>
                </article>
            @empty
                <div class="return-empty-state">
                    <div class="return-empty-icon" aria-hidden="true">&#10003;</div>
                    <h3>No active transactions found</h3>
                    <p>
                        @if (request()->filled('search'))
                            No borrowed or overdue transactions match your search.
                        @else
                            All borrowed transactions have already been processed.
                        @endif
                    </p>
                    @if (request()->filled('search'))
                        <a href="{{ route('admin.return.index') }}" class="return-empty-link">Clear search</a>
                    @endif
                </div>
            @endforelse
        </div>

        @if ($activeTransactions->hasPages())
            <div class="pagination-wrapper return-pagination">
                {{ $activeTransactions->links() }}
            </div>
        @endif
    </section>
@endsection
