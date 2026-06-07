<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LibraryMemberService;
use Illuminate\Http\Request;

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
     * Deletes a member after service-level dependency checks.
     */
    public function destroy(int $memberId)
    {
        $this->libraryMemberService->deleteMember($memberId);

        return redirect()
            ->route('admin.members.index')
            ->with('success', 'Library member deleted successfully.');
    }

    /**
     * Validates member identity, role, and reference number input.
     */
    private function validateMember(Request $request, ?int $memberId = null): array
    {
    return $request->validate([
        'member_role' => ['required', 'in:student,faculty'],
        'reference_number' => [
            'required',
            'string',
            'max:50',
            'unique:library_member,reference_number,' . $memberId . ',member_id',
        ],
        'last_name' => ['required', 'string', 'max:50'],
        'first_name' => ['required', 'string', 'max:50'],
        'middle_initial' => ['nullable', 'string', 'max:3'],
    ]);
    }
}
