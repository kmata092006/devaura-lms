<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReturnController extends Controller
{
    /**
     * Receives the service that processes active return transactions.
     */
    public function __construct(
        private readonly ReturnService $returnService
    ) {
    }

    /**
     * Shows active borrowed and overdue transactions that can be processed.
     */
    public function index(Request $request): View
    {
        $data = $this->returnService->getReturnPageData(
            $request->query('search')
        );

        return view('admin.returning.index', $data);
    }

    /**
     * Closes an active transaction as a normal return.
     *
     * The service assigns the current application date automatically when the
     * action is processed. A return date supplied by the browser is never used.
     */
    public function returnBook(int $transactionId): RedirectResponse
    {
        $this->returnService->returnBookNormally($transactionId);

        return redirect()
            ->route('admin.return.index')
            ->with(
                'success',
                'Book returned successfully. The current date was recorded automatically, and the book is now Available.'
            );
    }

    /**
     * Closes an active transaction and marks its book as lost.
     */
    public function markAsLost(int $transactionId): RedirectResponse
    {
        $this->returnService->markBookAsLost($transactionId);

        return redirect()
            ->route('admin.return.index')
            ->with(
                'success',
                'Book marked as Lost. The current date was recorded automatically, and the transaction was closed.'
            );
    }

    /**
     * Closes an active transaction and marks the returned book as damaged.
     */
    public function markAsDamaged(int $transactionId): RedirectResponse
    {
        $this->returnService->markBookAsDamaged($transactionId);

        return redirect()
            ->route('admin.return.index')
            ->with(
                'success',
                'Book marked as Damaged. The current date was recorded automatically, and the transaction was closed.'
            );
    }
}
