<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BorrowService
{
    /**
     * Prepares member, book, and transaction data for the borrow page.
     */
    public function getBorrowPageData(?string $search = null): array
    {
        $this->updateOverdueTransactions();

        return [
            'members' => $this->getMembers(),
            'availableBooks' => $this->getAvailableBooks(),
            'activeTransactions' => $this->getActiveBorrowTransactions($search),
            'defaultDueDate' => now()->addDays(7)->toDateString(),
        ];
    }

    /**
     * Creates an active borrow transaction and marks the book as borrowed.
     */
    public function borrowBook(array $data): void
    {
        DB::transaction(function () use ($data) {
            $member = DB::table('library_member')
                ->where('member_id', $data['member_id'])
                ->first();

            if (!$member) {
                throw ValidationException::withMessages([
                    'member_id' => 'Selected library member does not exist.',
                ]);
            }

            $book = DB::table('book')
                ->where('book_id', $data['book_id'])
                ->lockForUpdate()
                ->first();

            if (!$book) {
                throw ValidationException::withMessages([
                    'book_id' => 'Selected book does not exist.',
                ]);
            }

            if ($book->book_status !== 'Available') {
                throw ValidationException::withMessages([
                    'book_id' => 'This book is not available for borrowing.',
                ]);
            }

            DB::table('borrow_transaction')->insert([
                'member_id' => $data['member_id'],
                'book_id' => $data['book_id'],
                'borrow_date' => now()->toDateString(),
                'due_date' => $data['due_date'],
                'borrow_status' => 'Borrowed',
            ]);

            DB::table('book')
                ->where('book_id', $data['book_id'])
                ->update([
                    'book_status' => 'Borrowed',
                ]);
        });
    }

    /**
     * Lists members that can be selected for a borrowing transaction.
     */
    private function getMembers(): Collection
    {
        return DB::table('library_member')
            ->select(
                'member_id',
                'member_role',
                'last_name',
                'first_name',
                DB::raw('`m.i.` as middle_initial')
            )
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Lists books that are currently available to borrow.
     */
    private function getAvailableBooks(): Collection
    {
        return DB::table('book')
            ->leftJoin('book_author', 'book.author_id', '=', 'book_author.author_id')
            ->leftJoin('book_genre', 'book.genre_id', '=', 'book_genre.genre_id')
            ->select(
                'book.book_id',
                'book.isbn',
                'book.book_title',
                'book.book_status',
                'book_author.first_name as author_first_name',
                'book_author.last_name as author_last_name',
                DB::raw('book_author.`m.i.` as author_middle_initial'),
                'book_genre.genre_name'
            )
            ->where('book.book_status', 'Available')
            ->orderBy('book.book_title')
            ->get();
    }

    /**
     * Returns active borrowed or overdue transactions for the borrow table.
     */
    private function getActiveBorrowTransactions(?string $search = null): LengthAwarePaginator
    {
        $query = DB::table('borrow_transaction')
            ->join('library_member', 'borrow_transaction.member_id', '=', 'library_member.member_id')
            ->join('book', 'borrow_transaction.book_id', '=', 'book.book_id')
            ->leftJoin('book_author', 'book.author_id', '=', 'book_author.author_id')
            ->select(
                'borrow_transaction.transaction_id',
                'borrow_transaction.borrow_date',
                'borrow_transaction.due_date',
                'borrow_transaction.return_date',
                'borrow_transaction.borrow_status',
                'library_member.member_id',
                'library_member.member_role',
                'library_member.first_name as member_first_name',
                'library_member.last_name as member_last_name',
                DB::raw('library_member.`m.i.` as member_middle_initial'),
                'book.book_id',
                'book.isbn',
                'book.book_title',
                'book.book_status',
                'book_author.first_name as author_first_name',
                'book_author.last_name as author_last_name',
                DB::raw('book_author.`m.i.` as author_middle_initial')
            )
            ->whereIn('borrow_transaction.borrow_status', ['Borrowed', 'Overdue']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('borrow_transaction.transaction_id', 'like', "%{$search}%")
                    ->orWhere('library_member.member_id', 'like', "%{$search}%")
                    ->orWhere('library_member.first_name', 'like', "%{$search}%")
                    ->orWhere('library_member.last_name', 'like', "%{$search}%")
                    ->orWhereRaw('library_member.`m.i.` LIKE ?', ["%{$search}%"])
                    ->orWhere('book.book_id', 'like', "%{$search}%")
                    ->orWhere('book.isbn', 'like', "%{$search}%")
                    ->orWhere('book.book_title', 'like', "%{$search}%")
                    ->orWhere('borrow_transaction.borrow_status', 'like', "%{$search}%");
            });
        }

        return $query
            ->orderByDesc('borrow_transaction.transaction_id')
            ->paginate(10)
            ->withQueryString();
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
