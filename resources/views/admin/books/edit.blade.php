@extends('layouts.admin')

@section('title', 'Edit Book')
@section('page-title', 'Edit Book')

@section('content')
    <div class="form-card wide-form-card">
        <div class="form-card-header">
            <h2>Edit Book</h2>
            <p>Update the selected book record.</p>
        </div>

        @if ($errors->any())
            <div class="alert error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.books.update', $book->book_id) }}" class="record-form">
            @csrf
            @method('PUT')

            <div class="form-section">
                <h3>Book Details</h3>

                <div class="form-grid two-columns">
                    <div class="form-group">
                        <label for="book_title">Book Title</label>
                        <input
                            type="text"
                            name="book_title"
                            id="book_title"
                            value="{{ old('book_title', $book->book_title) }}"
                            maxlength="255"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="isbn">ISBN</label>
                        <input
                            type="text"
                            name="isbn"
                            id="isbn"
                            value="{{ old('isbn', $book->isbn) }}"
                            maxlength="13"
                            placeholder="Optional"
                        >
                    </div>

                    <div class="form-group">
                        <label for="book_status">Book Status</label>
                        <select name="book_status" id="book_status" required>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" {{ old('book_status', $book->book_status) === $status ? 'selected' : '' }}>
                                    {{ $status }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="genre_name">Genre</label>
                        <input
                            type="text"
                            name="genre_name"
                            id="genre_name"
                            value="{{ old('genre_name', $book->genre_name) }}"
                            maxlength="100"
                            pattern="^[a-zA-Z\s]+$"
                            title="Only letters and spaces are allowed."
                            oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                            required
                        >
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Author Details</h3>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="author_last_name">Author Last Name</label>
                        <input
                            type="text"
                            name="author_last_name"
                            id="author_last_name"
                            value="{{ old('author_last_name', $book->author_last_name) }}"
                            maxlength="50"
                            pattern="^[a-zA-Z\.\s]+$"
                            title="Only letters, spaces, and periods are allowed."
                            oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="author_first_name">Author First Name</label>
                        <input
                            type="text"
                            name="author_first_name"
                            id="author_first_name"
                            value="{{ old('author_first_name', $book->author_first_name) }}"
                            maxlength="50"
                            pattern="^[a-zA-Z\.\s]+$"
                            title="Only letters, spaces, and periods are allowed."
                            oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="author_middle_initial">Author M.I.</label>
                        <input
                            type="text"
                            name="author_middle_initial"
                            id="author_middle_initial"
                            value="{{ old('author_middle_initial', $book->author_middle_initial) }}"
                            maxlength="3"
                            pattern="^[a-zA-Z\.]+$"
                            title="Only letters and periods are allowed."
                            oninput="this.value = this.value.replace(/[^a-zA-Z\.]/g, '')"
                            placeholder="Optional"
                        >
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Publisher Details</h3>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="publisher_last_name">Publisher Last Name</label>
                        <input
                            type="text"
                            name="publisher_last_name"
                            id="publisher_last_name"
                            value="{{ old('publisher_last_name', $book->publisher_last_name) }}"
                            maxlength="50"
                            pattern="^[a-zA-Z\.\s]+$"
                            title="Only letters, spaces, and periods are allowed."
                            oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                        >
                        <small>Leave blank if company name is entered in First Name field.</small>
                    </div>

                    <div class="form-group">
                        <label for="publisher_first_name">Publisher First Name / Name</label>
                        <input
                            type="text"
                            name="publisher_first_name"
                            id="publisher_first_name"
                            value="{{ old('publisher_first_name', $book->publisher_first_name) }}"
                            maxlength="50"
                            pattern="^[a-zA-Z\.\s]+$"
                            title="Only letters, spaces, and periods are allowed."
                            oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="publisher_middle_initial">Publisher M.I.</label>
                        <input
                            type="text"
                            name="publisher_middle_initial"
                            id="publisher_middle_initial"
                            value="{{ old('publisher_middle_initial', $book->publisher_middle_initial) }}"
                            maxlength="3"
                            pattern="^[a-zA-Z\.]+$"
                            title="Only letters and periods are allowed."
                            oninput="this.value = this.value.replace(/[^a-zA-Z\.]/g, '')"
                            placeholder="Optional"
                        >
                    </div>

                    <div class="form-group">
                        <label for="date_published">Date Published</label>
                        <input
                            type="date"
                            name="date_published"
                            id="date_published"
                            value="{{ old('date_published', $book->date_published) }}"
                            required
                        >
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="primary-button">Update Book</button>
                <a href="{{ route('admin.books.index') }}" class="secondary-button">Cancel</a>
            </div>
        </form>
    </div>
@endsection