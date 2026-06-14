@extends('layouts.admin')

@section('title', 'Borrow Book')
@section('page-title', 'Borrow Book')

@section('content')
    <div class="page-actions">
        <div>
            <h2>Borrow Book Transaction</h2>
            <p>
                Select a library member and an available book to create a
                borrowing transaction.
            </p>
        </div>

        <a
            href="{{ route('admin.books.index') }}"
            class="action-button"
        >
            Manage Books
        </a>
    </div>

    {{-- Success message --}}
    @if (session('success'))
        <div class="alert success">
            {{ session('success') }}
        </div>
    @endif

    {{-- Validation messages --}}
    @if ($errors->any())
        <div class="alert error">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- New borrow transaction --}}
    <div class="borrow-layout">
        <div class="form-card borrow-form-card">
            <div class="form-card-header">
                <h2>New Borrow Transaction</h2>
                <p>
                    Only books with Available status and no active transaction can be borrowed.
                </p>
            </div>

            <form
                method="POST"
                action="{{ route('admin.borrow.store') }}"
                class="record-form"
            >
                @csrf

                {{-- Library member --}}
                <div class="form-group">
                    <label for="member_id">
                        Library Member
                    </label>

                    <select
                        name="member_id"
                        id="member_id"
                        required
                    >
                        <option value="">
                            Select library member
                        </option>

                        @foreach ($members as $member)
                            <option
                                value="{{ $member->member_id }}"
                                {{ (string) old('member_id') === (string) $member->member_id ? 'selected' : '' }}
                            >
                                {{ $member->first_name }}

                                @if (!empty($member->middle_initial))
                                    {{ rtrim($member->middle_initial, '.') }}.
                                @endif

                                {{ $member->last_name }}
                                — {{ ucfirst($member->member_role) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Available book --}}
                <div class="form-group">
                    <label for="book_id">
                        Available Book
                    </label>

                    <select
                        name="book_id"
                        id="book_id"
                        required
                    >
                        <option value="">
                            Select available book
                        </option>

                        @foreach ($availableBooks as $book)
                            @php
                                $availableBookAuthor = trim(
                                    implode(
                                        ' ',
                                        array_filter([
                                            $book->author_first_name ?? null,
                                            !empty($book->author_middle_initial)
                                                ? rtrim($book->author_middle_initial, '.') . '.'
                                                : null,
                                            $book->author_last_name ?? null,
                                        ])
                                    )
                                );
                            @endphp

                            <option
                                value="{{ $book->book_id }}"
                                {{ (string) old('book_id') === (string) $book->book_id ? 'selected' : '' }}
                            >
                                {{ $book->book_title }}
                                —
                                {{ $availableBookAuthor !== ''
                                    ? $availableBookAuthor
                                    : 'Author unknown' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Due date --}}
                <div class="form-group">
                    <label for="due_date">
                        Due Date
                    </label>

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

                    <a
                        href="{{ route('admin.borrow.index') }}"
                        class="secondary-button"
                    >
                        Reset
                    </a>
                </div>

                @if ($members->isEmpty())
                    <p class="form-note warning-note">
                        No library members were found. Add a library member first.
                    </p>
                @endif

                @if ($availableBooks->isEmpty())
                    <p class="form-note warning-note">
                        No borrowable books were found. Lost, Damaged, Reserved,
                        Under Repair, Borrowed, and Overdue books are excluded.
                    </p>
                @endif
            </form>
        </div>

        {{-- Borrowing rules --}}
        <aside class="borrow-info-card">
            <h3>Borrowing Rules</h3>

            <ul>
                <li>
                    The selected book must have
                    <strong>Available</strong> status.
                </li>

                <li>
                    After borrowing, the book status becomes
                    <strong>Borrowed</strong>.
                </li>

                <li>
                    The transaction status becomes
                    <strong>Borrowed</strong>.
                </li>

                <li>
                    Transactions are marked
                    <strong>Overdue</strong>
                    after their due date.
                </li>

                <li>
                    Books marked <strong>Lost</strong> or <strong>Damaged</strong>
                    remain unavailable and cannot be borrowed again.
                </li>
            </ul>
        </aside>
    </div>

    {{-- Active transactions introduction --}}
    <div class="section-heading">
        <div>
            <h2>Active Borrowed Transactions</h2>

            <p>
                These are transactions that are currently Borrowed or Overdue.
            </p>
        </div>
    </div>

    {{-- Search --}}
    <form
        method="GET"
        action="{{ route('admin.borrow.index') }}"
        class="filter-form"
    >
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search by member, book title, author, ISBN, or status"
            autocomplete="off"
        >

        <button type="submit">
            Search
        </button>

        <a
            href="{{ route('admin.borrow.index') }}"
            class="secondary-button"
        >
            Reset
        </a>
    </form>

    {{-- Active borrowed transaction records --}}
    <section class="system-records-shell borrow-records-shell">
        <div class="system-records-meta">
            <div>
                <h3>Active Borrowed Transactions</h3>

                <p>
                    {{ $activeTransactions->total() }}
                    active
                    {{ \Illuminate\Support\Str::plural(
                        'transaction',
                        $activeTransactions->total()
                    ) }}
                </p>
            </div>
        </div>

        <div class="system-record-list">
            @forelse ($activeTransactions as $transaction)
                @php
                    $borrowMemberName = trim(
                        implode(
                            ' ',
                            array_filter([
                                $transaction->member_first_name ?? null,
                                !empty($transaction->member_middle_initial)
                                    ? rtrim($transaction->member_middle_initial, '.') . '.'
                                    : null,
                                $transaction->member_last_name ?? null,
                            ])
                        )
                    );

                    $borrowInitials = strtoupper(
                        substr($transaction->member_first_name ?? 'M', 0, 1)
                        . substr($transaction->member_last_name ?? '', 0, 1)
                    );

                    $borrowDate = \Illuminate\Support\Carbon::parse(
                        $transaction->borrow_date
                    );

                    $dueDate = \Illuminate\Support\Carbon::parse(
                        $transaction->due_date
                    );

                    $isOverdue = strtolower(
                        (string) $transaction->borrow_status
                    ) === 'overdue';
                @endphp

                <article
                    class="system-record-card {{ $isOverdue ? 'is-overdue' : '' }}"
                >
                    <header class="system-record-header">
                        <div class="system-record-identity">
                            <div
                                class="system-record-avatar {{ $isOverdue ? '' : 'is-green' }}"
                                aria-hidden="true"
                            >
                                {{ $borrowInitials !== '' ? $borrowInitials : 'MB' }}
                            </div>

                            <div class="system-record-copy">
                                <span class="system-record-eyebrow">
                                    Borrower
                                </span>

                                <h3>
                                    {{ $borrowMemberName !== ''
                                        ? $borrowMemberName
                                        : 'Unknown Library Member' }}
                                </h3>

                                <p>
                                    {{ ucfirst($transaction->member_role) }}
                                    member
                                </p>
                            </div>
                        </div>

                        <div class="system-record-badges">
                            <span
                                class="system-record-badge {{ $isOverdue ? 'is-danger' : '' }}"
                            >
                                {{ $transaction->borrow_status }}
                            </span>
                        </div>
                    </header>

                    <div class="system-record-body borrow-record-grid">
                        {{-- Book --}}
                        <div class="system-record-field is-primary">
                            <span>Borrowed Book</span>

                            <strong>
                                {{ $transaction->book_title }}
                            </strong>

                            <small>
                                {{ $transaction->isbn ?: 'ISBN not available' }}
                            </small>
                        </div>

                        {{-- Member role --}}
                        <div class="system-record-field">
                            <span>Member Role</span>

                            <strong>
                                {{ ucfirst($transaction->member_role) }}
                            </strong>
                        </div>

                        {{-- Borrow date --}}
                        <div class="system-record-field">
                            <span>Borrow Date</span>

                            <strong>
                                {{ $borrowDate->format('M d, Y') }}
                            </strong>
                        </div>

                        {{-- Due date --}}
                        <div
                            class="system-record-field {{ $isOverdue ? 'is-danger' : '' }}"
                        >
                            <span>Due Date</span>

                            <strong>
                                {{ $dueDate->format('M d, Y') }}
                            </strong>

                            @if ($isOverdue)
                                <small>
                                    Past the return deadline
                                </small>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                {{-- Empty state with green check icon --}}
                <div class="system-record-empty">
                    <div
                        aria-hidden="true"
                        style="
                            width: 58px;
                            height: 58px;
                            margin: 0 auto 18px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            border-radius: 999px;
                            background: #d1fae5;
                            color: #15803d;
                            font-size: 31px;
                            font-weight: 900;
                            line-height: 1;
                        "
                    >
                        &#10003;
                    </div>

                    <strong>
                        No active borrow transactions found
                    </strong>

                    <span>
                        Borrowed and overdue transactions will appear here.
                    </span>
                </div>
            @endforelse
        </div>
    </section>

    {{-- Pagination --}}
    @if ($activeTransactions->hasPages())
        <div class="pagination-wrapper">
            {{ $activeTransactions->withQueryString()->links() }}
        </div>
    @endif
@endsection