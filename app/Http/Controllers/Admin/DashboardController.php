<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /**
     * Shows dashboard cards that link to available LMS modules.
     */
    public function index()
    {
        $cards = [
            [
                'title' => 'Attendance Logs',
                'description' => 'Log time-in and time-out for library personnel, members, and visitors.',
                'status' => 'Available',
                'url' => route('admin.attendance.index'),
            ],
            [
                'title' => 'Library Members',
                'description' => 'Manage student and faculty library member records.',
                'status' => 'Available',
                'url' => route('admin.members.index'),
            ],
            [
                'title' => 'Book Management',
                'description' => 'Add, edit, delete, and manage books, authors, publishers, and genres.',
                'status' => 'Available',
                'url' => route('admin.books.index'),
            ],
            [
                'title' => 'Borrow Book',
                'description' => 'Process book borrowing transactions for library members.',
                'status' => 'Available',
                'url' => route('admin.borrow.index'),
            ],
            [
                'title' => 'Return Book',
                'description' => 'Process returned, overdue, lost, or damaged books.',
                'status' => 'Available',
                'url' => route('admin.return.index'),
            ],
            [
                'title' => 'Book List / Search',
                'description' => 'View and search all books by title, ISBN, author, publisher, genre, and status.',
                'status' => 'Available',
                'url' => route('admin.book-list.index'),
            ],
            [
                'title' => 'Reports',
                'description' => 'Generate attendance, book status, personnel, member, visitor, and book reports.',
                'status' => 'Next module',
                'url' => '#',
            ],
        ];

        return view('admin.dashboard', compact('cards'));
    }
}
