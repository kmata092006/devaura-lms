@extends('layouts.admin')

@section('title', 'Add Book | UBNHS Library Management System')
@section('page-title', 'Add Book')

@section('content')
    <div class="page-actions">
        <div>
            <h2>Add Book</h2>
            <p>Add a book manually or use Google Books lookup to auto-fill details.</p>
        </div>

        <a href="{{ route('admin.books.index') }}" class="secondary-button">
            Back
        </a>
    </div>

    @if ($errors->any())
        <div class="alert error">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Optional Google Books helper used to auto-fill the manual book form. --}}
    <div class="google-books-card">
        <div class="google-books-header">
            <div>
                <span class="google-books-eyebrow">Optional Helper</span>
                <h3>Google Books Lookup</h3>
                <p>Search by ISBN or title to auto-fill the manual Add Book form. You can still edit all fields before saving.</p>
            </div>
        </div>

        <div class="google-books-search-row">
            <input
                type="text"
                id="googleBooksQuery"
                placeholder="Enter ISBN or book title"
                autocomplete="off"
            >

            <button type="button" id="googleBooksSearchButton">
                Search Google Books
            </button>
        </div>

        <div id="googleBooksMessage" class="google-books-message" style="display: none;"></div>

        <div id="googleBooksResults" class="google-books-results"></div>
    </div>

    {{-- Main manual book form. Google Books only fills these fields but does not save automatically. --}}
    <form method="POST" action="{{ route('admin.books.store') }}" class="book-form-card">
        @csrf

        <div class="book-form-section">
            <div class="book-section-title">
                <span>01</span>
                <div>
                    <h3>Book Information</h3>
                    <p>These fields can be filled manually or through Google Books lookup.</p>
                </div>
            </div>

            <div class="book-form-grid">
                <div class="book-form-group book-title-field">
                    <label for="book_title">Book Title</label>
                    <input
                        type="text"
                        name="book_title"
                        id="book_title"
                        value="{{ old('book_title') }}"
                        maxlength="255"
                        placeholder="Enter Book Title"
                        required
                    >
                </div>

                <div class="book-form-group">
                    <label for="isbn">ISBN</label>
                    <input
                        type="text"
                        name="isbn"
                        id="isbn"
                        value="{{ old('isbn') }}"
                        maxlength="13"
                        placeholder="Enter ISBN"
                    >
                </div>

                <div class="book-form-group">
                    <label for="book_status">Book Status</label>
                    <select name="book_status" id="book_status" required>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" {{ old('book_status', 'Available') === $status ? 'selected' : '' }}>
                                {{ $status }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="book-form-section">
            <div class="book-section-title">
                <span>02</span>
                <div>
                    <h3>Author, Publisher, and Genre</h3>
                    <p>Select existing records or type new details. New details will be created when you save.</p>
                </div>
            </div>

            <div class="book-form-grid">
                <div class="book-form-group">
                    <label for="author_id">Existing Author</label>
                    <select name="author_id" id="author_id">
                        <option value="">No existing author selected</option>

                        @foreach ($authors as $author)
                            <option value="{{ $author->author_id }}" {{ (string) old('author_id') === (string) $author->author_id ? 'selected' : '' }}>
                                {{ $author->display_name }}
                            </option>
                        @endforeach
                    </select>
                    <small>Leave blank if you want to use a new author name below.</small>
                </div>

                <div class="book-form-group">
                    <label for="author_name">New / Auto-filled Author</label>
                    <input
                        type="text"
                        name="author_name"
                        id="author_name"
                        value="{{ old('author_name') }}"
                        maxlength="150"
                        placeholder="Enter Author Name"
                        pattern="^[a-zA-Z\s\.,\-]+$"
                        title="Only letters, spaces, hyphens, periods, and commas are allowed."
                        oninput="this.value = this.value.replace(/[^a-zA-Z\s\.,\-]/g, '')"
                    >
                </div>

                <div class="book-form-group">
                    <label for="publisher_id">Existing Publisher</label>
                    <select name="publisher_id" id="publisher_id">
                        <option value="">No existing publisher selected</option>

                        @foreach ($publishers as $publisher)
                            <option value="{{ $publisher->publisher_id }}" {{ (string) old('publisher_id') === (string) $publisher->publisher_id ? 'selected' : '' }}>
                                {{ $publisher->display_name }}
                            </option>
                        @endforeach
                    </select>
                    <small>Leave blank if you want to use a new publisher name below.</small>
                </div>

                <div class="book-form-group">
                    <label for="publisher_name">New / Auto-filled Publisher</label>
                    <input
                        type="text"
                        name="publisher_name"
                        id="publisher_name"
                        value="{{ old('publisher_name') }}"
                        maxlength="150"
                        placeholder="Enter Publisher Name"
                        pattern="^[a-zA-Z\s\.,\-]+$"
                        title="Only letters, spaces, hyphens, periods, and commas are allowed."
                        oninput="this.value = this.value.replace(/[^a-zA-Z\s\.,\-]/g, '')"
                    >
                </div>

                <div class="book-form-group">
                    <label for="date_published">Date Published</label>
                    <input
                        type="date"
                        name="date_published"
                        id="date_published"
                        value="{{ old('date_published') }}"
                        required
                    >
                </div>

                <div class="book-form-group">
                    <label for="genre_id">Existing Genre</label>
                    <select name="genre_id" id="genre_id">
                        <option value="">No existing genre selected</option>

                        @foreach ($genres as $genre)
                            <option value="{{ $genre->genre_id }}" {{ (string) old('genre_id') === (string) $genre->genre_id ? 'selected' : '' }}>
                                {{ $genre->genre_name }}
                            </option>
                        @endforeach
                    </select>
                    <small>Leave blank if you want to use a new genre below.</small>
                </div>

                <div class="book-form-group">
                    <label for="genre_name">New / Auto-filled Genre</label>
                    <input
                        type="text"
                        name="genre_name"
                        id="genre_name"
                        value="{{ old('genre_name') }}"
                        maxlength="100"
                        placeholder="Enter Genre"
                        pattern="^[a-zA-Z\s]+$"
                        title="Only letters and spaces are allowed."
                        oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                    >
                </div>
            </div>
        </div>

        <div class="book-form-actions">
            <button type="submit" class="primary-button">
                Save Book
            </button>

            <a href="{{ route('admin.books.index') }}" class="secondary-button">
                Cancel
            </a>
        </div>
    </form>

    <script>
        // Route used by the Google Books lookup AJAX request.
        const googleBooksSearchUrl = @json(route('admin.books.google-books.search'));

        const googleBooksQuery = document.getElementById('googleBooksQuery');
        const googleBooksSearchButton = document.getElementById('googleBooksSearchButton');
        const googleBooksMessage = document.getElementById('googleBooksMessage');
        const googleBooksResults = document.getElementById('googleBooksResults');

        const bookTitleInput = document.getElementById('book_title');
        const isbnInput = document.getElementById('isbn');
        const authorIdSelect = document.getElementById('author_id');
        const authorNameInput = document.getElementById('author_name');
        const publisherIdSelect = document.getElementById('publisher_id');
        const publisherNameInput = document.getElementById('publisher_name');
        const datePublishedInput = document.getElementById('date_published');
        const genreIdSelect = document.getElementById('genre_id');
        const genreNameInput = document.getElementById('genre_name');

        googleBooksSearchButton.addEventListener('click', searchGoogleBooks);

        googleBooksQuery.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchGoogleBooks();
            }
        });

        /**
         * Sends the ISBN/title query to the backend Google Books endpoint.
         */
        async function searchGoogleBooks() {
            const query = googleBooksQuery.value.trim();

            if (!query) {
                showGoogleBooksMessage('Please enter an ISBN or book title.', 'error');
                return;
            }

            googleBooksSearchButton.disabled = true;
            googleBooksSearchButton.textContent = 'Searching...';
            googleBooksResults.innerHTML = '';
            showGoogleBooksMessage('Searching Google Books...', 'info');

            try {
                const response = await fetch(`${googleBooksSearchUrl}?query=${encodeURIComponent(query)}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();

                if (!data.success) {
                    showGoogleBooksMessage(data.message || 'No book found. You can still add manually.', 'error');
                    return;
                }

                showGoogleBooksMessage('Select a result to fill the Add Book form.', 'success');
                renderGoogleBooksResults(data.books || []);
            } catch (error) {
                showGoogleBooksMessage('Google Books lookup failed. You can still add the book manually.', 'error');
            } finally {
                googleBooksSearchButton.disabled = false;
                googleBooksSearchButton.textContent = 'Search Google Books';
            }
        }

        /**
         * Displays Google Books search results as selectable cards.
         */
        function renderGoogleBooksResults(books) {
            googleBooksResults.innerHTML = '';

            books.forEach((book, index) => {
                const card = document.createElement('div');
                card.className = 'google-book-result-card';

                const thumbnail = book.thumbnail
                    ? `<img src="${escapeHtml(book.thumbnail)}" alt="Book cover">`
                    : `<div class="google-book-placeholder">No Cover</div>`;

                card.innerHTML = `
                    <div class="google-book-cover">
                        ${thumbnail}
                    </div>

                    <div class="google-book-info">
                        <h4>${escapeHtml(book.book_title || 'Untitled Book')}</h4>
                        <p><strong>Author:</strong> ${escapeHtml(book.author_name || '-')}</p>
                        <p><strong>Publisher:</strong> ${escapeHtml(book.publisher_name || '-')}</p>
                        <p><strong>Genre:</strong> ${escapeHtml(book.genre_name || '-')}</p>
                        <p><strong>ISBN:</strong> ${escapeHtml(book.isbn || '-')}</p>

                        <button type="button" class="use-google-book-button" data-index="${index}">
                            Use This Book
                        </button>
                    </div>
                `;

                googleBooksResults.appendChild(card);
            });

            document.querySelectorAll('.use-google-book-button').forEach(button => {
                button.addEventListener('click', function () {
                    const selectedBook = books[this.dataset.index];
                    fillBookForm(selectedBook);
                });
            });
        }

        /**
         * Auto-fills the manual form using the selected Google Books result.
         */
        function fillBookForm(book) {
            bookTitleInput.value = book.book_title || '';
            isbnInput.value = book.isbn || '';
            
            // Clean dynamic values against text filters on autofill
            authorNameInput.value = (book.author_name || '').replace(/[^a-zA-Z\s\.,\-]/g, '');
            publisherNameInput.value = (book.publisher_name || '').replace(/[^a-zA-Z\s\.,\-]/g, '');
            genreNameInput.value = (book.genre_name || '').replace(/[^a-zA-Z\s]/g, '');
            datePublishedInput.value = book.date_published || '';

            authorIdSelect.value = '';
            publisherIdSelect.value = '';
            genreIdSelect.value = '';

            showGoogleBooksMessage('Book details filled. Please review before saving.', 'success');

            bookTitleInput.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });
        }

        /**
         * Shows lookup status messages such as success, error, or loading states.
         */
        function showGoogleBooksMessage(message, type) {
            googleBooksMessage.style.display = 'block';
            googleBooksMessage.className = `google-books-message ${type}`;
            googleBooksMessage.textContent = message;
        }

        /**
         * Escapes dynamic Google Books data before inserting it into HTML.
         */
        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }
    </script>
@endsection