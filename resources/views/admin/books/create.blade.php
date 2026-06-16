@extends('layouts.admin')

@section('title', 'Add Book | UBNHS Library Management System')
@section('page-title', 'Add Book')

@section('content')
    @php
        $selectedGenreChoice = old('genre_choice', '');
        $otherGenreValue = old('genre_other', '');
    @endphp

    <div class="page-actions">
        <div>
            <h2>Add Book</h2>
            <p>Register a new library book manually or use Book Lookup to retrieve available catalog information.</p>
        </div>

        <a href="{{ route('admin.books.index') }}" class="secondary-button">
            Back
        </a>
    </div>

    @if ($errors->any())
        <div class="alert error" role="alert">
            <strong>Please correct the following:</strong>

            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="book-lookup-card">
        <div class="book-lookup-header">
            <div>
                <span class="book-lookup-eyebrow">Optional Book Search</span>
                <h3>Book Lookup</h3>
                <p>
                    Search by ISBN, complete title, or title with author. The lookup checks Google Books
                    and Open Library, then combines matching edition details when available.
                </p>
            </div>
        </div>

        <div class="book-lookup-search-row">
            <input
                type="text"
                id="bookLookupQuery"
                maxlength="255"
                placeholder="Enter ISBN, complete title, or title by author"
                autocomplete="off"
            >

            <button type="button" id="bookLookupSearchButton">
                Search Books
            </button>
        </div>

        <div id="bookLookupMessage" class="book-lookup-message" style="display: none;"></div>
        <div id="bookLookupResults" class="book-lookup-results"></div>
    </div>

    <form
        method="POST"
        action="{{ route('admin.books.store') }}"
        class="book-form-card"
        id="addBookForm"
        novalidate
    >
        @csrf

        <input type="hidden" name="book_status" value="Available">

        <div class="book-form-section">
            <div class="book-section-title">
                <span>01</span>

                <div>
                    <h3>Book Information</h3>
                    <p>Enter the complete book title. Add an ISBN only when it is available and can be verified. Every new record starts as Available.</p>
                </div>
            </div>

            <div class="book-form-grid">
                <div class="book-form-group book-title-field">
                    <label for="book_title">
                        Book Title <b class="required-mark">*</b>
                    </label>

                    <input
                        type="text"
                        name="book_title"
                        id="book_title"
                        value="{{ old('book_title') }}"
                        maxlength="255"
                        placeholder="Example: Project Hail Mary"
                        class="{{ $errors->has('book_title') ? 'is-invalid' : '' }}"
                        aria-invalid="{{ $errors->has('book_title') ? 'true' : 'false' }}"
                        required
                    >

                    @error('book_title')
                        <small class="book-field-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="book-form-group">
                    <label for="isbn">
                        ISBN <span class="optional-mark">Optional</span>
                    </label>

                    <input
                        type="text"
                        name="isbn"
                        id="isbn"
                        value="{{ old('isbn') }}"
                        inputmode="numeric"
                        minlength="10"
                        maxlength="13"
                        pattern="(?:[0-9]{10}|[0-9]{13})"
                        title="Enter a valid numeric ISBN-10 or ISBN-13."
                        placeholder="Enter ISBN when available"
                        autocomplete="off"
                        class="{{ $errors->has('isbn') ? 'is-invalid' : '' }}"
                        aria-invalid="{{ $errors->has('isbn') ? 'true' : 'false' }}"
                    >

                    <small>
                        Leave blank when the ISBN is unknown. When provided, enter exactly 10 or 13 digits with a valid check digit.
                    </small>

                    @error('isbn')
                        <small class="book-field-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="book-form-group">
                    <label>Initial Status</label>

                    <div class="book-status-display" role="status" aria-label="Initial book status: Available">
                        <span class="book-status-dot" aria-hidden="true"></span>

                        <div>
                            <strong>Available</strong>
                            <small>New books are saved as Available. Borrowed status is assigned only after a borrowing transaction is created.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="book-form-section">
            <div class="book-section-title">
                <span>02</span>

                <div>
                    <h3>Catalog Details</h3>
                    <p>Select the book's genre and add the author, publisher, or exact publication date only when that information is available.</p>
                </div>
            </div>

            <div class="catalog-guidance" role="note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M12 11v5"></path>
                    <path d="M12 8h.01"></path>
                </svg>

                <p>
                    Genre is required for catalog classification. Author, publisher, ISBN, and publication date may be left blank when the information cannot be verified.
                </p>
            </div>

            <div class="book-form-grid catalog-grid">
                <div class="book-form-group">
                    <label for="author_name">
                        Primary Author <span class="optional-mark">Optional</span>
                    </label>

                    <input
                        type="text"
                        name="author_name"
                        id="author_name"
                        value="{{ old('author_name') }}"
                        maxlength="150"
                        placeholder="Enter author when known"
                        autocomplete="off"
                        class="{{ $errors->has('author_name') ? 'is-invalid' : '' }}"
                        aria-invalid="{{ $errors->has('author_name') ? 'true' : 'false' }}"
                    >

                    <small>Leave blank when the author is unknown. When provided, enter the main author credited on this edition.</small>

                    @error('author_name')
                        <small class="book-field-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="book-form-group">
                    <label for="publisher_name">
                        Publisher <span class="optional-mark">Optional</span>
                    </label>

                    <input
                        type="text"
                        name="publisher_name"
                        id="publisher_name"
                        value="{{ old('publisher_name') }}"
                        maxlength="150"
                        placeholder="Enter publisher when known"
                        autocomplete="off"
                        class="{{ $errors->has('publisher_name') ? 'is-invalid' : '' }}"
                        aria-invalid="{{ $errors->has('publisher_name') ? 'true' : 'false' }}"
                    >

                    <small>Leave blank when the publisher is unknown. When provided, enter the publisher exactly as printed in the book.</small>

                    @error('publisher_name')
                        <small class="book-field-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="book-form-group">
                    <label for="genre_choice">
                        Genre <b class="required-mark">*</b>
                    </label>

                    <select
                        name="genre_choice"
                        id="genre_choice"
                        class="{{ $errors->has('genre_choice') ? 'is-invalid' : '' }}"
                        aria-invalid="{{ $errors->has('genre_choice') ? 'true' : 'false' }}"
                        required
                    >
                        <option value="">Choose the book's genre</option>

                        @foreach ($controlledGenres as $genre)
                            <option value="{{ $genre }}" {{ $selectedGenreChoice === $genre ? 'selected' : '' }}>
                                {{ $genre }}
                            </option>
                        @endforeach

                        <option value="Other" {{ $selectedGenreChoice === 'Other' ? 'selected' : '' }}>
                            Other
                        </option>
                    </select>

                    <small>Select the single genre that best describes the book.</small>

                    @error('genre_choice')
                        <small class="book-field-error">{{ $message }}</small>
                    @enderror
                </div>

                <div
                    class="book-form-group genre-other-field"
                    id="genreOtherGroup"
                    {{ $selectedGenreChoice === 'Other' ? '' : 'hidden' }}
                >
                    <label for="genre_other">
                        Other Genre <b class="required-mark">*</b>
                    </label>

                    <input
                        type="text"
                        name="genre_other"
                        id="genre_other"
                        value="{{ $otherGenreValue }}"
                        maxlength="100"
                        placeholder="Enter the genre name"
                        autocomplete="off"
                        class="{{ $errors->has('genre_other') ? 'is-invalid' : '' }}"
                        aria-invalid="{{ $errors->has('genre_other') ? 'true' : 'false' }}"
                    >

                    <small>Enter a concise genre only when no option in the standard list applies.</small>

                    @error('genre_other')
                        <small class="book-field-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="book-form-group">
                    <label for="date_published">
                        Publication Date <span class="optional-mark">Optional</span>
                    </label>

                    <input
                        type="date"
                        name="date_published"
                        id="date_published"
                        value="{{ old('date_published') }}"
                        max="{{ now()->toDateString() }}"
                        class="{{ $errors->has('date_published') ? 'is-invalid' : '' }}"
                        aria-invalid="{{ $errors->has('date_published') ? 'true' : 'false' }}"
                    >

                    <small>
                        Enter the full publication date only when it is printed or reliably known; otherwise, leave this field blank.
                    </small>

                    @error('date_published')
                        <small class="book-field-error">{{ $message }}</small>
                    @enderror
                </div>
            </div>
        </div>

        <div class="book-form-actions">
            <button type="submit" class="primary-button" id="saveBookButton">
                Save Book
            </button>

            <a href="{{ route('admin.books.index') }}" class="secondary-button">
                Cancel
            </a>
        </div>
    </form>

    <script>
        const bookLookupSearchUrl = @json(route('admin.books.book-lookup.search'));
        const controlledGenres = @json($controlledGenres);

        const addBookForm = document.getElementById('addBookForm');
        const saveBookButton = document.getElementById('saveBookButton');
        const bookLookupQuery = document.getElementById('bookLookupQuery');
        const bookLookupSearchButton = document.getElementById('bookLookupSearchButton');
        const bookLookupMessage = document.getElementById('bookLookupMessage');
        const bookLookupResults = document.getElementById('bookLookupResults');

        const bookTitleInput = document.getElementById('book_title');
        const isbnInput = document.getElementById('isbn');
        const authorNameInput = document.getElementById('author_name');
        const publisherNameInput = document.getElementById('publisher_name');
        const genreChoiceSelect = document.getElementById('genre_choice');
        const genreOtherGroup = document.getElementById('genreOtherGroup');
        const genreOtherInput = document.getElementById('genre_other');
        const datePublishedInput = document.getElementById('date_published');

        const authorPattern = /^(?=.*\p{L})[\p{L}\p{M}\s.'’,-]+$/u;
        const publisherPattern = /^(?=.*\p{L})[\p{L}\p{M}\p{N}\s.,'’&()\-]+$/u;
        const genrePattern = /^(?=.*\p{L})[\p{L}\p{M}\p{N}\s&/,'’\-]+$/u;

        toggleOtherGenre();

        genreChoiceSelect.addEventListener('change', function () {
            toggleOtherGenre();
        });

        isbnInput.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 13);
            this.setCustomValidity('');
        });

        configureCatalogInput(
            authorNameInput,
            /[^\p{L}\p{M}\s.'’,-]/gu,
            authorPattern,
            'Enter a valid author name using letters, spaces, apostrophes, periods, commas, or hyphens only.'
        );

        configureCatalogInput(
            publisherNameInput,
            /[^\p{L}\p{M}\p{N}\s.,'’&()\-]/gu,
            publisherPattern,
            'Enter a valid publisher name using supported company-name characters only.'
        );

        configureCatalogInput(
            genreOtherInput,
            /[^\p{L}\p{M}\p{N}\s&/,'’\-]/gu,
            genrePattern,
            'Enter a valid genre using letters, numbers, spaces, ampersands, slashes, commas, apostrophes, or hyphens only.'
        );

        bookLookupSearchButton.addEventListener('click', searchBookLookup);

        bookLookupQuery.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchBookLookup();
            }
        });

        addBookForm.addEventListener('submit', function (event) {
            validateIsbnField();
            validateCatalogInput(authorNameInput, authorPattern, 'Enter a valid author name.');
            validateCatalogInput(publisherNameInput, publisherPattern, 'Enter a valid publisher name.');

            if (genreChoiceSelect.value === 'Other') {
                validateCatalogInput(genreOtherInput, genrePattern, 'Enter a valid custom genre.');
            } else {
                genreOtherInput.setCustomValidity('');
            }

            if (!this.checkValidity()) {
                event.preventDefault();
                this.reportValidity();
                return;
            }

            saveBookButton.disabled = true;
            saveBookButton.textContent = 'Saving...';
        });

        function toggleOtherGenre() {
            const useOther = genreChoiceSelect.value === 'Other';

            genreOtherGroup.hidden = !useOther;
            genreOtherInput.required = useOther;

            if (!useOther) {
                genreOtherInput.value = '';
                genreOtherInput.setCustomValidity('');
            }
        }

        function validateIsbnField() {
            const isbn = isbnInput.value;

            if (isbn === '') {
                isbnInput.setCustomValidity('');
                return true;
            }

            if (!/^(?:[0-9]{10}|[0-9]{13})$/.test(isbn)) {
                isbnInput.setCustomValidity('Enter exactly 10 or exactly 13 numeric digits.');
                return false;
            }

            if (!isValidIsbn(isbn)) {
                isbnInput.setCustomValidity('Enter a valid ISBN. The ISBN check digit is incorrect.');
                return false;
            }

            isbnInput.setCustomValidity('');
            return true;
        }

        function isValidIsbn(isbn) {
            if (/^[0-9]{10}$/.test(isbn)) {
                let sum = 0;

                for (let index = 0; index < 10; index += 1) {
                    sum += Number(isbn[index]) * (10 - index);
                }

                return sum % 11 === 0;
            }

            if (/^[0-9]{13}$/.test(isbn)) {
                let sum = 0;

                for (let index = 0; index < 12; index += 1) {
                    sum += Number(isbn[index]) * (index % 2 === 0 ? 1 : 3);
                }

                const expectedCheckDigit = (10 - (sum % 10)) % 10;

                return expectedCheckDigit === Number(isbn[12]);
            }

            return false;
        }

        function configureCatalogInput(input, disallowedCharacters, validationPattern, validationMessage) {
            input.addEventListener('input', function () {
                this.value = this.value.replace(disallowedCharacters, '');
                this.setCustomValidity('');
            });

            input.addEventListener('blur', function () {
                this.value = normalizeSpaces(this.value);
                validateCatalogInput(this, validationPattern, validationMessage);
            });
        }

        function validateCatalogInput(input, validationPattern, validationMessage) {
            const value = normalizeSpaces(input.value);
            input.value = value;

            if (input.required && value === '') {
                input.setCustomValidity('This field is required.');
                return false;
            }

            if (value !== '' && !validationPattern.test(value)) {
                input.setCustomValidity(validationMessage);
                return false;
            }

            input.setCustomValidity('');
            return true;
        }

        function normalizeSpaces(value) {
            return String(value || '').replace(/\s+/g, ' ').trim();
        }

        async function searchBookLookup() {
            const query = normalizeSpaces(bookLookupQuery.value);

            if (query.length < 2) {
                showBookLookupMessage('Enter at least two characters, an ISBN, or a complete book title.', 'error');
                bookLookupQuery.focus();
                return;
            }

            const controller = new AbortController();
            const timeoutId = window.setTimeout(() => controller.abort(), 35000);

            bookLookupSearchButton.disabled = true;
            bookLookupSearchButton.textContent = 'Searching...';
            bookLookupResults.innerHTML = '';
            showBookLookupMessage(
                'Searching Google Books and Open Library, then checking matching editions...',
                'info'
            );

            try {
                const response = await fetch(`${bookLookupSearchUrl}?query=${encodeURIComponent(query)}`, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: controller.signal,
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const validationMessage = data?.errors?.query?.[0];
                    throw new Error(validationMessage || data.message || 'The book lookup request could not be completed.');
                }

                if (!data.success || !Array.isArray(data.books) || data.books.length === 0) {
                    showBookLookupMessage(
                        data.message || 'No matching book was found. Try the exact ISBN, complete title, or include the author name.',
                        'error'
                    );
                    return;
                }

                showBookLookupMessage(
                    data.message || 'Select the best matching edition to fill the Add Book form.',
                    'success'
                );
                renderBookLookupResults(data.books);
            } catch (error) {
                const message = error?.name === 'AbortError'
                    ? 'The lookup took too long. Check the internet connection and search again.'
                    : (error?.message || 'Book lookup failed. Check the connection or add the book manually.');

                showBookLookupMessage(message, 'error');
            } finally {
                window.clearTimeout(timeoutId);
                bookLookupSearchButton.disabled = false;
                bookLookupSearchButton.textContent = 'Search Books';
            }
        }

        function renderBookLookupResults(books) {
            bookLookupResults.innerHTML = '';

            books.forEach((book, index) => {
                const card = document.createElement('article');
                card.className = 'book-lookup-result-card';

                const thumbnail = book.thumbnail
                    ? `<img src="${escapeHtml(book.thumbnail)}" alt="Cover of ${escapeHtml(book.book_title || 'book')}" loading="lazy">`
                    : '<div class="book-lookup-placeholder">No Cover</div>';

                const publicationLabel = book.date_published
                    ? 'Publication Date'
                    : (book.publication_year ? 'Publication Year' : 'Publication Date');
                const publicationValue = book.date_published || book.publication_year || 'Not available';
                const availableCount = Number(book.metadata_available_count ?? 0);
                const totalCount = Number(book.metadata_total_count ?? 4);
                const isComplete = Boolean(book.is_complete);
                const missingFields = Array.isArray(book.missing_fields) ? book.missing_fields : [];
                const completenessText = isComplete
                    ? 'Complete key details'
                    : `${availableCount} of ${totalCount} key details found`;
                const missingText = missingFields.length > 0
                    ? `Missing: ${missingFields.join(', ')}`
                    : 'ISBN, author, publisher, and genre found';

                card.innerHTML = `
                    <div class="book-lookup-cover">${thumbnail}</div>

                    <div class="book-lookup-info">
                        <div class="book-lookup-title-row">
                            <h4>${escapeHtml(book.book_title || 'Untitled Book')}</h4>
                            <span class="book-lookup-completeness ${isComplete ? 'is-complete' : ''}">
                                ${escapeHtml(completenessText)}
                            </span>
                        </div>

                        <p class="book-lookup-source"><strong>Sources:</strong> ${escapeHtml(book.source || 'Book lookup')}</p>

                        <div class="book-lookup-metadata-grid">
                            ${metadataItem('Author', book.author_name)}
                            ${metadataItem('Publisher', book.publisher_name)}
                            ${metadataItem('Genre', book.genre_name)}
                            ${metadataItem(publicationLabel, publicationValue)}
                            ${metadataItem('ISBN', book.isbn)}
                        </div>

                        <p class="book-lookup-missing-note ${isComplete ? 'is-complete' : ''}">
                            ${escapeHtml(missingText)}
                        </p>

                        <button type="button" class="use-book-result-button" data-index="${index}">
                            Use Book Details
                        </button>
                    </div>
                `;

                bookLookupResults.appendChild(card);
            });

            bookLookupResults.querySelectorAll('.use-book-result-button').forEach(button => {
                button.addEventListener('click', function () {
                    fillBookForm(books[Number(this.dataset.index)]);
                });
            });
        }

        function metadataItem(label, value) {
            const hasValue = normalizeSpaces(value || '') !== '';

            return `
                <div class="book-lookup-metadata-item ${hasValue ? '' : 'is-missing'}">
                    <span>${escapeHtml(label)}</span>
                    <strong>${escapeHtml(hasValue ? value : 'Not available')}</strong>
                </div>
            `;
        }

        function fillBookForm(book) {
            bookTitleInput.value = normalizeSpaces(book.book_title || '');
            isbnInput.value = isValidIsbn(String(book.isbn || '')) ? book.isbn : '';

            authorNameInput.value = sanitizeValue(
                book.author_name || '',
                /[^\p{L}\p{M}\s.'’,-]/gu
            );

            publisherNameInput.value = sanitizeValue(
                book.publisher_name || '',
                /[^\p{L}\p{M}\p{N}\s.,'’&()\-]/gu
            );

            applyLookupGenre(book.genre_name || '');
            datePublishedInput.value = /^\d{4}-\d{2}-\d{2}$/.test(book.date_published || '')
                ? book.date_published
                : '';

            const missingFields = Array.isArray(book.missing_fields) ? book.missing_fields : [];
            const reviewText = missingFields.length > 0
                ? `Book details were filled. Review the form and provide ${missingFields.join(', ')} manually when known.`
                : 'Complete book details were filled. Review the information before saving.';

            showBookLookupMessage(reviewText, 'success');

            bookTitleInput.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });
        }

        function applyLookupGenre(rawGenre) {
            const genre = normalizeSpaces(rawGenre);

            if (!genre) {
                genreChoiceSelect.value = '';
                toggleOtherGenre();
                return;
            }

            const normalizedGenre = genre.toLowerCase();
            const mappings = [
                ['Science Fiction', ['science fiction', 'sci-fi', 'science-fiction']],
                ['Fantasy', ['fantasy']],
                ['Mystery & Thriller', ['mystery', 'thriller', 'detective', 'suspense']],
                ['Romance', ['romance']],
                ['Biography & Autobiography', ['biography', 'autobiography', 'memoir']],
                ['History', ['history', 'historical']],
                ['Technology & Computing', ['technology', 'computers', 'computer', 'programming']],
                ['Science', ['science']],
                ['Reference', ['reference', 'encyclopedia', 'dictionary']],
                ['Young Adult', ['young adult', 'juvenile fiction']],
                ["Children's Literature", ['children', 'juvenile literature', 'picture books']],
                ['Poetry', ['poetry']],
                ['Drama', ['drama', 'plays']],
                ['Religion', ['religion', 'religious']],
                ['Self-Help', ['self-help', 'self help']],
                ['Education', ['education', 'teaching']],
                ['Nonfiction', ['nonfiction', 'non-fiction']],
                ['Fiction', ['fiction']],
            ];

            const mapped = mappings.find(([, keywords]) =>
                keywords.some(keyword => normalizedGenre.includes(keyword))
            );

            if (mapped && controlledGenres.includes(mapped[0])) {
                genreChoiceSelect.value = mapped[0];
                toggleOtherGenre();
                return;
            }

            genreChoiceSelect.value = 'Other';
            toggleOtherGenre();
            genreOtherInput.value = sanitizeValue(
                genre,
                /[^\p{L}\p{M}\p{N}\s&/,'’\-]/gu
            );
        }

        function sanitizeValue(value, disallowedCharacters) {
            return normalizeSpaces(String(value || '').replace(disallowedCharacters, ''));
        }

        function showBookLookupMessage(message, type) {
            bookLookupMessage.style.display = 'block';
            bookLookupMessage.className = `book-lookup-message ${type}`;
            bookLookupMessage.textContent = message;
        }

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
