<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BookService;
use Illuminate\Http\Request;

class BookController extends Controller
{
    private BookService $bookService;

    public function __construct(BookService $bookService)
    {
        $this->bookService = $bookService;
    }

    public function index(Request $request)
    {
        $books = $this->bookService->getBooks(
            $request->query('search'),
            $request->query('status')
        );

        return view('admin.books.index', [
            'books' => $books,
            'statuses' => $this->bookStatuses(),
        ]);
    }

    public function create()
    {
        return view('admin.books.create', [
            'statuses' => $this->bookStatuses(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateBook($request);

        $this->bookService->createBook($validated);

        return redirect()
            ->route('admin.books.index')
            ->with('success', 'Book added successfully.');
    }

    public function edit(int $bookId)
    {
        $book = $this->bookService->findBook($bookId);

        return view('admin.books.edit', [
            'book' => $book,
            'statuses' => $this->bookStatuses(),
        ]);
    }

    public function update(Request $request, int $bookId)
    {
        $validated = $this->validateBook($request);

        $this->bookService->updateBook($bookId, $validated);

        return redirect()
            ->route('admin.books.index')
            ->with('success', 'Book updated successfully.');
    }

    public function destroy(int $bookId)
    {
        $this->bookService->deleteBook($bookId);

        return redirect()
            ->route('admin.books.index')
            ->with('success', 'Book deleted successfully.');
    }

    private function validateBook(Request $request): array
    {
        return $request->validate([
            'isbn' => ['nullable', 'string', 'max:13'],
            'book_title' => ['required', 'string', 'max:255'],
            'book_status' => ['required', 'in:Available,Borrowed,Reserved,Lost,Damaged,Under Repair'],

            'author_last_name' => ['nullable', 'string', 'max:50'],
            'author_first_name' => ['nullable', 'string', 'max:50'],
            'author_middle_initial' => ['nullable', 'string', 'max:3'],

            'publisher_last_name' => ['nullable', 'string', 'max:50'],
            'publisher_first_name' => ['nullable', 'string', 'max:50'],
            'publisher_middle_initial' => ['nullable', 'string', 'max:3'],
            'date_published' => ['nullable', 'date'],

            'genre_name' => ['required', 'string', 'max:100'],
        ]);
    }

    private function bookStatuses(): array
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
}