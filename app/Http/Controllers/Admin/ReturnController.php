<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    private ReturnService $returnService;

    /**
     * Injects the return service used by this controller.
     */
    public function __construct(ReturnService $returnService)
    {
        $this->returnService = $returnService;
    }

    /**
     * Shows active borrow transactions ready for return processing.
     */
    public function index(Request $request)
    {
        $data = $this->returnService->getReturnPageData(
            $request->query('search')
        );

        return view('admin.returning.index', $data);
    }

    /**
     * Returns a book through the service workflow.
     */
    public function returnNormal(int $transactionId)
    {
        $this->returnService->returnBookNormally($transactionId);

        return redirect()
            ->route('admin.return.index')
            ->with('success', 'Book returned successfully. The book status is now Available.');
    }

    /**
     * Marks a borrowed book as lost through the service workflow.
     */
    public function markLost(int $transactionId)
    {
        $this->returnService->markBookAsLost($transactionId);

        return redirect()
            ->route('admin.return.index')
            ->with('success', 'Book marked as Lost. The transaction has been closed.');
    }

    /**
     * Marks a returned book as damaged through the service workflow.
     */
    public function markDamaged(int $transactionId)
    {
        $this->returnService->markBookAsDamaged($transactionId);

        return redirect()
            ->route('admin.return.index')
            ->with('success', 'Book marked as Damaged. The transaction has been closed.');
    }

    /**
     * Legacy return handler that closes a transaction and frees the book.
     */
    public function returnBook($transactionId)
    {
        $transaction = DB::table('borrow_transaction')
            ->where('transaction_id', $transactionId)
            ->first();

        if (!$transaction) {
            return redirect()
                ->route('admin.return.index')
                ->withErrors(['return' => 'Borrow transaction not found.']);
        }

        if ($transaction->borrow_status === 'Returned') {
            return redirect()
                ->route('admin.return.index')
                ->withErrors(['return' => 'This book has already been returned.']);
        }

        DB::transaction(function () use ($transaction) {
            DB::table('borrow_transaction')
                ->where('transaction_id', $transaction->transaction_id)
                ->update([
                    'return_date' => now()->toDateString(),
                    'borrow_status' => 'Returned',
                ]);

            DB::table('book')
                ->where('book_id', $transaction->book_id)
                ->update([
                    'book_status' => 'Available',
                ]);
        });

        return redirect()
            ->route('admin.return.index')
            ->with('success', 'Book returned successfully.');
    }

    /**
     * Legacy handler that closes a transaction as lost.
     */
    public function markAsLost($transactionId)
    {
        $transaction = DB::table('borrow_transaction')
            ->where('transaction_id', $transactionId)
            ->first();

        if (!$transaction) {
            return redirect()
                ->route('admin.return.index')
                ->withErrors(['return' => 'Borrow transaction not found.']);
        }

        DB::transaction(function () use ($transaction) {
            DB::table('borrow_transaction')
                ->where('transaction_id', $transaction->transaction_id)
                ->update([
                    'return_date' => now()->toDateString(),
                    'borrow_status' => 'Lost',
                ]);

            DB::table('book')
                ->where('book_id', $transaction->book_id)
                ->update([
                    'book_status' => 'Lost',
                ]);
        });

        return redirect()
            ->route('admin.return.index')
            ->with('success', 'Book marked as lost successfully.');
    }

    /**
     * Legacy handler that closes a transaction and marks the book damaged.
     */
    public function markAsDamaged($transactionId)
    {
        $transaction = DB::table('borrow_transaction')
            ->where('transaction_id', $transactionId)
            ->first();

        if (!$transaction) {
            return redirect()
                ->route('admin.return.index')
                ->withErrors(['return' => 'Borrow transaction not found.']);
        }

        DB::transaction(function () use ($transaction) {
            DB::table('borrow_transaction')
                ->where('transaction_id', $transaction->transaction_id)
                ->update([
                    'return_date' => now()->toDateString(),
                    'borrow_status' => 'Returned',
                ]);

            DB::table('book')
                ->where('book_id', $transaction->book_id)
                ->update([
                    'book_status' => 'Damaged',
                ]);
        });

        return redirect()
            ->route('admin.return.index')
            ->with('success', 'Book marked as damaged successfully.');
    }
}
