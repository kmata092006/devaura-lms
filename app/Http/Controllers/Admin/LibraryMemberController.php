<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LibraryMemberService;
use Illuminate\Http\Request;

class LibraryMemberController extends Controller
{
    private LibraryMemberService $libraryMemberService;

    public function __construct(LibraryMemberService $libraryMemberService)
    {
        $this->libraryMemberService = $libraryMemberService;
    }

    public function index(Request $request)
    {
        $members = $this->libraryMemberService->getMembers(
            $request->query('search'),
            $request->query('role')
        );

        return view('admin.members.index', compact('members'));
    }

    public function create()
    {
        return view('admin.members.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateMember($request);

        $this->libraryMemberService->createMember($validated);

        return redirect()
            ->route('admin.members.index')
            ->with('success', 'Library member added successfully.');
    }

    public function edit(int $memberId)
    {
        $member = $this->libraryMemberService->findMember($memberId);

        return view('admin.members.edit', compact('member'));
    }

    public function update(Request $request, int $memberId)
    {
        $validated = $this->validateMember($request);

        $this->libraryMemberService->updateMember($memberId, $validated);

        return redirect()
            ->route('admin.members.index')
            ->with('success', 'Library member updated successfully.');
    }

    public function destroy(int $memberId)
    {
        $this->libraryMemberService->deleteMember($memberId);

        return redirect()
            ->route('admin.members.index')
            ->with('success', 'Library member deleted successfully.');
    }

    private function validateMember(Request $request): array
    {
        return $request->validate([
            'member_role' => ['required', 'in:student,faculty'],
            'last_name' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:50'],
            'middle_initial' => ['nullable', 'string', 'max:3'],
        ]);
    }
}