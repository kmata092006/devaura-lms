<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LibraryMemberService
{
    /**
     * Returns paginated library members with optional search and role filters.
     *
     * The result also includes linked borrowing and attendance record counts so
     * the interface can prevent users from attempting an invalid deletion.
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
     * Attempts to delete a member while preserving linked transaction and
     * attendance history.
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

            $borrowTransactionCount = DB::table('borrow_transaction')
                ->where('member_id', $memberId)
                ->count();

            $attendanceLogCount = DB::table('attendance_log')
                ->where('member_id', $memberId)
                ->count();

            if ($borrowTransactionCount > 0 || $attendanceLogCount > 0) {
                $linkedRecords = [];

                if ($borrowTransactionCount > 0) {
                    $linkedRecords[] = $borrowTransactionCount === 1
                        ? '1 borrowing transaction'
                        : "{$borrowTransactionCount} borrowing transactions";
                }

                if ($attendanceLogCount > 0) {
                    $linkedRecords[] = $attendanceLogCount === 1
                        ? '1 attendance record'
                        : "{$attendanceLogCount} attendance records";
                }

                return [
                    'deleted' => false,
                    'message' => 'This library member cannot be deleted because the account is linked to '
                        . $this->formatLinkedRecords($linkedRecords)
                        . '. Keep the member record to preserve the system history.',
                ];
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

            return [
                'deleted' => true,
                'message' => 'Library member deleted successfully.',
            ];
        });
    }

    /**
     * Formats dependency descriptions for a readable validation message.
     *
     * @param array<int, string> $records
     */
    private function formatLinkedRecords(array $records): string
    {
        if (count($records) <= 1) {
            return $records[0] ?? 'existing system records';
        }

        $lastRecord = array_pop($records);

        return implode(', ', $records) . ' and ' . $lastRecord;
    }
}