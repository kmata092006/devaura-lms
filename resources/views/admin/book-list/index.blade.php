@extends('layouts.admin')

@section('title', 'Book List / Search')
@section('page-title', 'Book List / Search')

@section('content')
    {{-- Provides a read-only catalog search entry point. --}}
    <div class="page-actions">
        <div>
            <h2>Complete Book List</h2>
            <p>View and search all books by title, ISBN, author, publisher, genre, and status.</p>
        </div>

        <a href="{{ route('admin.books.index') }}" class="action-button">Go to Book Management</a>
    </div>

    {{-- Applies catalog search and dropdown filters through query parameters. --}}
    <form method="GET" action="{{ route('admin.book-list.index') }}" class="book-list-filter-form">
        <div class="form-group">
            <label for="search">Search</label>
            <input
                type="text"
                name="search"
                id="search"
                value="{{ request('search') }}"
                placeholder="Search by ISBN, title, author, publisher, genre, or status"
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
                    <option value="{{ $author->author_id }}" {{ request('author') == $author->author_id ? 'selected' : '' }}>
                        {{ $author->first_name }}
                        {{ $author->middle_initial ? $author->middle_initial . '.' : '' }}
                        {{ $author->last_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="publisher">Publisher</label>
            <select name="publisher" id="publisher">
                <option value="">All Publishers</option>
                @foreach ($publishers as $publisher)
                    <option value="{{ $publisher->publisher_id }}" {{ request('publisher') == $publisher->publisher_id ? 'selected' : '' }}>
                        {{ $publisher->first_name }}
                        {{ $publisher->middle_initial ? $publisher->middle_initial . '.' : '' }}
                        {{ $publisher->last_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="book-list-filter-actions">
            <button type="submit">Apply Filters</button>
            <a href="{{ route('admin.book-list.index') }}" class="secondary-button">Reset</a>
        </div>
    </form>

    <div class="book-list-summary">
        <strong>{{ $books->total() }}</strong>
        book record{{ $books->total() === 1 ? '' : 's' }} found.
    </div>

    <div class="system-records-shell book-list-records-shell">
        <div class="system-records-meta">
            <div>
                <h3>Book Catalog Records</h3>
                <p>{{ $books->total() }} {{ \Illuminate\Support\Str::plural('book', $books->total()) }} found</p>
            </div>
        </div>

        <div class="system-record-list">
            @forelse ($books as $book)
                @php
                    // Format related author and publisher names for the catalog card.
                    $bookAuthorName = trim(implode(' ', array_filter([
                        $book->author_first_name,
                        $book->author_middle_initial
                            ? rtrim($book->author_middle_initial, '.') . '.'
                            : null,
                        $book->author_last_name,
                    ])));
                    $bookPublisherName = trim(implode(' ', array_filter([
                        $book->publisher_first_name,
                        $book->publisher_middle_initial
                            ? rtrim($book->publisher_middle_initial, '.') . '.'
                            : null,
                        $book->publisher_last_name,
                    ])));
                @endphp

                <article class="system-record-card {{ $book->book_status === 'Borrowed' ? 'is-warning' : '' }}">
                    <header class="system-record-header">
                        <div class="system-record-identity">
                            <div class="system-record-avatar" aria-hidden="true">BK</div>
                            <div class="system-record-copy">
                                <span class="system-record-eyebrow">Catalog Record</span>
                                <h3>{{ $book->book_title }}</h3>
                                <p>{{ $bookAuthorName !== '' ? $bookAuthorName : 'Author not available' }}</p>
                            </div>
                        </div>

                        <div class="system-record-badges">
                            <span class="system-record-badge status-{{ str_replace(' ', '-', strtolower($book->book_status)) }}">
                                {{ $book->book_status }}
                            </span>
                        </div>
                    </header>

                    <div class="system-record-body book-list-record-grid">
                        <div class="system-record-field">
                            <span>ISBN</span>
                            <strong>{{ $book->isbn ?: 'Not available' }}</strong>
                        </div>

                        <div class="system-record-field">
                            <span>Publisher</span>
                            <strong>{{ $bookPublisherName !== '' ? $bookPublisherName : 'Not available' }}</strong>
                        </div>

                        <div class="system-record-field">
                            <span>Date Published</span>
                            <strong>{{ $book->date_published ?: 'Not available' }}</strong>
                        </div>

                        <div class="system-record-field">
                            <span>Genre</span>
                            <strong>{{ $book->genre_name ?: 'Not available' }}</strong>
                        </div>

                        <div class="system-record-field is-muted">
                            <span>Book Status</span>
                            <strong>{{ $book->book_status }}</strong>
                        </div>
                    </div>
                </article>
            @empty
                <div class="system-record-empty">
                    <strong>No books matched your search</strong>
                    Adjust the search term or selected filters.
                </div>
            @endforelse
        </div>
    </div>

    <div class="pagination-wrapper">
        {{ $books->links() }}
    </div>
@endsection
