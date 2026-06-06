<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BookListService
{
    public function getBookList(
        ?string $search = null,
        ?string $status = null,
        ?string $genre = null,
        ?string $author = null,
        ?string $publisher = null
    ): LengthAwarePaginator {
        $query = DB::table('book')
            ->leftJoin('book_author', 'book.author_id', '=', 'book_author.author_id')
            ->leftJoin('book_publisher', 'book.publisher_id', '=', 'book_publisher.publisher_id')
            ->leftJoin('book_genre', 'book.genre_id', '=', 'book_genre.genre_id')
            ->select(
                'book.book_id',
                'book.isbn',
                'book.book_title',
                'book.book_status',
                'book_author.author_id',
                'book_author.first_name as author_first_name',
                'book_author.last_name as author_last_name',
                DB::raw('book_author.`m.i.` as author_middle_initial'),
                'book_publisher.publisher_id',
                'book_publisher.first_name as publisher_first_name',
                'book_publisher.last_name as publisher_last_name',
                DB::raw('book_publisher.`m.i.` as publisher_middle_initial'),
                'book_publisher.date_published',
                'book_genre.genre_id',
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
                    ->orWhere('book_publisher.date_published', 'like', "%{$search}%")
                    ->orWhere('book_genre.genre_name', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('book.book_status', $status);
        }

        if ($genre) {
            $query->where('book_genre.genre_name', $genre);
        }

        if ($author) {
            $query->where('book_author.author_id', $author);
        }

        if ($publisher) {
            $query->where('book_publisher.publisher_id', $publisher);
        }

        return $query
            ->orderBy('book.book_title')
            ->paginate(10)
            ->withQueryString();
    }

    public function getStatuses(): array
    {
        return [
            'Available',
            'Borrowed',
            'Reserved',
            'Lost',
            'Damaged',
            'Under Repair',
        ];
    }

    public function getGenres(): Collection
    {
        return DB::table('book_genre')
            ->select('genre_name')
            ->orderBy('genre_name')
            ->get();
    }

    public function getAuthors(): Collection
    {
        return DB::table('book_author')
            ->select(
                'author_id',
                'first_name',
                'last_name',
                DB::raw('`m.i.` as middle_initial')
            )
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    public function getPublishers(): Collection
    {
        return DB::table('book_publisher')
            ->select(
                'publisher_id',
                'first_name',
                'last_name',
                DB::raw('`m.i.` as middle_initial')
            )
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }
}