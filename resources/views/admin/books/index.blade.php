@extends('layouts.admin')

@section('title', 'Book Management')
@section('page-title', 'Book Management')

@section('content')
    <div class="page-actions">
        <div>
            <h2>Book Records</h2>
            <p>Manage books, authors, publishers, genres, ISBN, titles, and statuses.</p>
        </div>

        <a href="{{ route('admin.books.create') }}" class="action-button">Add Book</a>
    </div>

    @if (session('success'))
        <div class="alert success">
            {{ session('success') }}
        </div>
    @endif

    <form method="GET" action="{{ route('admin.books.index') }}" class="filter-form">
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search by ID, ISBN, title, author, publisher, genre, or status"
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

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Book ID</th>
                    <th>ISBN</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Publisher</th>
                    <th>Genre</th>
                    <th>Status</th>
                    <th class="table-actions-column">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($books as $book)
                    <tr>
                        <td>{{ $book->book_id }}</td>
                        <td>{{ $book->isbn ?? '-' }}</td>
                        <td>{{ $book->book_title }}</td>
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
                        <td>{{ $book->genre_name }}</td>
                        <td>
                            <span class="status-label status-{{ str_replace(' ', '-', strtolower($book->book_status)) }}">
                                {{ $book->book_status }}
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="{{ route('admin.books.edit', $book->book_id) }}" class="small-button edit">Edit</a>

                                <form
                                    method="POST"
                                    action="{{ route('admin.books.destroy', $book->book_id) }}"
                                    onsubmit="return confirm('Delete this book? This action cannot be undone.');"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="small-button delete">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-table">No books found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $books->links() }}
    </div>
@endsection