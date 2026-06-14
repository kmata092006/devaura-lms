@extends('layouts.admin')

@section('title', 'Borrow Book')
@section('page-title', 'Borrow Book')

@section('content')
    {{-- Starts a borrowing workflow and links back to book maintenance. --}}
    <div class="page-actions">
        <div>
            <h2>Borrow Book Transaction</h2>
            <p>Select a library member and an available book to create a borrow transaction.</p>
        </div>

        <a href="{{ route('admin.books.index') }}" class="action-button">Manage Books</a>
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

    {{-- Creates a borrow transaction from an eligible member and available book. --}}
    <div class="borrow-layout">
        <div class="form-card borrow-form-card">
            <div class="form-card-header">
                <h2>New Borrow Transaction</h2>
                <p>Only books with Available status can be borrowed.</p>
            </div>

            <form method="POST" action="{{ route('admin.borrow.store') }}" class="record-form">
                @csrf

                <div class="form-group">
                    <label for="member_id">Library Member</label>
                    <select name="member_id" id="member_id" required>
                        <option value="">Select library member</option>
                        @foreach ($members as $member)
                            <option value="{{ $member->member_id }}" {{ old('member_id') == $member->member_id ? 'selected' : '' }}>
                                {{ $member->first_name }}
                                {{ $member->middle_initial ? $member->middle_initial . '.' : '' }}
                                {{ $member->last_name }}
                                — {{ ucfirst($member->member_role) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="book_id">Available Book</label>
                    <select name="book_id" id="book_id" required>
                        <option value="">Select available book</option>
                        @foreach ($availableBooks as $book)
                            @php
                                $authorName = trim(implode(' ', array_filter([
                                    $book->author_first_name ?? null,
                                    !empty($book->author_middle_initial)
                                        ? $book->author_middle_initial . '.'
                                        : null,
                                    $book->author_last_name ?? null,
                                ])));
                            @endphp

                            <option value="{{ $book->book_id }}" {{ old('book_id') == $book->book_id ? 'selected' : '' }}>
                                {{ $book->book_title }} — {{ $authorName !== '' ? $authorName : 'Author unknown' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="due_date">Due Date</label>
                    <input
                        type="date"
                        name="due_date"
                        id="due_date"
                        value="{{ old('due_date', $defaultDueDate) }}"
                        min="{{ now()->toDateString() }}"
                        required
                    >
                </div>

                <div class="form-actions">
                    <button
                        type="submit"
                        class="primary-button"
                        {{ $members->isEmpty() || $availableBooks->isEmpty() ? 'disabled' : '' }}
                    >
                        Borrow Book
                    </button>

                    <a href="{{ route('admin.borrow.index') }}" class="secondary-button">Reset</a>
                </div>

                @if ($members->isEmpty())
                    <p class="form-note warning-note">No library members found. Add a member first.</p>
                @endif

                @if ($availableBooks->isEmpty())
                    <p class="form-note warning-note">No available books found. Add or update a book status first.</p>
                @endif
            </form>
        </div>

        <div class="borrow-info-card">
            <h3>Borrowing Rules</h3>
            <ul>
                <li>The selected book must have <strong>Available</strong> status.</li>
                <li>After borrowing, the book status becomes <strong>Borrowed</strong>.</li>
                <li>The transaction status becomes <strong>Borrowed</strong>.</li>
                <li>Overdue records are marked based on the due date.</li>
            </ul>
        </div>
    </div>

    <div class="section-heading">
        <div>
            <h2>Active Borrowed Transactions</h2>
            <p>These are transactions that are currently Borrowed or Overdue.</p>
        </div>
    </div>

    {{-- Filters active borrowed and overdue transactions. --}}
    <form method="GET" action="{{ route('admin.borrow.index') }}" class="filter-form">
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search by member, book title, author, ISBN, or status"
        >

        <button type="submit">Search</button>
        <a href="{{ route('admin.borrow.index') }}" class="secondary-button">Reset</a>
    </form>

    <div class="system-records-shell borrow-records-shell">
        <div class="system-records-meta">
            <div>
                <h3>Active Borrowed Transactions</h3>
                <p>{{ $activeTransactions->total() }} active {{ \Illuminate\Support\Str::plural('transaction', $activeTransactions->total()) }}</p>
            </div>
        </div>

        <div class="system-record-list">
            @forelse ($activeTransactions as $transaction)
                @php
                    // Prepare borrower display details and overdue styling for the card.
                    $borrowMemberName = trim(implode(' ', array_filter([
                        $transaction->member_first_name,
                        $transaction->member_middle_initial
                            ? rtrim($transaction->member_middle_initial, '.') . '.'
                            : null,
                        $transaction->member_last_name,
                    ])));
                    $borrowInitials = strtoupper(
                        substr($transaction->member_first_name ?? 'M', 0, 1)
                        . substr($transaction->member_last_name ?? '', 0, 1)
                    );
                    $borrowDate = \Illuminate\Support\Carbon::parse($transaction->borrow_date);
                    $dueDate = \Illuminate\Support\Carbon::parse($transaction->due_date);
                    $isOverdue = $transaction->borrow_status === 'Overdue';
                @endphp

                <article class="system-record-card {{ $isOverdue ? 'is-overdue' : '' }}">
                    <header class="system-record-header">
                        <div class="system-record-identity">
                            <div class="system-record-avatar {{ $isOverdue ? '' : 'is-green' }}" aria-hidden="true">
                                {{ $borrowInitials ?: 'MB' }}
                            </div>
                            <div class="system-record-copy">
                                <span class="system-record-eyebrow">Borrower</span>
                                <h3>{{ $borrowMemberName }}</h3>
                                <p>{{ ucfirst($transaction->member_role) }} member</p>
                            </div>
                        </div>

                        <div class="system-record-badges">
                            <span class="system-record-badge {{ $isOverdue ? 'is-danger' : '' }}">
                                {{ $transaction->borrow_status }}
                            </span>
                        </div>
                    </header>

                    <div class="system-record-body borrow-record-grid">
                        <div class="system-record-field is-primary">
                            <span>Borrowed Book</span>
                            <strong>{{ $transaction->book_title }}</strong>
                            <small>{{ $transaction->isbn ?: 'ISBN not available' }}</small>
                        </div>

                        <div class="system-record-field">
                            <span>Member Role</span>
                            <strong>{{ ucfirst($transaction->member_role) }}</strong>
                        </div>

                        <div class="system-record-field">
                            <span>Borrow Date</span>
                            <strong>{{ $borrowDate->format('M d, Y') }}</strong>
                        </div>

                        <div class="system-record-field {{ $isOverdue ? 'is-danger' : '' }}">
                            <span>Due Date</span>
                            <strong>{{ $dueDate->format('M d, Y') }}</strong>
                            @if ($isOverdue)
                                <small>Past the return deadline</small>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="system-record-empty">
                    <strong>No active borrow transactions found</strong>
                    Borrowed and overdue transactions will appear here.
                </div>
            @endforelse
        </div>
    </div>

    <div class="pagination-wrapper">
        {{ $activeTransactions->links() }}
    </div>
@endsection
