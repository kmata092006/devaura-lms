@extends('layouts.admin')

@section('title', 'Book List / Search')
@section('page-title', 'Book List / Search')

@section('content')
    <div class="page-actions">
        <div>
            <h2>Complete Book List</h2>
            <p>View and search all books by title, ISBN, author, publisher, genre, and status.</p>
        </div>

        <a href="{{ route('admin.books.index') }}" class="action-button">Go to Book Management</a>
    </div>

    <form method="GET" action="{{ route('admin.book-list.index') }}" class="book-list-filter-form">
        <div class="form-group">
            <label for="search">Search</label>
            <input
                type="text"
                name="search"
                id="search"
                value="{{ request('search') }}"
                placeholder="Search by ID, ISBN, title, author, publisher, genre, or status"
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

    <div class="table-card">
        <table class="data-table book-list-table">
            <thead>
                <tr>
                    <th>ISBN</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Publisher</th>
                    <th>Date Published</th>
                    <th>Genre</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($books as $book)
                    <tr>
                        <td>{{ $book->isbn ?? '-' }}</td>
                        <td class="book-title-cell">{{ $book->book_title }}</td>
                        <td>
                            {{ $book->author_first_name }}
                            {{ $book->author_middle_initial ? $book->author_middle_initial . '.' : '' }}
                            {{ $book->author_last_name }}
                        </td>
                        <td>
                            {{ $book->publisher_first_name }}
                            {{ $book->publisher_middle_initial ? $book->publisher_middle_initial . '.' : '' }}
                            {{ $book->publisher_last_name }}
                        </td>
                        <td>{{ $book->date_published ?? '-' }}</td>
                        <td>{{ $book->genre_name }}</td>
                        <td>
                            <span class="status-label status-{{ str_replace(' ', '-', strtolower($book->book_status)) }}">
                                {{ $book->book_status }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-table">
                            No books matched your search or filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $books->links() }}
    </div>
@endsection