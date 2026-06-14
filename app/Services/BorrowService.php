<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BorrowService
{
    private const ACTIVE_TRANSACTION_STATUSES = ['Borrowed', 'Overdue'];
    private const BORROWABLE_BOOK_STATUS = 'Available';

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
     * Creates an active borrow transaction and marks the book as Borrowed.
     *
     * Lost, Damaged, Reserved, and Under Repair books are rejected even when a
     * manipulated request attempts to submit their database identifier.
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

            if ($book->book_status !== self::BORROWABLE_BOOK_STATUS) {
                throw ValidationException::withMessages([
                    'book_id' => $this->getUnavailableBookMessage($book->book_status),
                ]);
            }

            $activeTransaction = DB::table('borrow_transaction')
                ->where('book_id', $data['book_id'])
                ->whereIn('borrow_status', self::ACTIVE_TRANSACTION_STATUSES)
                ->select('transaction_id')
                ->lockForUpdate()
                ->first();

            if ($activeTransaction !== null) {
                throw ValidationException::withMessages([
                    'book_id' => 'This book already has an active borrowing transaction and cannot be borrowed again.',
                ]);
            }

            DB::table('borrow_transaction')->insert([
                'member_id' => $data['member_id'],
                'book_id' => $data['book_id'],
                'borrow_date' => now()->toDateString(),
                'due_date' => $data['due_date'],
                'return_date' => null,
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
     * Lists only genuinely available books with no active transaction.
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
            ->where('book.book_status', self::BORROWABLE_BOOK_STATUS)
            ->whereNotExists(function ($query) {
                $query
                    ->selectRaw('1')
                    ->from('borrow_transaction')
                    ->whereColumn('borrow_transaction.book_id', 'book.book_id')
                    ->whereIn('borrow_transaction.borrow_status', self::ACTIVE_TRANSACTION_STATUSES);
            })
            ->orderBy('book.book_title')
            ->get();
    }

    /**
     * Returns active Borrowed or Overdue transactions for the borrow list.
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
            ->whereIn('borrow_transaction.borrow_status', self::ACTIVE_TRANSACTION_STATUSES);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('library_member.first_name', 'like', "%{$search}%")
                    ->orWhere('library_member.last_name', 'like', "%{$search}%")
                    ->orWhereRaw('library_member.`m.i.` LIKE ?', ["%{$search}%"])
                    ->orWhere('book.isbn', 'like', "%{$search}%")
                    ->orWhere('book.book_title', 'like', "%{$search}%")
                    ->orWhere('book_author.first_name', 'like', "%{$search}%")
                    ->orWhere('book_author.last_name', 'like', "%{$search}%")
                    ->orWhereRaw('book_author.`m.i.` LIKE ?', ["%{$search}%"])
                    ->orWhere('borrow_transaction.borrow_status', 'like', "%{$search}%");
            });
        }

        return $query
            ->orderByDesc('borrow_transaction.transaction_id')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Gives a clear validation message for each non-borrowable book status.
     */
    private function getUnavailableBookMessage(string $status): string
    {
        return match ($status) {
            'Lost' => 'This book is marked Lost and cannot be borrowed.',
            'Damaged' => 'This book is marked Damaged and cannot be borrowed.',
            'Under Repair' => 'This book is under repair and cannot be borrowed.',
            'Reserved' => 'This book is reserved and cannot be borrowed.',
            'Borrowed' => 'This book is already borrowed.',
            default => 'This book is not available for borrowing.',
        };
    }

    /**
     * Marks Borrowed transactions as Overdue once their due date has passed.
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
