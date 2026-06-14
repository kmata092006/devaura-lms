<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BookLookupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookController extends Controller
{
    /**
     * Defines the valid status options used when adding, editing, and filtering books.
     */
    private array $bookStatuses = [
        'Available',
        'Borrowed',
        'Reserved',
        'Lost',
        'Damaged',
        'Under Repair',
    ];


    /**
     * Uses one consistent set of catalog genres instead of reusing arbitrary
     * values previously entered into the database.
     */
    private array $controlledGenres = [
        'Fiction',
        'Nonfiction',
        'Science Fiction',
        'Fantasy',
        'Mystery & Thriller',
        'Romance',
        'Biography & Autobiography',
        'History',
        'Science',
        'Technology & Computing',
        'Reference',
        'Young Adult',
        "Children's Literature",
        'Poetry',
        'Drama',
        'Religion',
        'Self-Help',
        'Education',
    ];

    /**
     * Displays the book list with search, filter, pagination, and related author/publisher/genre data.
     */
    public function index(Request $request)
    {
        // Builds the main book query with joined author, publisher, and genre details.
        $query = DB::table('book as book')
            ->leftJoin('book_author as author', 'book.author_id', '=', 'author.author_id')
            ->leftJoin('book_publisher as publisher', 'book.publisher_id', '=', 'publisher.publisher_id')
            ->leftJoin('book_genre as genre', 'book.genre_id', '=', 'genre.genre_id')
            ->select(
                'book.book_id',
                'book.book_title',
                'book.isbn',
                'book.book_status',
                'book.author_id',
                'book.publisher_id',
                'book.genre_id',
                'genre.genre_name',
                'publisher.date_published',
                'author.last_name as author_last_name',
                'author.first_name as author_first_name',
                DB::raw('author.`m.i.` as author_middle_initial'),
                'publisher.last_name as publisher_last_name',
                'publisher.first_name as publisher_first_name',
                DB::raw('publisher.`m.i.` as publisher_middle_initial')
            )
            ->selectSub(function ($subquery) {
                $subquery
                    ->from('borrow_transaction')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('borrow_transaction.book_id', 'book.book_id');
            }, 'borrow_transaction_count')
            ->selectSub(function ($subquery) {
                $subquery
                    ->from('borrow_transaction')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('borrow_transaction.book_id', 'book.book_id')
                    ->whereIn('borrow_transaction.borrow_status', ['Borrowed', 'Overdue']);
            }, 'active_borrow_transaction_count');

        // Applies keyword search across book, author, publisher, genre, and status fields.
        if ($request->filled('search')) {
            $search = trim($request->query('search'));

            $query->where(function ($q) use ($search) {
                $q->where('book.book_title', 'like', "%{$search}%")
                    ->orWhere('book.isbn', 'like', "%{$search}%")
                    ->orWhere('book.book_status', 'like', "%{$search}%")
                    ->orWhere('genre.genre_name', 'like', "%{$search}%")
                    ->orWhere('author.last_name', 'like', "%{$search}%")
                    ->orWhere('author.first_name', 'like', "%{$search}%")
                    ->orWhereRaw('author.`m.i.` LIKE ?', ["%{$search}%"])
                    ->orWhere('publisher.last_name', 'like', "%{$search}%")
                    ->orWhere('publisher.first_name', 'like', "%{$search}%")
                    ->orWhereRaw('publisher.`m.i.` LIKE ?', ["%{$search}%"]);
            });
        }

        // Applies dropdown filters when selected by the user.
        if ($request->filled('status')) {
            $query->where('book.book_status', $request->query('status'));
        }

        if ($request->filled('genre_id')) {
            $query->where('book.genre_id', $request->query('genre_id'));
        }

        if ($request->filled('author_id')) {
            $query->where('book.author_id', $request->query('author_id'));
        }

        if ($request->filled('publisher_id')) {
            $query->where('book.publisher_id', $request->query('publisher_id'));
        }

        $books = $query
            ->orderBy('book.book_title')
            ->paginate(10)
            ->withQueryString();

        // Formats joined author and publisher fields into readable display names.
        $books->getCollection()->transform(function ($book) {
            $book->author_name = $this->formatPersonName(
                $book->author_last_name,
                $book->author_first_name,
                $book->author_middle_initial
            );

            $book->publisher_name = $this->formatPersonName(
                $book->publisher_last_name,
                $book->publisher_first_name,
                $book->publisher_middle_initial
            );

            $book->genre_name = $book->genre_name ?? '-';

            return $book;
        });

        $authors = $this->getAuthors();
        $publishers = $this->getPublishers();
        $genres = $this->getGenres();
        $statuses = $this->bookStatuses;

        return view('admin.books.index', compact(
            'books',
            'authors',
            'publishers',
            'genres',
            'statuses'
        ));
    }

    /**
     * Shows the Add Book form. New books always begin as Available, while the
     * genre field uses only the approved controlled list plus Other.
     */
    public function create()
    {
        $controlledGenres = $this->controlledGenres;

        return view('admin.books.create', compact('controlledGenres'));
    }

    /**
     * Validates and stores a new book record. ISBN, author, publisher, and
     * publication date may be omitted when the information is unavailable.
     * Genre remains required for consistent catalog classification.
     */
    public function store(Request $request)
    {
        // A new catalog record cannot begin in a transaction-based status.
        $request->merge(['book_status' => 'Available']);
        $this->normalizeBookRequest($request);

        $validated = $request->validate(
            $this->bookValidationRules(isCreate: true),
            $this->bookValidationMessages()
        );

        DB::transaction(function () use ($validated) {
            $authorId = $this->findOrCreateAuthor($validated['author_name'] ?? null);
            $publisherId = $this->findOrCreatePublisher(
                $validated['publisher_name'] ?? null,
                $validated['date_published'] ?? null
            );
            $genreId = $this->findOrCreateGenre($validated['genre_name']);

            DB::table('book')->insert([
                'author_id' => $authorId,
                'publisher_id' => $publisherId,
                'genre_id' => $genreId,
                'isbn' => $this->cleanIsbn($validated['isbn'] ?? null),
                'book_title' => $validated['book_title'],
                'book_status' => $validated['book_status'],
            ]);
        });

        return redirect()
            ->route('admin.books.index')
            ->with('success', 'Book added successfully.');
    }

    /**
     * Shows the edit form for a selected book record.
     */
    public function edit($bookId)
    {
        $book = DB::table('book as book')
            ->leftJoin('book_author as author', 'book.author_id', '=', 'author.author_id')
            ->leftJoin('book_publisher as publisher', 'book.publisher_id', '=', 'publisher.publisher_id')
            ->leftJoin('book_genre as genre', 'book.genre_id', '=', 'genre.genre_id')
            ->select(
                'book.book_id',
                'book.book_title',
                'book.isbn',
                'book.book_status',
                'genre.genre_name',
                'publisher.date_published',
                'author.last_name as author_last_name',
                'author.first_name as author_first_name',
                DB::raw('author.`m.i.` as author_middle_initial'),
                'publisher.last_name as publisher_last_name',
                'publisher.first_name as publisher_first_name',
                DB::raw('publisher.`m.i.` as publisher_middle_initial')
            )
            ->where('book.book_id', $bookId)
            ->first();

        if (!$book) {
            return redirect()
                ->route('admin.books.index')
                ->withErrors(['book' => 'Book record not found.']);
        }

        $formattedAuthorName = $this->formatPersonName(
            $book->author_last_name,
            $book->author_first_name,
            $book->author_middle_initial
        );

        $formattedPublisherName = $this->formatPersonName(
            $book->publisher_last_name,
            $book->publisher_first_name,
            $book->publisher_middle_initial
        );

        $book->author_name = $formattedAuthorName === '-' ? '' : $formattedAuthorName;
        $book->publisher_name = $formattedPublisherName === '-' ? '' : $formattedPublisherName;

        $transactionSummary = DB::table('borrow_transaction')
            ->where('book_id', $bookId)
            ->selectRaw('COUNT(*) as total_transactions')
            ->selectRaw(
                "SUM(CASE WHEN borrow_status IN ('Borrowed', 'Overdue') THEN 1 ELSE 0 END) as active_transactions"
            )
            ->first();

        $book->borrow_transaction_count = (int) ($transactionSummary->total_transactions ?? 0);
        $book->active_borrow_transaction_count = (int) ($transactionSummary->active_transactions ?? 0);
        $book->has_transaction_history = $book->borrow_transaction_count > 0;
        $book->has_active_transaction = $book->active_borrow_transaction_count > 0;

        $statuses = $this->bookStatuses;
        $controlledGenres = $this->controlledGenres;

        return view('admin.books.edit', compact(
            'book',
            'statuses',
            'controlledGenres'
        ));
    }

    /**
     * Validates and updates an existing book record without mutating shared
     * catalog records used by other books. Once borrowing history exists, the
     * circulation status is preserved and can only change through transactions.
     */
    public function update(Request $request, $bookId)
    {
        $this->normalizeBookRequest($request);

        $validated = $request->validate(
            $this->bookValidationRules(isCreate: false, bookId: (int) $bookId),
            $this->bookValidationMessages()
        );

        DB::transaction(function () use ($bookId, $validated) {
            $book = DB::table('book')
                ->where('book_id', $bookId)
                ->lockForUpdate()
                ->first();

            if (!$book) {
                throw ValidationException::withMessages([
                    'book' => 'Book record not found.',
                ]);
            }

            $transactionHistory = DB::table('borrow_transaction')
                ->where('book_id', $bookId)
                ->select('transaction_id', 'borrow_status')
                ->lockForUpdate()
                ->get();

            $hasTransactionHistory = $transactionHistory->isNotEmpty();
            $requestedStatus = $validated['book_status'];

            if ($hasTransactionHistory && $requestedStatus !== $book->book_status) {
                throw ValidationException::withMessages([
                    'book_status' => 'This book status is managed by borrowing and return transactions. Lost and Damaged books cannot be changed back to Available from the Edit Book page.',
                ]);
            }

            if (!$hasTransactionHistory && $requestedStatus === 'Borrowed') {
                throw ValidationException::withMessages([
                    'book_status' => 'A book can only become Borrowed through the Borrow Book module so a member and due date are recorded.',
                ]);
            }

            $statusToSave = $hasTransactionHistory
                ? $book->book_status
                : $requestedStatus;

            $authorId = $this->findOrCreateAuthor($validated['author_name'] ?? null);
            $publisherId = $this->findOrCreatePublisher(
                $validated['publisher_name'] ?? null,
                $validated['date_published'] ?? null
            );
            $genreId = $this->findOrCreateGenre($validated['genre_name']);

            DB::table('book')
                ->where('book_id', $bookId)
                ->update([
                    'author_id' => $authorId,
                    'publisher_id' => $publisherId,
                    'genre_id' => $genreId,
                    'isbn' => $this->cleanIsbn($validated['isbn'] ?? null),
                    'book_title' => $validated['book_title'],
                    'book_status' => $statusToSave,
                ]);
        });

        return redirect()
            ->route('admin.books.index')
            ->with('success', 'Book updated successfully. The transaction-managed status was preserved.');
    }

    /**
     * Deletes a book after all active borrowing transactions have been closed.
     * Completed transaction rows are removed with the book so the database does
     * not retain orphaned references to a deleted catalog record.
     */
    public function destroy($bookId)
    {
        try {
            $deletedCompletedTransactions = DB::transaction(function () use ($bookId) {
                $book = DB::table('book')
                    ->where('book_id', $bookId)
                    ->lockForUpdate()
                    ->first();

                if (!$book) {
                    throw ValidationException::withMessages([
                        'book' => 'Book record not found.',
                    ]);
                }

                $activeTransaction = DB::table('borrow_transaction')
                    ->where('book_id', $bookId)
                    ->whereIn('borrow_status', ['Borrowed', 'Overdue'])
                    ->lockForUpdate()
                    ->first();

                if ($book->book_status === 'Borrowed' || $activeTransaction !== null) {
                    throw ValidationException::withMessages([
                        'book' => 'This book cannot be deleted while it has an active borrowing transaction.',
                    ]);
                }

                $completedTransactionCount = DB::table('borrow_transaction')
                    ->where('book_id', $bookId)
                    ->count();

                if ($completedTransactionCount > 0) {
                    DB::table('borrow_transaction')
                        ->where('book_id', $bookId)
                        ->delete();
                }

                DB::table('book')
                    ->where('book_id', $bookId)
                    ->delete();

                return $completedTransactionCount;
            });
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.books.index')
                ->withErrors($exception->errors());
        }

        $message = $deletedCompletedTransactions > 0
            ? 'Book and completed borrowing history deleted successfully.'
            : 'Book deleted successfully.';

        return redirect()
            ->route('admin.books.index')
            ->with('success', $message);
    }

    /**
     * Handles AJAX requests for the multi-provider book lookup and returns matched book details as JSON.
     */
    public function searchBookLookup(Request $request, BookLookupService $bookLookupService)
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:255'],
        ]);

        return response()->json(
            $bookLookupService->search($validated['query'])
        );
    }

    /**
     * Retrieves authors and prepares their formatted display names for dropdowns.
     */
    private function getAuthors()
    {
        return DB::table('book_author')
            ->select(
                'author_id',
                'last_name',
                'first_name',
                DB::raw('`m.i.` as middle_initial')
            )
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(function ($author) {
                $author->display_name = $this->formatPersonName(
                    $author->last_name,
                    $author->first_name,
                    $author->middle_initial
                );

                return $author;
            });
    }

    /**
     * Retrieves publishers and prepares their formatted display names for dropdowns.
     */
    private function getPublishers()
    {
        return DB::table('book_publisher')
            ->select(
                'publisher_id',
                'last_name',
                'first_name',
                DB::raw('`m.i.` as middle_initial'),
                'date_published'
            )
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(function ($publisher) {
                $publisher->display_name = $this->formatPersonName(
                    $publisher->last_name,
                    $publisher->first_name,
                    $publisher->middle_initial
                );

                return $publisher;
            });
    }

    /**
     * Retrieves all book genres in alphabetical order.
     */
    private function getGenres()
    {
        return DB::table('book_genre')
            ->orderBy('genre_name')
            ->get();
    }

    /**
     * Finds an existing author by name or creates a new author record.
     */
    private function findOrCreateAuthor(?string $authorName): ?int
    {
        $authorName = $this->normalizeText($authorName);

        if ($authorName === '') {
            return null;
        }

        [$firstName, $lastName] = $this->splitPersonName($authorName);

        // Checks for duplicate author records using normalized first and last names.
        $existingAuthor = DB::table('book_author')
            ->whereRaw('LOWER(TRIM(COALESCE(first_name, ""))) = ?', [mb_strtolower($firstName ?? '')])
            ->whereRaw('LOWER(TRIM(COALESCE(last_name, ""))) = ?', [mb_strtolower($lastName ?? '')])
            ->first();

        if ($existingAuthor) {
            return (int) $existingAuthor->author_id;
        }

        DB::insert(
            'INSERT INTO book_author (last_name, first_name, `m.i.`) VALUES (?, ?, ?)',
            [
                $lastName,
                $firstName,
                null,
            ]
        );

        return (int) DB::getPdo()->lastInsertId();
    }

    /**
     * Finds an existing publisher by its displayed name or creates a new
     * company-style publisher record without incorrectly splitting the name.
     */
    private function findOrCreatePublisher(?string $publisherName, ?string $datePublished = null): ?int
    {
        $publisherName = $this->normalizeText((string) $publisherName);
        $datePublished = $datePublished !== null && trim($datePublished) !== ''
            ? trim($datePublished)
            : null;

        if ($publisherName === '' && $datePublished === null) {
            return null;
        }

        // The current database stores the publication date on the publisher
        // record. When the publisher name is unknown but a date is known, a
        // date-only metadata record is reused instead of inventing a name.
        if ($publisherName === '') {
            $dateOnlyPublisher = DB::table('book_publisher')
                ->whereNull('last_name')
                ->whereNull('first_name')
                ->whereRaw('`m.i.` IS NULL')
                ->where('date_published', $datePublished)
                ->first();

            if ($dateOnlyPublisher) {
                return (int) $dateOnlyPublisher->publisher_id;
            }

            DB::insert(
                'INSERT INTO book_publisher (last_name, first_name, `m.i.`, date_published) VALUES (?, ?, ?, ?)',
                [null, null, null, $datePublished]
            );

            return (int) DB::getPdo()->lastInsertId();
        }

        $normalizedTarget = $this->normalizeComparableText($publisherName);

        $existingPublisher = DB::table('book_publisher')
            ->select(
                'publisher_id',
                'last_name',
                'first_name',
                DB::raw('`m.i.` as middle_initial'),
                'date_published'
            )
            ->get()
            ->first(function ($publisher) use ($normalizedTarget, $datePublished) {
                $displayName = $this->formatPersonName(
                    $publisher->last_name,
                    $publisher->first_name,
                    $publisher->middle_initial
                );

                $sameName = $this->normalizeComparableText($displayName) === $normalizedTarget;
                $sameDate = (string) ($publisher->date_published ?? '') === (string) ($datePublished ?? '');

                return $sameName && $sameDate;
            });

        if ($existingPublisher) {
            return (int) $existingPublisher->publisher_id;
        }

        DB::insert(
            'INSERT INTO book_publisher (last_name, first_name, `m.i.`, date_published) VALUES (?, ?, ?, ?)',
            [null, $publisherName, null, $datePublished]
        );

        return (int) DB::getPdo()->lastInsertId();
    }

    /**
     * Finds an existing genre by name or creates a new genre record.
     */
    private function findOrCreateGenre(string $genreName): ?int
    {
        $genreName = $this->normalizeText($genreName);

        if ($genreName === '') {
            return null;
        }

        // Prevents duplicate genre records by comparing normalized genre names.
        $existingGenre = DB::table('book_genre')
            ->whereRaw('LOWER(TRIM(genre_name)) = ?', [mb_strtolower($genreName)])
            ->first();

        if ($existingGenre) {
            return (int) $existingGenre->genre_id;
        }

        DB::table('book_genre')->insert([
            'genre_name' => $genreName,
        ]);

        return (int) DB::getPdo()->lastInsertId();
    }

    /**
     * Splits a full name into first name and last name for author storage.
     */
    private function splitPersonName(string $name): array
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));

        if ($name === '') {
            return [null, null];
        }

        // Supports names entered as "Last Name, First Name".
        if (str_contains($name, ',')) {
            $parts = array_map('trim', explode(',', $name, 2));

            $lastName = $parts[0] ?: null;
            $firstName = $parts[1] ?? null;

            return [$firstName, $lastName];
        }

        $parts = explode(' ', $name);

        if (count($parts) === 1) {
            return [$name, null];
        }

        // Uses the last word as the last name when the name is entered normally.
        $lastName = array_pop($parts);
        $firstName = implode(' ', $parts);

        return [$firstName ?: null, $lastName ?: null];
    }

    /**
     * Formats first, middle initial, and last name fields into one readable name.
     */
    private function formatPersonName(?string $lastName, ?string $firstName, ?string $middleInitial): string
    {
        $lastName = trim($lastName ?? '');
        $firstName = trim($firstName ?? '');
        $middleInitial = trim($middleInitial ?? '');

        $middleInitial = rtrim($middleInitial, '.');

        if ($firstName !== '' && $lastName !== '') {
            return $middleInitial !== ''
                ? "{$lastName}, {$firstName} {$middleInitial}."
                : "{$lastName}, {$firstName}";
        }

        if ($firstName !== '') {
            return $middleInitial !== ''
                ? "{$firstName} {$middleInitial}."
                : $firstName;
        }

        if ($lastName !== '') {
            return $middleInitial !== ''
                ? "{$lastName} {$middleInitial}."
                : $lastName;
        }

        return '-';
    }

    /**
     * Normalizes user-entered text before validation so duplicate spaces and
     * accidental surrounding whitespace do not create inconsistent records.
     */
    private function normalizeBookRequest(Request $request): void
    {
        $genreChoice = $this->normalizeText((string) $request->input('genre_choice'));
        $genreOther = $this->normalizeText((string) $request->input('genre_other'));
        $resolvedGenre = $genreChoice === 'Other' ? $genreOther : $genreChoice;

        $request->merge([
            'book_title' => $this->normalizeText((string) $request->input('book_title')),
            'isbn' => $this->normalizeNullableText($request->input('isbn')),
            'author_name' => $this->normalizeNullableText($request->input('author_name')),
            'publisher_name' => $this->normalizeNullableText($request->input('publisher_name')),
            'genre_choice' => $genreChoice,
            'genre_other' => $genreOther,
            'genre_name' => $resolvedGenre,
            'date_published' => $request->filled('date_published')
                ? trim((string) $request->input('date_published'))
                : null,
        ]);
    }

    /**
     * Returns the shared Add/Edit validation rules for book metadata.
     */
    private function bookValidationRules(bool $isCreate, ?int $bookId = null): array
    {
        $statusRules = $isCreate
            ? ['required', Rule::in(['Available'])]
            : ['required', Rule::in($this->bookStatuses)];

        $genreChoices = array_merge($this->controlledGenres, ['Other']);
        $isbnUniqueRule = Rule::unique('book', 'isbn');

        if ($bookId !== null) {
            $isbnUniqueRule->ignore($bookId, 'book_id');
        }

        return [
            'book_title' => [
                'bail',
                'required',
                'string',
                'max:255',
                'regex:/^(?=.*[\p{L}\p{N}])[^\x00-\x1F\x7F<>]+$/u',
            ],
            'isbn' => [
                'bail',
                'nullable',
                'string',
                'regex:/^(?:[0-9]{10}|[0-9]{13})$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (!$this->isValidIsbn((string) $value)) {
                        $fail('Enter a valid ISBN-10 or ISBN-13. The ISBN check digit is incorrect.');
                    }
                },
                $isbnUniqueRule,
            ],
            'book_status' => $statusRules,
            'author_name' => [
                'bail',
                'nullable',
                'string',
                'min:2',
                'max:150',
                'regex:/^(?=.*\p{L})[\p{L}\p{M}\s\.\'’,-]+$/u',
                'not_regex:/^[\s\.\'’,-]+$/u',
            ],
            'publisher_name' => [
                'bail',
                'nullable',
                'string',
                'min:2',
                'max:150',
                'regex:/^(?=.*\p{L})[\p{L}\p{M}\p{N}\s\.,\'’&()\-]+$/u',
                'not_regex:/^[\s\.,\'’&()\-]+$/u',
            ],
            'genre_choice' => ['required', Rule::in($genreChoices)],
            'genre_other' => [
                'nullable',
                'required_if:genre_choice,Other',
                'string',
                'max:100',
                'regex:/^(?=.*\p{L})[\p{L}\p{M}\p{N}\s&\/,\'’\-]+$/u',
            ],
            'genre_name' => [
                'required',
                'string',
                'max:100',
                'regex:/^(?=.*\p{L})[\p{L}\p{M}\p{N}\s&\/,\'’\-]+$/u',
            ],
            'date_published' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * Returns clear validation messages shared by Add and Edit Book forms.
     */
    private function bookValidationMessages(): array
    {
        return [
            'book_title.required' => 'The book title is required.',
            'book_title.max' => 'The book title may not exceed 255 characters.',
            'book_title.regex' => 'Enter a valid book title without angle brackets or control characters.',
            'isbn.regex' => 'When provided, the ISBN must contain exactly 10 or 13 numeric digits. Spaces, hyphens, letters, and symbols are not allowed.',
            'isbn.unique' => 'This ISBN is already registered to another book.',
            'book_status.required' => 'The book status is required.',
            'book_status.in' => 'The selected book status is invalid.',
            'author_name.min' => 'The author name must contain at least 2 characters.',
            'author_name.regex' => 'The author may contain letters, spaces, apostrophes, periods, commas, and hyphens only. Numbers and other symbols are not allowed.',
            'author_name.not_regex' => 'The author name must contain letters.',
            'author_name.max' => 'The author name may not exceed 150 characters.',
            'publisher_name.min' => 'The publisher name must contain at least 2 characters.',
            'publisher_name.regex' => 'The publisher may contain letters, numbers, spaces, periods, commas, apostrophes, ampersands, parentheses, and hyphens only.',
            'publisher_name.not_regex' => 'The publisher name must contain letters.',
            'publisher_name.max' => 'The publisher name may not exceed 150 characters.',
            'genre_choice.required' => 'Please select a genre.',
            'genre_choice.in' => 'The selected genre is invalid.',
            'genre_other.required_if' => 'Enter the genre when Other is selected.',
            'genre_other.regex' => 'The custom genre contains unsupported characters.',
            'genre_other.max' => 'The custom genre may not exceed 100 characters.',
            'genre_name.required' => 'The genre is required.',
            'genre_name.regex' => 'The genre contains unsupported characters.',
            'date_published.date' => 'Enter a valid publication date.',
            'date_published.before_or_equal' => 'The publication date cannot be later than today.',
        ];
    }

    /**
     * Normalizes an optional text field and converts a blank value to null.
     */
    private function normalizeNullableText(mixed $value): ?string
    {
        $normalized = $this->normalizeText((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    /**
     * Trims text and collapses repeated whitespace to one space.
     */
    private function normalizeText(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    /**
     * Produces a normalized value for case-insensitive duplicate comparison.
     */
    private function normalizeComparableText(string $value): string
    {
        return mb_strtolower($this->normalizeText(str_replace(',', '', $value)));
    }

    /**
     * Validates the ISBN-10 or ISBN-13 check digit after the numeric length rule
     * has been satisfied. ISBN-10 values ending in X are intentionally rejected
     * because this system requires numeric-only ISBN input.
     */
    private function isValidIsbn(string $isbn): bool
    {
        if (!preg_match('/^(?:[0-9]{10}|[0-9]{13})$/', $isbn)) {
            return false;
        }

        if (strlen($isbn) === 10) {
            $sum = 0;

            for ($index = 0; $index < 10; $index++) {
                $sum += ((int) $isbn[$index]) * (10 - $index);
            }

            return $sum % 11 === 0;
        }

        $sum = 0;

        for ($index = 0; $index < 12; $index++) {
            $weight = $index % 2 === 0 ? 1 : 3;
            $sum += ((int) $isbn[$index]) * $weight;
        }

        $expectedCheckDigit = (10 - ($sum % 10)) % 10;

        return $expectedCheckDigit === (int) $isbn[12];
    }

    /**
     * Returns a validated numeric ISBN for persistence.
     */
    private function cleanIsbn(?string $isbn): ?string
    {
        $isbn = trim((string) $isbn);

        return $this->isValidIsbn($isbn) ? $isbn : null;
    }
}