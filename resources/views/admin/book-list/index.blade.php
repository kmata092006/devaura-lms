@extends('layouts.admin')

@section('title', 'Book List / Search')
@section('page-title', 'Book List / Search')

@section('content')
    @inject('roleService', 'App\Services\RoleService')

    <div class="page-actions book-list-page-actions">
        <div>
            <span class="book-list-eyebrow">Library Catalog</span>
            <h2>Complete Book List</h2>
            <p>Search the library collection and quickly identify whether a book is available for borrowing.</p>
        </div>

        @if ($roleService->hasPermission('staff' || 'student staff', 'Book Management'))
            <a href="{{ route('admin.books.index') }}" class="action-button">
                Go to Book Management
            </a>
        @endif
    </div>

    <section class="book-status-guide" aria-label="Book status color guide">
        <div class="book-status-guide-copy">
            <h3>Book Status Guide</h3>
            <p>Only books marked <strong>Available</strong> can be selected in the Borrow Book module.</p>
        </div>

        <div class="book-status-guide-items">
            <span class="book-status-guide-item is-available">
                <span class="book-status-guide-dot" aria-hidden="true"></span>
                Available
            </span>

            <span class="book-status-guide-item is-borrowed">
                <span class="book-status-guide-dot" aria-hidden="true"></span>
                Borrowed
            </span>

            <span class="book-status-guide-item is-lost">
                <span class="book-status-guide-dot" aria-hidden="true"></span>
                Lost
            </span>

            <span class="book-status-guide-item is-damaged">
                <span class="book-status-guide-dot" aria-hidden="true"></span>
                Damaged
            </span>
        </div>
    </section>

    <form method="GET" action="{{ route('admin.book-list.index') }}" class="book-list-filter-form">
        <div class="form-group book-list-search-field">
            <label for="search">Search</label>
            <input
                type="text"
                name="search"
                id="search"
                value="{{ request('search') }}"
                placeholder="Search by ISBN, title, author, publisher, genre, or status"
                autocomplete="off"
            >
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" id="status">
                <option value="">All Statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                        {{ $status }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="genre">Genre</label>
            <select name="genre" id="genre">
                <option value="">All Genres</option>
                @foreach ($genres as $genre)
                    <option value="{{ $genre->genre_name }}" {{ request('genre') === $genre->genre_name ? 'selected' : '' }}>
                        {{ $genre->genre_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="author">Author</label>
            <select name="author" id="author">
                <option value="">All Authors</option>
                @foreach ($authors as $author)
                    @php
                        $authorName = trim(implode(' ', array_filter([
                            $author->first_name ?? null,
                            !empty($author->middle_initial) ? rtrim($author->middle_initial, '.') . '.' : null,
                            $author->last_name ?? null,
                        ])));
                    @endphp

                    <option value="{{ $author->author_id }}" {{ (string) request('author') === (string) $author->author_id ? 'selected' : '' }}>
                        {{ $authorName !== '' ? $authorName : 'Unnamed Author' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="publisher">Publisher</label>
            <select name="publisher" id="publisher">
                <option value="">All Publishers</option>
                @foreach ($publishers as $publisher)
                    @php
                        $publisherName = trim(implode(' ', array_filter([
                            $publisher->first_name ?? null,
                            !empty($publisher->middle_initial) ? rtrim($publisher->middle_initial, '.') . '.' : null,
                            $publisher->last_name ?? null,
                        ])));
                    @endphp

                    <option value="{{ $publisher->publisher_id }}" {{ (string) request('publisher') === (string) $publisher->publisher_id ? 'selected' : '' }}>
                        {{ $publisherName !== '' ? $publisherName : 'Unnamed Publisher' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="book-list-filter-actions">
            <button type="submit">Apply Filters</button>
            <a href="{{ route('admin.book-list.index') }}" class="secondary-button">Reset</a>
        </div>
    </form>

    <section class="book-list-records-shell">
        <div class="book-list-records-header">
            <div>
                <h3>Book Records</h3>
                <p>
                    <strong>{{ $books->total() }}</strong>
                    {{ \Illuminate\Support\Str::plural('book', $books->total()) }} found
                </p>
            </div>
        </div>

        <div class="book-list-records">
            @forelse ($books as $book)
                @php
                    $status = trim((string) ($book->book_status ?? 'Available'));
                    $statusSlug = \Illuminate\Support\Str::slug($status);

                    $authorName = trim(implode(' ', array_filter([
                        $book->author_first_name ?? null,
                        !empty($book->author_middle_initial) ? rtrim($book->author_middle_initial, '.') . '.' : null,
                        $book->author_last_name ?? null,
                    ])));

                    $publisherName = trim(implode(' ', array_filter([
                        $book->publisher_first_name ?? null,
                        !empty($book->publisher_middle_initial) ? rtrim($book->publisher_middle_initial, '.') . '.' : null,
                        $book->publisher_last_name ?? null,
                    ])));

                    $statusDescriptions = [
                        'Available' => 'Available for borrowing',
                        'Borrowed' => 'Currently checked out',
                        'Lost' => 'Unavailable — reported lost',
                        'Damaged' => 'Unavailable — returned damaged',
                        'Reserved' => 'Unavailable — currently reserved',
                        'Under Repair' => 'Unavailable — under repair',
                    ];

                    $statusDescription = $statusDescriptions[$status] ?? 'Current catalog status';
                    $canBeBorrowed = $status === 'Available';
                @endphp

                <article class="book-list-record status-card-{{ $statusSlug }}">
                    <header class="book-list-record-header">
                        <div class="book-list-record-identity">
                            <div class="book-list-record-avatar" aria-hidden="true">BK</div>

                            <div class="book-list-record-title">
                                <span>Book Record</span>
                                <h4>{{ $book->book_title }}</h4>
                                <p>{{ $authorName !== '' ? $authorName : 'Author not available' }}</p>
                            </div>
                        </div>

                        <span class="book-list-status-badge status-{{ $statusSlug }}">
                            <span class="book-list-status-dot" aria-hidden="true"></span>
                            {{ $status }}
                        </span>
                    </header>

                    <div class="book-list-record-details">
                        <div class="book-list-detail">
                            <span>ISBN</span>
                            <strong>{{ $book->isbn ?: 'Not available' }}</strong>
                        </div>

                        <div class="book-list-detail">
                            <span>Publisher</span>
                            <strong>{{ $publisherName !== '' ? $publisherName : 'Not available' }}</strong>
                        </div>

                        <div class="book-list-detail">
                            <span>Publication Date</span>
                            <strong>
                                {{ !empty($book->date_published)
                                    ? \Illuminate\Support\Carbon::parse($book->date_published)->format('M d, Y')
                                    : 'Not available' }}
                            </strong>
                        </div>

                        <div class="book-list-detail">
                            <span>Genre</span>
                            <strong>{{ $book->genre_name ?: 'Not available' }}</strong>
                        </div>

                        <div class="book-list-detail book-list-status-detail">
                            <span>Book Status</span>
                            <strong class="book-list-status-value">
                                <span class="book-list-status-dot" aria-hidden="true"></span>
                                {{ $status }}
                            </strong>
                            <small>{{ $statusDescription }}</small>
                        </div>
                    </div>

                    <footer class="book-list-record-footer {{ $canBeBorrowed ? 'is-available' : 'is-unavailable' }}">
                        @if ($canBeBorrowed)
                            <span class="book-list-availability-icon" aria-hidden="true">✓</span>
                            <span>This book can be selected in the Borrow Book module.</span>
                        @else
                            <span class="book-list-availability-icon" aria-hidden="true">!</span>
                            <span>This book cannot be borrowed while its status is {{ $status }}.</span>
                        @endif
                    </footer>
                </article>
            @empty
                <div class="book-list-empty-state">
                    <div class="book-list-empty-icon" aria-hidden="true">⌕</div>
                    <h3>No books matched your search</h3>
                    <p>Try changing the keyword or clearing one or more filters.</p>
                </div>
            @endforelse
        </div>
    </section>

    @if ($books->hasPages())
        <div class="pagination-wrapper">
            {{ $books->links() }}
        </div>
    @endif
@endsection
