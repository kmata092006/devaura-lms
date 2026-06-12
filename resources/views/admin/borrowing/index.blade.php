@extends('layouts.admin')

@section('title', 'Borrow Book')
@section('page-title', 'Borrow Book')

@section('content')
    <div class="page-actions">
        <div>
            <h2>Borrow Book Transaction</h2>
            <p>Select a library member and an available book to create a borrow transaction.</p>
        </div>

        <a href="{{ route('admin.books.index') }}" class="action-button">Manage Books</a>
    </div>

    @if (session('success'))
        <div class="alert success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert error">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="borrow-layout">
        <div class="form-card borrow-form-card">
            <div class="form-card-header">
                <h2>New Borrow Transaction</h2>
                <p>Only books with Available status can be borrowed.</p>
            </div>

            <form method="POST" action="{{ route('admin.borrow.store') }}" class="record-form">
                @csrf

                <div class="form-group">
                    <label for="member_id">Library Member</label>
                    <select name="member_id" id="member_id" required>
                        <option value="">Select library member</option>
                        @foreach ($members as $member)
                            <option value="{{ $member->member_id }}" {{ old('member_id') == $member->member_id ? 'selected' : '' }}>
                                [ID: {{ $member->member_id }}]
                                {{ $member->first_name }}
                                {{ $member->middle_initial ? $member->middle_initial . '.' : '' }}
                                {{ $member->last_name }}
                                — {{ ucfirst($member->member_role) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="book_id">Available Book</label>
                    <select name="book_id" id="book_id" required>
                        <option value="">Select available book</option>
                        @foreach ($availableBooks as $book)
                            <option value="{{ $book->book_id }}" {{ old('book_id') == $book->book_id ? 'selected' : '' }}>
                                [ID: {{ $book->book_id }}]
                                {{ $book->book_title }}
                                {{ $book->isbn ? '— ISBN: ' . $book->isbn : '' }}
                                {{ $book->genre_name ? '— ' . $book->genre_name : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="due_date">Due Date</label>
                    <input
                        type="date"
                        name="due_date"
                        id="due_date"
                        value="{{ old('due_date', $defaultDueDate) }}"
                        min="{{ now()->toDateString() }}"
                        required
                    >
                </div>

                <div class="form-actions">
                    <button
                        type="submit"
                        class="primary-button"
                        {{ $members->isEmpty() || $availableBooks->isEmpty() ? 'disabled' : '' }}
                    >
                        Borrow Book
                    </button>

                    <a href="{{ route('admin.borrow.index') }}" class="secondary-button">Reset</a>
                </div>

                @if ($members->isEmpty())
                    <p class="form-note warning-note">No library members found. Add a member first.</p>
                @endif

                @if ($availableBooks->isEmpty())
                    <p class="form-note warning-note">No available books found. Add or update a book status first.</p>
                @endif
            </form>
        </div>

        <div class="borrow-info-card">
            <h3>Borrowing Rules</h3>
            <ul>
                <li>The selected book must have <strong>Available</strong> status.</li>
                <li>After borrowing, the book status becomes <strong>Borrowed</strong>.</li>
                <li>The transaction status becomes <strong>Borrowed</strong>.</li>
                <li>Overdue records are marked based on the due date.</li>
            </ul>
        </div>
    </div>

    <div class="section-heading">
        <div>
            <h2>Active Borrowed Transactions</h2>
            <p>These are transactions that are currently Borrowed or Overdue.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.borrow.index') }}" class="filter-form">
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search by transaction ID, member, book, ISBN, or status"
        >

        <button type="submit">Search</button>
        <a href="{{ route('admin.borrow.index') }}" class="secondary-button">Reset</a>
    </form>

    <div class="table-card">
        <table class="data-table borrow-table">
            <thead>
                <tr>
                    <th>Member</th>
                    <th>Role</th>
                    <th>Book</th>
                    <th>ISBN</th>
                    <th>Borrow Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activeTransactions as $transaction)
                    <tr>
                        <td>{{ $transaction->member_first_name }} {{ $transaction->member_middle_initial ? $transaction->member_middle_initial . '.' : '' }} {{ $transaction->member_last_name }}</td>
                        <td><span class="role-pill">{{ ucfirst($transaction->member_role) }}</span></td>
                        <td class="book-title-cell">{{ $transaction->book_title }}</td>
                        <td>{{ $transaction->isbn ?? '-' }}</td>
                        <td>{{ $transaction->borrow_date }}</td>
                        <td>{{ $transaction->due_date }}</td>
                        <td>
                            <span class="status-label status-{{ str_replace(' ', '-', strtolower($transaction->borrow_status)) }}">
                                {{ $transaction->borrow_status }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-table">No active borrow transactions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $activeTransactions->links() }}
    </div>
@endsection