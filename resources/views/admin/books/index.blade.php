@extends('layouts.admin')

@section('title', 'Book Management')
@section('page-title', 'Book Management')

@section('content')
    <style>
        .book-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .book-delete-form {
            display: inline-flex;
            margin: 0;
        }

        .book-cannot-delete {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 32px;
            padding: 6px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #e2e8f0;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            cursor: help;
            white-space: nowrap;
        }

        .book-cannot-delete svg {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }
    </style>

    <div class="page-actions">
        <div>
            <h2>Book Records</h2>
            <p>Manage books, authors, publishers, genres, ISBN, titles, and statuses.</p>
        </div>

        <a href="{{ route('admin.books.create') }}" class="action-button">Add Book</a>
    </div>

    @if (session('success'))
        <div class="alert success" role="status">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert error" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="GET" action="{{ route('admin.books.index') }}" class="filter-form">
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search by ISBN, title, author, publisher, genre, or status"
        >

        <select name="status">
            <option value="">All Statuses</option>

            @foreach ($statuses as $status)
                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                    {{ $status }}
                </option>
            @endforeach
        </select>

        <button type="submit">Search</button>
        <a href="{{ route('admin.books.index') }}" class="secondary-button">Reset</a>
    </form>

    <div class="system-records-shell book-management-records-shell">
        <div class="system-records-meta">
            <div>
                <h3>Book Records</h3>
                <p>{{ $books->total() }} {{ \Illuminate\Support\Str::plural('book', $books->total()) }} found</p>
            </div>
        </div>

        <div class="system-record-list">
            @forelse ($books as $book)
                    @php
                        $totalTransactionCount = (int) ($book->borrow_transaction_count ?? 0);
                        $activeTransactionCount = (int) ($book->active_borrow_transaction_count ?? 0);
                        $bookStatus = trim((string) ($book->book_status ?? 'Unknown'));
                        $statusKey = strtolower(str_replace([' ', '_'], '-', $bookStatus));
                        $isBorrowed = strcasecmp($bookStatus, 'Borrowed') === 0;
                        $hasActiveBorrowing = $activeTransactionCount > 0 || $isBorrowed;
                        $cannotDelete = $hasActiveBorrowing;

                        $statusClass = match ($statusKey) {
                            'available' => 'is-status-available',
                            'borrowed' => 'is-status-borrowed',
                            'lost' => 'is-status-lost',
                            'damaged' => 'is-status-damaged',
                            'reserved' => 'is-status-reserved',
                            'under-repair' => 'is-status-under-repair',
                            default => 'is-status-neutral',
                        };

                        $statusDescription = match ($statusKey) {
                            'available' => 'Ready for borrowing',
                            'borrowed' => 'Currently checked out',
                            'lost' => 'Unavailable because the book was reported lost',
                            'damaged' => 'Unavailable because the book was returned damaged',
                            'reserved' => 'Reserved and not currently available',
                            'under-repair' => 'Temporarily unavailable while being repaired',
                            default => 'Current circulation status',
                        };

                        $cannotDeleteMessage = $hasActiveBorrowing
                            ? 'This book cannot be deleted because it still has an active borrowing transaction.'
                            : '';

                        $deleteConfirmation = $totalTransactionCount > 0
                            ? 'Delete this book and its completed borrowing records? This action cannot be undone.'
                            : 'Delete this book? This action cannot be undone.';
                    @endphp

                <article class="system-record-card book-status-card {{ $statusClass }}">
                    <header class="system-record-header">
                        <div class="system-record-identity">
                            <div class="system-record-avatar book-status-avatar" aria-hidden="true">BK</div>
                            <div class="system-record-copy">
                                <span class="system-record-eyebrow">Book Record</span>
                                <h3>{{ $book->book_title }}</h3>
                                <p>{{ $book->author_name ?: 'Author not available' }}</p>
                            </div>
                        </div>

                        <div class="system-record-badges">
                            <span class="system-record-badge book-status-badge">
                                <span class="book-status-dot" aria-hidden="true"></span>
                                {{ $bookStatus }}
                            </span>
                        </div>
                    </header>

                    <div class="system-record-body book-record-grid">
                        <div class="system-record-field">
                            <span>ISBN</span>
                            <strong>{{ $book->isbn ?: 'Not available' }}</strong>
                        </div>

                        <div class="system-record-field">
                            <span>Publisher</span>
                            <strong>{{ $book->publisher_name ?: 'Not available' }}</strong>
                        </div>

                        <div class="system-record-field">
                            <span>Genre</span>
                            <strong>{{ $book->genre_name ?: 'Not available' }}</strong>
                        </div>

                        <div class="system-record-field book-status-field">
                            <span>Book Status</span>
                            <strong class="book-status-value">
                                <span class="book-status-dot" aria-hidden="true"></span>
                                {{ $bookStatus }}
                            </strong>
                            <small>{{ $statusDescription }}</small>
                        </div>

                        <div class="system-record-actions-panel">
                            <span class="system-record-section-label">Actions</span>
                            <div class="system-record-actions book-actions">
                                <a href="{{ route('admin.books.edit', $book->book_id) }}" class="small-button edit">Edit</a>

                                @if ($cannotDelete)
                                    <span
                                        class="book-cannot-delete"
                                        title="{{ $cannotDeleteMessage }}"
                                        aria-label="{{ $cannotDeleteMessage }}"
                                        tabindex="0"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect x="4" y="10" width="16" height="10" rx="2"></rect>
                                            <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                                        </svg>
                                        <span>Cannot Delete</span>
                                    </span>
                                @else
                                    <form
                                        method="POST"
                                        action="{{ route('admin.books.destroy', $book->book_id) }}"
                                        class="book-delete-form"
                                        onsubmit='return confirm(@js($deleteConfirmation));'
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="small-button delete">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="system-record-empty">
                    <strong>No books found</strong>
                    Add a book or adjust the search and status filters.
                </div>
            @endforelse
        </div>
    </div>

    <div class="pagination-wrapper">
        {{ $books->links() }}
    </div>
@endsection
