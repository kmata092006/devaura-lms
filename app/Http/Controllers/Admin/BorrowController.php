<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BorrowService;
use Illuminate\Http\Request;

class BorrowController extends Controller
{
    private BorrowService $borrowService;

    public function __construct(BorrowService $borrowService)
    {
        $this->borrowService = $borrowService;
    }

    public function index(Request $request)
    {
        $data = $this->borrowService->getBorrowPageData(
            $request->query('search')
        );

        return view('admin.borrowing.index', $data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => ['required', 'integer', 'exists:library_member,member_id'],
            'book_id' => ['required', 'integer', 'exists:book,book_id'],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $this->borrowService->borrowBook($validated);

        return redirect()
            ->route('admin.borrow.index')
            ->with('success', 'Book borrowed successfully. The book status is now Borrowed.');
    }
}