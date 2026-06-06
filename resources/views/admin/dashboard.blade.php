@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('page-title', 'Admin Dashboard')

@section('content')
    <div class="dashboard-intro">
        <h2>Welcome, {{ session('full_name') }}.</h2>
        <p>Select a module below to manage the Library Management System.</p>
    </div>

    <div class="dashboard-grid">
        @foreach ($cards as $card)
            <article class="dashboard-card">
                <div>
                    <h3>{{ $card['title'] }}</h3>
                    <p>{{ $card['description'] }}</p>
                </div>

                <span class="status-pill">{{ $card['status'] }}</span>
            </article>
        @endforeach
    </div>
@endsection