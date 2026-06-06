<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $cards = [
            [
                'title' => 'Attendance Logs',
                'description' => 'Log time-in and time-out for library personnel, members, and visitors.',
                'status' => 'Coming soon',
            ],
            [
                'title' => 'Library Members',
                'description' => 'Manage student and faculty library member records.',
                'status' => 'Coming soon',
            ],
            [
                'title' => 'Book Management',
                'description' => 'Add, edit, delete, and manage books, authors, publishers, and genres.',
                'status' => 'Coming soon',
            ],
            [
                'title' => 'Borrow Book',
                'description' => 'Process book borrowing transactions for library members.',
                'status' => 'Coming soon',
            ],
            [
                'title' => 'Return Book',
                'description' => 'Process returned, overdue, lost, or damaged books.',
                'status' => 'Coming soon',
            ],
            [
                'title' => 'Book List / Search',
                'description' => 'View and search all books by title, ISBN, author, publisher, genre, and status.',
                'status' => 'Coming soon',
            ],
            [
                'title' => 'Reports',
                'description' => 'Generate attendance, book status, personnel, member, visitor, and book reports.',
                'status' => 'Coming soon',
            ],
        ];

        return view('admin.dashboard', compact('cards'));
    }
}