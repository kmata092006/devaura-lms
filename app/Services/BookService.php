<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookService
{
    /**
     * Returns the searchable, filterable book management list.
     */
    public function getBooks(?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        $query = DB::table('book')
            ->leftJoin('book_author', 'book.author_id', '=', 'book_author.author_id')
            ->leftJoin('book_publisher', 'book.publisher_id', '=', 'book_publisher.publisher_id')
            ->leftJoin('book_genre', 'book.genre_id', '=', 'book_genre.genre_id')
            ->select(
                'book.book_id',
                'book.isbn',
                'book.book_title',
                'book.book_status',
                'book_author.first_name as author_first_name',
                'book_author.last_name as author_last_name',
                DB::raw('book_author.`m.i.` as author_middle_initial'),
                'book_publisher.first_name as publisher_first_name',
                'book_publisher.last_name as publisher_last_name',
                DB::raw('book_publisher.`m.i.` as publisher_middle_initial'),
                'book_publisher.date_published',
                'book_genre.genre_name'
            );

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('book.book_id', 'like', "%{$search}%")
                    ->orWhere('book.isbn', 'like', "%{$search}%")
                    ->orWhere('book.book_title', 'like', "%{$search}%")
                    ->orWhere('book.book_status', 'like', "%{$search}%")
                    ->orWhere('book_author.first_name', 'like', "%{$search}%")
                    ->orWhere('book_author.last_name', 'like', "%{$search}%")
                    ->orWhereRaw('book_author.`m.i.` LIKE ?', ["%{$search}%"])
                    ->orWhere('book_publisher.first_name', 'like', "%{$search}%")
                    ->orWhere('book_publisher.last_name', 'like', "%{$search}%")
                    ->orWhereRaw('book_publisher.`m.i.` LIKE ?', ["%{$search}%"])
                    ->orWhere('book_genre.genre_name', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('book.book_status', $status);
        }

        return $query
            ->orderBy('book.book_title')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Loads a single book with its author, publisher, and genre details.
     */
    public function findBook(int $bookId): object
    {
        $book = DB::table('book')
            ->leftJoin('book_author', 'book.author_id', '=', 'book_author.author_id')
            ->leftJoin('book_publisher', 'book.publisher_id', '=', 'book_publisher.publisher_id')
            ->leftJoin('book_genre', 'book.genre_id', '=', 'book_genre.genre_id')
            ->select(
                'book.book_id',
                'book.author_id',
                'book.publisher_id',
                'book.genre_id',
                'book.isbn',
                'book.book_title',
                'book.book_status',
                'book_author.first_name as author_first_name',
                'book_author.last_name as author_last_name',
                DB::raw('book_author.`m.i.` as author_middle_initial'),
                'book_publisher.first_name as publisher_first_name',
                'book_publisher.last_name as publisher_last_name',
                DB::raw('book_publisher.`m.i.` as publisher_middle_initial'),
                'book_publisher.date_published',
                'book_genre.genre_name'
            )
            ->where('book.book_id', $bookId)
            ->first();

        if (!$book) {
            abort(404, 'Book not found.');
        }

        return $book;
    }

    /**
     * Creates a book and any missing related author, publisher, or genre.
     */
    public function createBook(array $data): void
    {
        DB::transaction(function () use ($data) {
            $authorId = $this->findOrCreateAuthor($data);
            $publisherId = $this->findOrCreatePublisher($data);
            $genreId = $this->findOrCreateGenre($data['genre_name']);

            DB::table('book')->insert([
                'author_id' => $authorId,
                'publisher_id' => $publisherId,
                'genre_id' => $genreId,
                'isbn' => $data['isbn'],
                'book_title' => $data['book_title'],
                'book_status' => $data['book_status'],
            ]);
        });
    }

    /**
     * Updates catalog details while protecting transaction-managed statuses.
     *
     * Once borrowing history exists, the current status can only be changed by
     * the Borrow Book or Return Book workflow. This prevents Lost or Damaged
     * books from being changed back to Available through a normal edit request.
     */
    public function updateBook(int $bookId, array $data): void
    {
        DB::transaction(function () use ($bookId, $data) {
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
                ->select('transaction_id')
                ->lockForUpdate()
                ->first();

            $hasTransactionHistory = $transactionHistory !== null;
            $requestedStatus = $data['book_status'];

            if ($hasTransactionHistory && $requestedStatus !== $book->book_status) {
                throw ValidationException::withMessages([
                    'book_status' => 'This book status is managed by borrowing and return transactions and cannot be changed from the Edit Book page.',
                ]);
            }

            if (!$hasTransactionHistory && $requestedStatus === 'Borrowed') {
                throw ValidationException::withMessages([
                    'book_status' => 'A book can only become Borrowed through the Borrow Book module.',
                ]);
            }

            $authorId = $this->findOrCreateAuthor($data);
            $publisherId = $this->findOrCreatePublisher($data);
            $genreId = $this->findOrCreateGenre($data['genre_name']);

            DB::table('book')
                ->where('book_id', $bookId)
                ->update([
                    'author_id' => $authorId,
                    'publisher_id' => $publisherId,
                    'genre_id' => $genreId,
                    'isbn' => $data['isbn'],
                    'book_title' => $data['book_title'],
                    'book_status' => $hasTransactionHistory
                        ? $book->book_status
                        : $requestedStatus,
                ]);
        });
    }

    /**
     * Deletes a book only after active Borrowed or Overdue transactions have
     * been closed. Completed transaction rows are removed with the book.
     */
    public function deleteBook(int $bookId): void
    {
        DB::transaction(function () use ($bookId) {
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

            DB::table('borrow_transaction')
                ->where('book_id', $bookId)
                ->delete();

            DB::table('book')
                ->where('book_id', $bookId)
                ->delete();
        });
    }

    /**
     * Reuses an existing author row or creates one for the book form data.
     */
    private function findOrCreateAuthor(array $data): int
    {
        $author = DB::table('book_author')
            ->where('last_name', $data['author_last_name'])
            ->where('first_name', $data['author_first_name'])
            ->whereRaw('COALESCE(`m.i.`, "") = ?', [$data['author_middle_initial'] ?? ''])
            ->first();

        if ($author) {
            return $author->author_id;
        }

        DB::insert(
            'INSERT INTO book_author (last_name, first_name, `m.i.`) VALUES (?, ?, ?)',
            [
                $data['author_last_name'],
                $data['author_first_name'],
                $data['author_middle_initial'] ?? null,
            ]
        );

        return (int) DB::getPdo()->lastInsertId();
    }

    /**
     * Reuses an existing publisher row or creates one for the book form data.
     */
    private function findOrCreatePublisher(array $data): int
    {
        $publisher = DB::table('book_publisher')
            ->where('last_name', $data['publisher_last_name'])
            ->where('first_name', $data['publisher_first_name'])
            ->whereRaw('COALESCE(`m.i.`, "") = ?', [$data['publisher_middle_initial'] ?? ''])
            ->where(function ($query) use ($data) {
                if (!empty($data['date_published'])) {
                    $query->where('date_published', $data['date_published']);
                } else {
                    $query->whereNull('date_published');
                }
            })
            ->first();

        if ($publisher) {
            return $publisher->publisher_id;
        }

        DB::insert(
            'INSERT INTO book_publisher (last_name, first_name, `m.i.`, date_published) VALUES (?, ?, ?, ?)',
            [
                $data['publisher_last_name'],
                $data['publisher_first_name'],
                $data['publisher_middle_initial'] ?? null,
                $data['date_published'] ?? null,
            ]
        );

        return (int) DB::getPdo()->lastInsertId();
    }

    /**
     * Reuses an existing genre row or creates a new genre by name.
     */
    private function findOrCreateGenre(string $genreName): int
    {
        $genre = DB::table('book_genre')
            ->where('genre_name', $genreName)
            ->first();

        if ($genre) {
            return $genre->genre_id;
        }

        return DB::table('book_genre')->insertGetId([
            'genre_name' => $genreName,
        ]);
    }
}
