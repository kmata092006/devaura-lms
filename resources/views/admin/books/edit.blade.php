@extends('layouts.admin')

@section('title', 'Edit Book | UBNHS Library Management System')
@section('page-title', 'Edit Book')

@section('content')
    @php
        // Restore editable status and genre selections after validation errors.
        $selectedBookStatus = old('book_status', $book->book_status);
        $hasActiveTransaction = (bool) ($book->has_active_transaction ?? false);
        $storedGenre = trim((string) ($book->genre_name ?? ''));
        $selectedGenreChoice = old('genre_choice');

        if ($selectedGenreChoice === null) {
            if ($storedGenre === '') {
                $selectedGenreChoice = '';
            } else {
                $selectedGenreChoice = in_array($storedGenre, $controlledGenres, true)
                    ? $storedGenre
                    : 'Other';
            }
        }

        $otherGenreValue = old(
            'genre_other',
            $selectedGenreChoice === 'Other' ? $storedGenre : ''
        );
    @endphp

    {{-- Introduces the edit workflow and links back to book management. --}}
    <div class="page-actions">
        <div>
            <h2>Edit Book</h2>
            <p>Review and update the selected book record and its catalog information.</p>
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

    {{-- Updates one book while respecting active borrowing transaction rules. --}}
    <form
        method="POST"
        action="{{ route('admin.books.update', $book->book_id) }}"
        class="book-form-card"
        id="editBookForm"
        data-has-active-transaction="{{ $hasActiveTransaction ? '1' : '0' }}"
        novalidate
    >
        @csrf
        @method('PUT')

        <div class="book-form-section">
            <div class="book-section-title">
                <span>01</span>

                <div>
                    <h3>Book Information</h3>
                    <p>Review the complete title and current circulation status. Keep the ISBN only when it is available and can be verified.</p>
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
                        value="{{ old('book_title', $book->book_title) }}"
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
                        value="{{ old('isbn', $book->isbn) }}"
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
                    <label for="book_status">
                        Book Status <b class="required-mark">*</b>
                    </label>

                    <select
                        name="book_status"
                        id="book_status"
                        class="{{ $errors->has('book_status') ? 'is-invalid' : '' }}"
                        aria-invalid="{{ $errors->has('book_status') ? 'true' : 'false' }}"
                        required
                    >
                        @foreach ($statuses as $status)
                            @php
                                $statusDisabled = $hasActiveTransaction
                                    ? !in_array($status, ['Borrowed', 'Available'], true)
                                    : $status === 'Borrowed';
                            @endphp

                            <option
                                value="{{ $status }}"
                                {{ $selectedBookStatus === $status ? 'selected' : '' }}
                                {{ $statusDisabled ? 'disabled' : '' }}
                            >
                                {{ $status }}

                                @if (!$hasActiveTransaction && $status === 'Borrowed')
                                    — use Borrow Book module
                                @elseif ($hasActiveTransaction && !in_array($status, ['Borrowed', 'Available'], true))
                                    — use Return Book module
                                @endif
                            </option>
                        @endforeach
                    </select>

                    @if ($hasActiveTransaction)
                        <small>
                            An active borrowing transaction exists. Selecting Available will complete the return
                            and update the book status across Book Management, Borrow Book, Return Book, and Book List/Search.
                        </small>
                    @else
                        <small>Borrowed status is assigned only after a borrowing transaction is created in the Borrow Book module.</small>
                    @endif

                    @error('book_status')
                        <small class="book-field-error">{{ $message }}</small>
                    @enderror
                </div>
            </div>
        </div>

        <div class="book-form-section">
            <div class="book-section-title">
                <span>02</span>

                <div>
                    <h3>Catalog Details</h3>
                    <p>Review the book's genre and keep the author, publisher, or exact publication date only when that information is available.</p>
                </div>
            </div>

            <div class="catalog-guidance" role="note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M12 11v5"></path>
                    <path d="M12 8h.01"></path>
                </svg>

                <p>
                    Genre is required for catalog classification. Author, publisher, ISBN, and publication date may be cleared when the information cannot be verified.
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
                        value="{{ old('author_name', $book->author_name) }}"
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
                        value="{{ old('publisher_name', $book->publisher_name) }}"
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
                        value="{{ old('date_published', $book->date_published) }}"
                        max="{{ now()->toDateString() }}"
                        class="{{ $errors->has('date_published') ? 'is-invalid' : '' }}"
                        aria-invalid="{{ $errors->has('date_published') ? 'true' : 'false' }}"
                    >

                    <small>Enter the full publication date only when it is printed or reliably known; otherwise, leave this field blank.</small>

                    @error('date_published')
                        <small class="book-field-error">{{ $message }}</small>
                    @enderror
                </div>
            </div>
        </div>

        <div class="book-form-actions">
            <button type="submit" class="primary-button" id="updateBookButton">
                Update Book
            </button>

            <a href="{{ route('admin.books.index') }}" class="secondary-button">
                Cancel
            </a>
        </div>
    </form>

    <script>
        // Cache form controls used for status, metadata, and validation behavior.
        const editBookForm = document.getElementById('editBookForm');
        const updateBookButton = document.getElementById('updateBookButton');
        const isbnInput = document.getElementById('isbn');
        const bookStatusSelect = document.getElementById('book_status');
        const authorNameInput = document.getElementById('author_name');
        const publisherNameInput = document.getElementById('publisher_name');
        const genreChoiceSelect = document.getElementById('genre_choice');
        const genreOtherGroup = document.getElementById('genreOtherGroup');
        const genreOtherInput = document.getElementById('genre_other');

        const authorPattern = /^(?=.*\p{L})[\p{L}\p{M}\s.'’,-]+$/u;
        const publisherPattern = /^(?=.*\p{L})[\p{L}\p{M}\p{N}\s.,'’&()\-]+$/u;
        const genrePattern = /^(?=.*\p{L})[\p{L}\p{M}\p{N}\s&/,'’\-]+$/u;

        // Initialize the custom genre field and validation hooks.
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

        // Validate fields and confirm transaction closure before saving status changes.
        editBookForm.addEventListener('submit', function (event) {
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

            const hasActiveTransaction = this.dataset.hasActiveTransaction === '1';

            if (hasActiveTransaction && bookStatusSelect.value === 'Available') {
                const confirmed = window.confirm(
                    'This book has an active borrowing transaction. Changing the status to Available will mark that transaction as Returned and remove it from the Return Book queue. Continue?'
                );

                if (!confirmed) {
                    event.preventDefault();
                    return;
                }
            }

            updateBookButton.disabled = true;
            updateBookButton.textContent = 'Updating...';
        });

        // Shows the custom genre input only when Other is selected.
        function toggleOtherGenre() {
            const useOther = genreChoiceSelect.value === 'Other';

            genreOtherGroup.hidden = !useOther;
            genreOtherInput.required = useOther;

            if (!useOther) {
                genreOtherInput.value = '';
                genreOtherInput.setCustomValidity('');
            }
        }

        // Accepts blank ISBNs, but validates length and check digit when provided.
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

        // Validates ISBN-10 and ISBN-13 check digits.
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

        // Sanitizes and validates catalog text fields as the user edits them.
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

        // Applies the required and pattern rules to one catalog text input.
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

        // Collapses repeated spaces before display, validation, and storage.
        function normalizeSpaces(value) {
            return String(value || '').replace(/\s+/g, ' ').trim();
        }
    </script>
@endsection
