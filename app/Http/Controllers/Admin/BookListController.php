<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BookListService;
use Illuminate\Http\Request;

class BookListController extends Controller
{
    private BookListService $bookListService;

    /**
     * Receives the service that prepares catalog records and filters.
     */
    public function __construct(BookListService $bookListService)
    {
        $this->bookListService = $bookListService;
    }

    /**
     * Shows the catalog using the current search and filter query values.
     */
    public function index(Request $request)
    {
        // Apply the active search and filters before rendering the catalog.
        $books = $this->bookListService->getBookList(
            $request->query('search'),
            $request->query('status'),
            $request->query('genre'),
            $request->query('author'),
            $request->query('publisher')
        );

        // Provide option lists for each catalog filter dropdown.
        return view('admin.book-list.index', [
            'books' => $books,
            'statuses' => $this->bookListService->getStatuses(),
            'genres' => $this->bookListService->getGenres(),
            'authors' => $this->bookListService->getAuthors(),
            'publishers' => $this->bookListService->getPublishers(),
        ]);
    }
}
