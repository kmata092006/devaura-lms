<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturnService
{
    /**
     * Prepares active transactions and queue summary data for the return page.
     */
    public function getReturnPageData(?string $search = null): array
    {
        $this->updateOverdueTransactions();

        return [
            'activeTransactions' => $this->getActiveTransactions($search),
            'returnSummary' => $this->getReturnSummary(),
        ];
    }

    /**
     * Closes an active transaction and makes the book available again.
     */
    public function returnBookNormally(int $transactionId): void
    {
        DB::transaction(function () use ($transactionId) {
            $transaction = $this->getLockedActiveTransaction($transactionId);
            $automaticReturnDate = $this->getAutomaticProcessingDate($transaction);

            DB::table('borrow_transaction')
                ->where('transaction_id', $transactionId)
                ->update([
                    'return_date' => $automaticReturnDate,
                    'borrow_status' => 'Returned',
                ]);

            DB::table('book')
                ->where('book_id', $transaction->book_id)
                ->update([
                    'book_status' => 'Available',
                ]);
        });
    }

    /**
     * Closes an active transaction and marks the book as lost.
     */
    public function markBookAsLost(int $transactionId): void
    {
        DB::transaction(function () use ($transactionId) {
            $transaction = $this->getLockedActiveTransaction($transactionId);
            $automaticReturnDate = $this->getAutomaticProcessingDate($transaction);

            DB::table('borrow_transaction')
                ->where('transaction_id', $transactionId)
                ->update([
                    'return_date' => $automaticReturnDate,
                    'borrow_status' => 'Lost',
                ]);

            DB::table('book')
                ->where('book_id', $transaction->book_id)
                ->update([
                    'book_status' => 'Lost',
                ]);
        });
    }

    /**
     * Closes an active transaction and marks the returned book as damaged.
     */
    public function markBookAsDamaged(int $transactionId): void
    {
        DB::transaction(function () use ($transactionId) {
            $transaction = $this->getLockedActiveTransaction($transactionId);
            $automaticReturnDate = $this->getAutomaticProcessingDate($transaction);

            DB::table('borrow_transaction')
                ->where('transaction_id', $transactionId)
                ->update([
                    'return_date' => $automaticReturnDate,
                    'borrow_status' => 'Returned',
                ]);

            DB::table('book')
                ->where('book_id', $transaction->book_id)
                ->update([
                    'book_status' => 'Damaged',
                ]);
        });
    }

    /**
     * Locks an active transaction before changing its state.
     */
    private function getLockedActiveTransaction(int $transactionId): object
    {
        $transaction = DB::table('borrow_transaction')
            ->where('transaction_id', $transactionId)
            ->whereIn('borrow_status', ['Borrowed', 'Overdue'])
            ->lockForUpdate()
            ->first();

        if (!$transaction) {
            throw ValidationException::withMessages([
                'transaction' => 'This transaction is no longer active or has already been processed.',
            ]);
        }

        return $transaction;
    }

    /**
     * Generates the date recorded when an active transaction is processed.
     *
     * The application timezone is Asia/Manila, so the stored date matches the
     * library user's local date at the moment the action is confirmed.
     */
    private function getAutomaticProcessingDate(object $transaction): string
    {
        $borrowDate = Carbon::parse($transaction->borrow_date)->startOfDay();
        $processingDate = now()->startOfDay();

        if ($processingDate->lt($borrowDate)) {
            throw ValidationException::withMessages([
                'transaction' => 'This transaction cannot be processed because its borrow date is later than the current date.',
            ]);
        }

        return $processingDate->toDateString();
    }

    /**
     * Returns active borrow transactions with readable member and book details.
     */
    private function getActiveTransactions(?string $search = null): LengthAwarePaginator
    {
        $query = DB::table('borrow_transaction')
            ->join('library_member', 'borrow_transaction.member_id', '=', 'library_member.member_id')
            ->join('book', 'borrow_transaction.book_id', '=', 'book.book_id')
            ->leftJoin('book_author', 'book.author_id', '=', 'book_author.author_id')
            ->leftJoin('book_genre', 'book.genre_id', '=', 'book_genre.genre_id')
            ->select(
                'borrow_transaction.transaction_id',
                'borrow_transaction.borrow_date',
                'borrow_transaction.due_date',
                'borrow_transaction.return_date',
                'borrow_transaction.borrow_status',
                'library_member.member_role',
                'library_member.first_name as member_first_name',
                'library_member.last_name as member_last_name',
                DB::raw('library_member.`m.i.` as member_middle_initial'),
                'book.isbn',
                'book.book_title',
                'book.book_status',
                'book_author.first_name as author_first_name',
                'book_author.last_name as author_last_name',
                DB::raw('book_author.`m.i.` as author_middle_initial'),
                'book_genre.genre_name'
            )
            ->whereIn('borrow_transaction.borrow_status', ['Borrowed', 'Overdue']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('library_member.first_name', 'like', "%{$search}%")
                    ->orWhere('library_member.last_name', 'like', "%{$search}%")
                    ->orWhereRaw('library_member.`m.i.` LIKE ?', ["%{$search}%"])
                    ->orWhere('library_member.member_role', 'like', "%{$search}%")
                    ->orWhere('book.isbn', 'like', "%{$search}%")
                    ->orWhere('book.book_title', 'like', "%{$search}%")
                    ->orWhere('book_author.first_name', 'like', "%{$search}%")
                    ->orWhere('book_author.last_name', 'like', "%{$search}%")
                    ->orWhereRaw('book_author.`m.i.` LIKE ?', ["%{$search}%"])
                    ->orWhere('book.book_status', 'like', "%{$search}%")
                    ->orWhere('borrow_transaction.borrow_status', 'like', "%{$search}%")
                    ->orWhere('book_genre.genre_name', 'like', "%{$search}%");
            });
        }

        return $query
            ->orderByRaw("CASE WHEN borrow_transaction.borrow_status = 'Overdue' THEN 0 ELSE 1 END")
            ->orderBy('borrow_transaction.due_date')
            ->orderByDesc('borrow_transaction.transaction_id')
            ->paginate(8)
            ->withQueryString();
    }

    /**
     * Builds compact queue totals for the module header.
     */
    private function getReturnSummary(): array
    {
        $activeQuery = DB::table('borrow_transaction')
            ->whereIn('borrow_status', ['Borrowed', 'Overdue']);

        return [
            'active' => (clone $activeQuery)->count(),
            'overdue' => (clone $activeQuery)
                ->where('borrow_status', 'Overdue')
                ->count(),
            'due_today' => (clone $activeQuery)
                ->whereDate('due_date', now()->toDateString())
                ->count(),
        ];
    }

    /**
     * Marks borrowed transactions as overdue once their due date has passed.
     */
    private function updateOverdueTransactions(): void
    {
        DB::table('borrow_transaction')
            ->where('borrow_status', 'Borrowed')
            ->whereDate('due_date', '<', now()->toDateString())
            ->update([
                'borrow_status' => 'Overdue',
            ]);
    }
}
