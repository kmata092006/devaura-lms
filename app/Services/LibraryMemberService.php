<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LibraryMemberService
{
    /**
     * Returns paginated library members with optional search and role filters.
     */
    public function getMembers(?string $search = null, ?string $role = null): LengthAwarePaginator
    {
        $query = DB::table('library_member')
            ->select(
                'member_id',
                'member_role',
                'reference_number',
                'last_name',
                'first_name',
                DB::raw('`m.i.` as middle_initial')
            );

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('member_id', 'like', "%{$search}%")
                    ->orWhere('member_role', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhereRaw('`m.i.` LIKE ?', ["%{$search}%"]);
            });
        }

        if ($role) {
            $query->where('member_role', $role);
        }

        return $query
            ->orderBy('last_name')
            ->orderBy('first_name')
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
     * Deletes a member only when no attendance or borrow records depend on it.
     */
    public function deleteMember(int $memberId): void
    {
        $hasBorrowTransactions = DB::table('borrow_transaction')
            ->where('member_id', $memberId)
            ->exists();

        $hasAttendanceLogs = DB::table('attendance_log')
            ->where('member_id', $memberId)
            ->exists();

        if ($hasBorrowTransactions || $hasAttendanceLogs) {
            abort(422, 'This library member cannot be deleted because they already have transaction or attendance records.');
        }

        DB::table('library_member')
            ->where('member_id', $memberId)
            ->delete();
    }
}
