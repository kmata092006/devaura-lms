<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GoogleBooksService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            );

        // Applies keyword search across book, author, publisher, genre, and status fields.
        if ($request->filled('search')) {
            $search = trim($request->query('search'));

            $query->where(function ($q) use ($search) {
                $q->where('book.book_id', 'like', "%{$search}%")
                    ->orWhere('book.book_title', 'like', "%{$search}%")
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
     * Shows the add book form with authors, publishers, genres, and status options.
     */
    public function create()
    {
        $authors = $this->getAuthors();
        $publishers = $this->getPublishers();
        $genres = $this->getGenres();
        $statuses = $this->bookStatuses;

        return view('admin.books.create', compact(
            'authors',
            'publishers',
            'genres',
            'statuses'
        ));
    }

    /**
     * Validates and stores a new book record, including optional new author, publisher, or genre entries.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'book_title' => ['required', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:13'],
            'book_status' => ['required', Rule::in($this->bookStatuses)],

            // Require author text input only if no existing selection is made
            'author_id' => ['nullable', 'integer', 'exists:book_author,author_id'],
            'author_name' => [
                Rule::requiredIf(fn() => !$request->filled('author_id')),
                'nullable', 'string', 'max:150'
            ],

            // Require publisher text input only if no existing selection is made
            'publisher_id' => ['nullable', 'integer', 'exists:book_publisher,publisher_id'],
            'publisher_name' => [
                Rule::requiredIf(fn() => !$request->filled('publisher_id')),
                'nullable', 'string', 'max:150'
            ],
            'date_published' => ['required', 'date'], // Strictly required globally

            // Require genre text input only if no existing selection is made
            'genre_id' => ['nullable', 'integer', 'exists:book_genre,genre_id'],
            'genre_name' => [
                Rule::requiredIf(fn() => !$request->filled('genre_id')),
                'nullable', 'string', 'max:100'
            ],
        ], [
            'author_name.required_if' => 'Please select an existing author or enter a new author name.',
            'author_name.regex' => 'The author name can only contain letters, spaces, periods, commas, and hyphens.',
            'publisher_name.required_if' => 'Please select an existing publisher or enter a new publisher name.',
            'publisher_name.regex' => 'The publisher name can only contain letters, spaces, periods, commas, and hyphens.',
            'genre_name.required_if' => 'Please select an existing genre or enter a new genre name.',
            'genre_name.regex' => 'The genre name can only contain letters and spaces.',
            'date_published.required' => 'The date published field is required.'
        ]);

        $isbn = $this->cleanIsbn($validated['isbn'] ?? null);
        $authorId = $validated['author_id'] ?? null;
        $publisherId = $validated['publisher_id'] ?? null;
        $genreId = $validated['genre_id'] ?? null;

        // Creates related records only when the user enters a new value instead of selecting an existing one.
        if (!$authorId && !empty($validated['author_name'])) {
            $authorId = $this->findOrCreateAuthor($validated['author_name']);
        }

        if (!$publisherId && !empty($validated['publisher_name'])) {
            $publisherId = $this->findOrCreatePublisher(
                $validated['publisher_name'],
                $validated['date_published']
            );
        }

        // Updates publisher publication date only if it was previously empty.
        if ($publisherId && !empty($validated['date_published'])) {
            DB::table('book_publisher')
                ->where('publisher_id', $publisherId)
                ->update([
                    'date_published' => $validated['date_published'],
                ]);
        }

        if (!$genreId && !empty($validated['genre_name'])) {
            $genreId = $this->findOrCreateGenre($validated['genre_name']);
        }

        DB::table('book')->insert([
            'author_id' => $authorId,
            'publisher_id' => $publisherId,
            'genre_id' => $genreId,
            'isbn' => $isbn,
            'book_title' => trim($validated['book_title']),
            'book_status' => $validated['book_status'],
        ]);

        return redirect()
            ->route('admin.books.index')
            ->with('success', 'Book added successfully.');
    }

    /**
     * Shows the edit form for a selected book record.
     */
    public function edit($bookId)
    {
        // Fix: Added table joins to properly fill the data fields upon loading the edit view
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

        $authors = $this->getAuthors();
        $publishers = $this->getPublishers();
        $genres = $this->getGenres();
        $statuses = $this->bookStatuses;

        return view('admin.books.edit', compact(
            'book',
            'authors',
            'publishers',
            'genres',
            'statuses'
        ));
    }

    /**
     * Validates and updates an existing book record and its selected related records.
     */
    public function update(Request $request, $bookId)
    {
        $book = DB::table('book')->where('book_id', $bookId)->first();

        if (!$book) {
            return redirect()
                ->route('admin.books.index')
                ->withErrors(['book' => 'Book record not found.']);
        }

        // Updated validation array to match edit.blade.php input variables explicitly
        $validated = $request->validate([
            'book_title' => ['required', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:13'],
            'book_status' => ['required', Rule::in($this->bookStatuses)],
            'genre_name' => ['required', 'string', 'max:100'],
            
            'author_last_name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z\.\s]+$/'],
            'author_first_name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z\.\s]+$/'],
            'author_middle_initial' => ['nullable', 'string', 'max:3', 'regex:/^[a-zA-Z\.]+$/'],
            
            'publisher_last_name' => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-Z\.\s]+$/'],
            'publisher_first_name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z\.\s]+$/'],
            'publisher_middle_initial' => ['nullable', 'string', 'max:3', 'regex:/^[a-zA-Z\.]+$/'],
            'date_published' => ['required', 'date'],
        ], [
            'genre_name.regex' => 'The genre name can only contain letters and spaces.',
            'author_last_name.regex' => 'The author last name can only contain letters and spaces.',
            'author_first_name.regex' => 'The author first name can only contain letters and spaces.',
            'author_middle_initial.regex' => 'The author middle initial can only contain letters and periods.',
            'publisher_last_name.regex' => 'The publisher last name can only contain letters and spaces.',
            'publisher_first_name.regex' => 'The publisher first name can only contain letters and spaces.',
            'publisher_middle_initial.regex' => 'The publisher middle initial can only contain letters and periods.',
            'date_published.required' => 'The date published field is required.'
        ]);

        $isbn = $this->cleanIsbn($validated['isbn'] ?? null);

        // Standardize dynamic lookups for Author, Publisher, and Genre components
        $authorFullName = $validated['author_last_name'] . ', ' . $validated['author_first_name'];
        $authorId = $this->findOrCreateAuthor($authorFullName);
        
        // Reuses existing related records or creates new ones based on user input.
        if ($authorId && isset($validated['author_middle_initial'])) {
            $cleanMI = strtoupper(trim(rtrim($validated['author_middle_initial'], '.')));
            DB::table('book_author')
                ->where('author_id', $authorId)
                ->update(['m.i.' => $cleanMI ?: null]);
        }

        $pubName = trim(($validated['publisher_last_name'] ?? '') . ' ' . $validated['publisher_first_name']);
        $publisherId = $this->findOrCreatePublisher($pubName, $validated['date_published']);

        // Preserves existing publisher dates by only filling blank date_published values.
        if ($publisherId) {
            $cleanPubMI = strtoupper(trim(rtrim($validated['publisher_middle_initial'] ?? '', '.')));
            // FIX: Replaced DB::table()->update() with DB::update() raw string to prevent 
            // Laravel from parsing `m.i.` as a JSON column update.
            DB::update(
                'UPDATE book_publisher SET last_name = ?, first_name = ?, `m.i.` = ?, date_published = ? WHERE publisher_id = ?',
                [
                    trim($validated['publisher_last_name'] ?? '') ?: null,
                    trim($validated['publisher_first_name']),
                    $cleanPubMI ?: null,
                    $validated['date_published'],
                    $publisherId
                ]
            );
        }

        $genreId = $this->findOrCreateGenre($validated['genre_name']);

        DB::table('book')
            ->where('book_id', $bookId)
            ->update([
                'author_id' => $authorId,
                'publisher_id' => $publisherId,
                'genre_id' => $genreId,
                'isbn' => $isbn,
                'book_title' => trim($validated['book_title']),
                'book_status' => $validated['book_status'],
            ]);

        return redirect()
            ->route('admin.books.index')
            ->with('success', 'Book updated successfully.');
    }

    /**
     * Deletes a book only when it has no borrowing transaction records.
     */
    public function destroy($bookId)
    {
        $book = DB::table('book')
            ->where('book_id', $bookId)
            ->first();

        if (!$book) {
            return redirect()
                ->route('admin.books.index')
                ->withErrors(['book' => 'Book record not found.']);
        }

        // Prevents deletion of books that are already part of borrow history.
        $hasBorrowTransaction = DB::table('borrow_transaction')
            ->where('book_id', $bookId)
            ->exists();

        if ($hasBorrowTransaction) {
            return redirect()
                ->route('admin.books.index')
                ->withErrors(['book' => 'This book cannot be deleted because it already has borrowing records.']);
        }

        DB::table('book')
            ->where('book_id', $bookId)
            ->delete();

        return redirect()
            ->route('admin.books.index')
            ->with('success', 'Book deleted successfully.');
    }

    /**
     * Handles AJAX requests for Google Books lookup and returns matched book details as JSON.
     */
    public function searchGoogleBooks(Request $request, GoogleBooksService $googleBooksService)
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:255'],
        ]);

        return response()->json(
            $googleBooksService->search($validated['query'])
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
    private function findOrCreateAuthor(string $authorName): ?int
    {
        $authorName = trim($authorName);

        if ($authorName === '') {
            return null;
        }

        [$firstName, $lastName] = $this->splitPersonName($authorName);

        // Checks for duplicate author records using normalized first and last names.
        $existingAuthor = DB::table('book_author')
            ->whereRaw('LOWER(TRIM(COALESCE(first_name, ""))) = ?', [strtolower($firstName ?? '')])
            ->whereRaw('LOWER(TRIM(COALESCE(last_name, ""))) = ?', [strtolower($lastName ?? '')])
            ->first();

        if ($existingAuthor) {
            return (int) $existingAuthor->author_id;
        }

        // Uses raw SQL because the database column name `m.i.` contains dots.
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
     * Finds an existing publisher by name or creates a new publisher record.
     */
    private function findOrCreatePublisher(string $publisherName, ?string $datePublished = null): ?int
    {
        $publisherName = trim($publisherName);

        if ($publisherName === '') {
            return null;
        }

        // Checks both first_name and last_name because publisher names may be stored in either field.
        $existingPublisher = DB::table('book_publisher')
            ->where(function ($query) use ($publisherName) {
                $query->whereRaw('LOWER(TRIM(COALESCE(first_name, ""))) = ?', [strtolower($publisherName)])
                    ->orWhereRaw('LOWER(TRIM(COALESCE(last_name, ""))) = ?', [strtolower($publisherName)]);
            })
            ->first();

        if ($existingPublisher) {
            return (int) $existingPublisher->publisher_id;
        }

        // Uses raw SQL because the database column name `m.i.` contains dots.
        DB::insert(
            'INSERT INTO book_publisher (last_name, first_name, `m.i.`, date_published) VALUES (?, ?, ?, ?)',
            [
                null,
                $publisherName,
                null,
                $datePublished,
            ]
        );

        return (int) DB::getPdo()->lastInsertId();
    }

    /**
     * Finds an existing genre by name or creates a new genre record.
     */
    private function findOrCreateGenre(string $genreName): ?int
    {
        $genreName = trim($genreName);

        if ($genreName === '') {
            return null;
        }

        // Prevents duplicate genre records by comparing normalized genre names.
        $existingGenre = DB::table('book_genre')
            ->whereRaw('LOWER(TRIM(genre_name)) = ?', [strtolower($genreName)])
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
     * Cleans ISBN input by removing invalid characters and limiting it to 13 characters.
     */
    private function cleanIsbn(?string $isbn): ?string
    {
        if (!$isbn) {
            return null;
        }

        $isbn = strtoupper(preg_replace('/[^0-9Xx]/', '', $isbn));

        if ($isbn === '') {
            return null;
        }

        return substr($isbn, 0, 13);
    }
}