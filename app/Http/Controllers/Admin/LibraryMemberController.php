<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LibraryMemberService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LibraryMemberController extends Controller
{
    private LibraryMemberService $libraryMemberService;

    /**
     * Injects the library member service used by this controller.
     */
    public function __construct(LibraryMemberService $libraryMemberService)
    {
        $this->libraryMemberService = $libraryMemberService;
    }

    /**
     * Shows the paginated member list with optional filters.
     */
    public function index(Request $request)
    {
        $members = $this->libraryMemberService->getMembers(
            $request->query('search'),
            $request->query('role')
        );

        return view('admin.members.index', compact('members'));
    }

    /**
     * Shows the form for adding a member.
     */
    public function create()
    {
        return view('admin.members.create');
    }

    /**
     * Validates and stores a new library member.
     */
    public function store(Request $request)
    {
        $validated = $this->validateMember($request);

        $this->libraryMemberService->createMember($validated);

        return redirect()
            ->route('admin.members.index')
            ->with('success', 'Library member added successfully.');
    }

    /**
     * Shows the edit form for one library member.
     */
    public function edit(int $memberId)
    {
        $member = $this->libraryMemberService->findMember($memberId);

        return view('admin.members.edit', compact('member'));
    }

    /**
     * Validates and updates an existing library member.
     */
    public function update(Request $request, int $memberId)
    {
        $validated = $this->validateMember($request, $memberId);

        $this->libraryMemberService->updateMember($memberId, $validated);

        return redirect()
            ->route('admin.members.index')
            ->with('success', 'Library member updated successfully.');
    }

    /**
     * Deletes a member when no borrowing transaction depends on the account.
     */
    public function destroy(int $memberId)
    {
        $result = $this->libraryMemberService->deleteMember($memberId);

        return redirect()
            ->route('admin.members.index')
            ->with($result['deleted'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Validates member identity, role, and the role-specific reference number.
     */
    private function validateMember(Request $request, ?int $memberId = null): array
    {
        $request->merge([
            'member_role' => strtolower(trim((string) $request->input('member_role'))),
            'reference_number' => $this->normalizeReferenceNumber(
                $request->input('reference_number')
            ),
        ]);

        $memberRole = (string) $request->input('member_role');

        $referenceNumberRules = [
            'bail',
            'required',
            'string',
        ];

        if ($memberRole === 'student') {
            $referenceNumberRules[] = 'size:12';
            $referenceNumberRules[] = 'regex:/^\d{12}$/';
        } elseif ($memberRole === 'faculty') {
            $referenceNumberRules[] = 'min:4';
            $referenceNumberRules[] = 'max:30';
            $referenceNumberRules[] = 'regex:/^(?=.*[A-Z])[A-Z0-9]+(?:[-_][A-Z0-9]+)*$/';
        }

        $referenceNumberRules[] = function (
            string $attribute,
            mixed $value,
            Closure $fail
        ) use ($memberId): void {
            $duplicateQuery = DB::table('library_member')
                ->whereRaw(
                    'UPPER(TRIM(reference_number)) = ?',
                    [strtoupper(trim((string) $value))]
                );

            if ($memberId !== null) {
                $duplicateQuery->where('member_id', '!=', $memberId);
            }

            if ($duplicateQuery->exists()) {
                $fail('This reference number is already registered to another library member.');
            }
        };

        return $request->validate([
            'member_role' => ['required', Rule::in(['student', 'faculty'])],
            'reference_number' => $referenceNumberRules,
            'last_name' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:50'],
            'middle_initial' => ['nullable', 'string', 'max:3'],
        ], [
            'member_role.required' => 'Please select whether the member is a student or faculty member.',
            'member_role.in' => 'The selected member role is invalid.',
            'reference_number.required' => $memberRole === 'student'
                ? 'The student LRN is required.'
                : 'The Faculty ID is required.',
            'reference_number.size' => 'The student LRN must contain exactly 12 digits.',
            'reference_number.min' => 'The Faculty ID must contain at least 4 characters.',
            'reference_number.max' => 'The Faculty ID must not exceed 30 characters.',
            'reference_number.regex' => $memberRole === 'student'
                ? 'The student LRN must contain exactly 12 digits. Letters, spaces, and symbols are not allowed.'
                : 'The Faculty ID must contain at least one letter and may only use letters, numbers, hyphens, and underscores. Hyphens and underscores must appear between letters or numbers.',
        ]);
    }

    /**
     * Stores reference numbers in one consistent searchable format.
     */
    private function normalizeReferenceNumber(mixed $referenceNumber): string
    {
        return strtoupper(trim((string) $referenceNumber));
    }
}
