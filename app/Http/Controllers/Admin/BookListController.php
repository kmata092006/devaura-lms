<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BookListService;
use Illuminate\Http\Request;

class BookListController extends Controller
{
    private BookListService $bookListService;

    public function __construct(BookListService $bookListService)
    {
        $this->bookListService = $bookListService;
    }

    public function index(Request $request)
    {
        $books = $this->bookListService->getBookList(
            $request->query('search'),
            $request->query('status'),
            $request->query('genre'),
            $request->query('author'),
            $request->query('publisher')
        );

        return view('admin.book-list.index', [
            'books' => $books,
            'statuses' => $this->bookListService->getStatuses(),
            'genres' => $this->bookListService->getGenres(),
            'authors' => $this->bookListService->getAuthors(),
            'publishers' => $this->bookListService->getPublishers(),
        ]);
    }
}