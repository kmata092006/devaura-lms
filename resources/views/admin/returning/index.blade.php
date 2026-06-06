@extends('layouts.admin')

@section('title', 'Return Book')
@section('page-title', 'Return Book')

@section('content')
    <div class="page-actions">
        <div>
            <h2>Return Book Transaction</h2>
            <p>Process borrowed books as returned, lost, or damaged.</p>
        </div>

        <a href="{{ route('admin.borrow.index') }}" class="action-button">Go to Borrow Book</a>
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

    <div class="return-info-grid">
        <div class="return-info-card">
            <h3>Normal Return</h3>
            <p>The transaction becomes <strong>Returned</strong> and the book becomes <strong>Available</strong>.</p>
        </div>

        <div class="return-info-card">
            <h3>Lost Book</h3>
            <p>The transaction becomes <strong>Lost</strong> and the book becomes <strong>Lost</strong>.</p>
        </div>

        <div class="return-info-card">
            <h3>Damaged Book</h3>
            <p>The transaction becomes <strong>Returned</strong> and the book becomes <strong>Damaged</strong>.</p>
        </div>
    </div>

    <div class="section-heading">
        <div>
            <h2>Active Borrowed Transactions</h2>
            <p>Only Borrowed and Overdue transactions appear here.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.return.index') }}" class="filter-form">
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search by transaction ID, member, book, ISBN, genre, or status"
        >

        <button type="submit">Search</button>
        <a href="{{ route('admin.return.index') }}" class="secondary-button">Reset</a>
    </form>

    <div class="table-card">
        <table class="data-table return-table">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Member</th>
                    <th>Role</th>
                    <th>Book</th>
                    <th>ISBN</th>
                    <th>Genre</th>
                    <th>Borrow Date</th>
                    <th>Due Date</th>
                    <th>Transaction Status</th>
                    <th>Book Status</th>
                    <th class="return-actions-column">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activeTransactions as $transaction)
                    <tr>
                        <td>{{ $transaction->transaction_id }}</td>
                        <td>
                            {{ $transaction->member_first_name }}
                            {{ $transaction->member_middle_initial ? $transaction->member_middle_initial . '.' : '' }}
                            {{ $transaction->member_last_name }}
                        </td>
                        <td>
                            <span class="role-pill">{{ ucfirst($transaction->member_role) }}</span>
                        </td>
                        <td class="book-title-cell">{{ $transaction->book_title }}</td>
                        <td>{{ $transaction->isbn ?? '-' }}</td>
                        <td>{{ $transaction->genre_name ?? '-' }}</td>
                        <td>{{ $transaction->borrow_date }}</td>
                        <td>{{ $transaction->due_date }}</td>
                        <td>
                            <span class="status-label status-{{ str_replace(' ', '-', strtolower($transaction->borrow_status)) }}">
                                {{ $transaction->borrow_status }}
                            </span>
                        </td>
                        <td>
                            <span class="status-label status-{{ str_replace(' ', '-', strtolower($transaction->book_status)) }}">
                                {{ $transaction->book_status }}
                            </span>
                        </td>
                        <td>
                            <div class="return-action-group">
                                <form
                                    method="POST"
                                    action="{{ route('admin.return.normal', $transaction->transaction_id) }}"
                                    onsubmit="return confirm('Confirm normal return for this book?');"
                                >
                                    @csrf
                                    <button type="submit" class="small-button return-normal">Return</button>
                                </form>

                                <form
                                    method="POST"
                                    action="{{ route('admin.return.lost', $transaction->transaction_id) }}"
                                    onsubmit="return confirm('Mark this book as lost? This will close the transaction.');"
                                >
                                    @csrf
                                    <button type="submit" class="small-button return-lost">Lost</button>
                                </form>

                                <form
                                    method="POST"
                                    action="{{ route('admin.return.damaged', $transaction->transaction_id) }}"
                                    onsubmit="return confirm('Mark this book as damaged? This will close the transaction.');"
                                >
                                    @csrf
                                    <button type="submit" class="small-button return-damaged">Damaged</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="empty-table">
                            No active borrowed or overdue transactions found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $activeTransactions->links() }}
    </div>
@endsection