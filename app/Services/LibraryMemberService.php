<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LibraryMemberService
{
    /**
     * Returns paginated library members with optional search and role filters.
     *
     * The result includes active borrowing and attendance counts. Returned
     * transactions remain historical records but are not shown as active loans
     * and do not prevent member deletion.
     */
    public function getMembers(?string $search = null, ?string $role = null): LengthAwarePaginator
    {
        $query = DB::table('library_member')
            ->select(
                'library_member.member_id',
                'library_member.member_role',
                'library_member.reference_number',
                'library_member.last_name',
                'library_member.first_name',
                DB::raw('library_member.`m.i.` as middle_initial')
            )
            ->selectSub(function ($subquery) {
                $subquery
                    ->from('borrow_transaction')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn(
                        'borrow_transaction.member_id',
                        'library_member.member_id'
                    )
                    ->whereIn(
                        'borrow_transaction.borrow_status',
                        ['Borrowed', 'Overdue']
                    );
            }, 'borrow_transaction_count')
            ->selectSub(function ($subquery) {
                $subquery
                    ->from('attendance_log')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn(
                        'attendance_log.member_id',
                        'library_member.member_id'
                    );
            }, 'attendance_log_count');

        $normalizedSearch = trim((string) $search);

        if ($normalizedSearch !== '') {
            $query->where(function ($memberQuery) use ($normalizedSearch) {
                $searchValue = "%{$normalizedSearch}%";

                $memberQuery
                    ->where('library_member.member_role', 'like', $searchValue)
                    ->orWhere('library_member.reference_number', 'like', $searchValue)
                    ->orWhere('library_member.last_name', 'like', $searchValue)
                    ->orWhere('library_member.first_name', 'like', $searchValue)
                    ->orWhereRaw(
                        'library_member.`m.i.` LIKE ?',
                        [$searchValue]
                    );
            });
        }

        $normalizedRole = strtolower(trim((string) $role));

        if (in_array($normalizedRole, ['student', 'faculty'], true)) {
            $query->where('library_member.member_role', $normalizedRole);
        }

        return $query
            ->orderBy('library_member.last_name')
            ->orderBy('library_member.first_name')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Loads one library member for editing or detail display.
     */
    public function findMember(int $memberId): object
    {
        $member = DB::table('library_member')
            ->select(
                'member_id',
                'member_role',
                'reference_number',
                'last_name',
                'first_name',
                DB::raw('`m.i.` as middle_initial')
            )
            ->where('member_id', $memberId)
            ->first();

        if (!$member) {
            abort(404, 'Library member not found.');
        }

        return $member;
    }

    /**
     * Creates a student or faculty library member record.
     */
    public function createMember(array $data): void
    {
        DB::insert(
            'INSERT INTO library_member (member_role, reference_number, last_name, first_name, `m.i.`) VALUES (?, ?, ?, ?, ?)',
            [
                $data['member_role'],
                $data['reference_number'],
                $data['last_name'],
                $data['first_name'],
                $data['middle_initial'] ?? null,
            ]
        );
    }

    /**
     * Updates a member's identity, role, and reference number.
     */
    public function updateMember(int $memberId, array $data): void
    {
        DB::update(
            'UPDATE library_member
             SET member_role = ?, reference_number = ?, last_name = ?, first_name = ?, `m.i.` = ?
             WHERE member_id = ?',
            [
                $data['member_role'],
                $data['reference_number'],
                $data['last_name'],
                $data['first_name'],
                $data['middle_initial'] ?? null,
                $memberId,
            ]
        );
    }

    /**
     * Deletes a member when no active Borrowed or Overdue transaction exists.
     * Attendance logs and completed borrowing rows are removed with the member,
     * including attendance records whose time_out is still null.
     *
     * @return array{deleted: bool, message: string}
     */
    public function deleteMember(int $memberId): array
    {
        return DB::transaction(function () use ($memberId): array {
            $member = DB::table('library_member')
                ->where('member_id', $memberId)
                ->lockForUpdate()
                ->first();

            if (!$member) {
                return [
                    'deleted' => false,
                    'message' => 'The selected library member no longer exists.',
                ];
            }

            $borrowTransactions = DB::table('borrow_transaction')
                ->where('member_id', $memberId)
                ->select('transaction_id', 'borrow_status')
                ->lockForUpdate()
                ->get();

            $activeBorrowTransactionCount = $borrowTransactions
                ->whereIn('borrow_status', ['Borrowed', 'Overdue'])
                ->count();

            if ($activeBorrowTransactionCount > 0) {
                $borrowingDescription = $activeBorrowTransactionCount === 1
                    ? '1 active borrowing transaction'
                    : "{$activeBorrowTransactionCount} active borrowing transactions";

                return [
                    'deleted' => false,
                    'message' => 'This library member cannot be deleted because the account still has '
                        . $borrowingDescription
                        . '. Return or resolve the borrowed book first.',
                ];
            }

            $completedBorrowTransactionCount = $borrowTransactions->count();

            if ($completedBorrowTransactionCount > 0) {
                DB::table('borrow_transaction')
                    ->where('member_id', $memberId)
                    ->delete();
            }

            $attendanceLogCount = DB::table('attendance_log')
                ->where('member_id', $memberId)
                ->count();

            if ($attendanceLogCount > 0) {
                DB::table('attendance_log')
                    ->where('member_id', $memberId)
                    ->delete();
            }

            $deletedRows = DB::table('library_member')
                ->where('member_id', $memberId)
                ->delete();

            if ($deletedRows === 0) {
                return [
                    'deleted' => false,
                    'message' => 'The library member could not be deleted. Please refresh the page and try again.',
                ];
            }

            $removedRecordTypes = [];

            if ($completedBorrowTransactionCount > 0) {
                $removedRecordTypes[] = 'completed borrowing records';
            }

            if ($attendanceLogCount > 0) {
                $removedRecordTypes[] = 'attendance records';
            }

            $message = empty($removedRecordTypes)
                ? 'Library member deleted successfully.'
                : 'Library member and related ' . implode(' and ', $removedRecordTypes) . ' deleted successfully.';

            return [
                'deleted' => true,
                'message' => $message,
            ];
        });
    }

}
