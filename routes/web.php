<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LibraryMemberController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.process');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('admin.only')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/members', [LibraryMemberController::class, 'index'])->name('members.index');
        Route::get('/members/create', [LibraryMemberController::class, 'create'])->name('members.create');
        Route::post('/members', [LibraryMemberController::class, 'store'])->name('members.store');
        Route::get('/members/{memberId}/edit', [LibraryMemberController::class, 'edit'])->name('members.edit');
        Route::put('/members/{memberId}', [LibraryMemberController::class, 'update'])->name('members.update');
        Route::delete('/members/{memberId}', [LibraryMemberController::class, 'destroy'])->name('members.destroy');
    });