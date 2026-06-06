<?php

use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\Admin\BookListController;
use App\Http\Controllers\Admin\BorrowController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LibraryMemberController;
use App\Http\Controllers\Admin\ReturnController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/attendance', [AttendanceController::class, 'kiosk'])->name('attendance.kiosk');
Route::post('/attendance/member/time-in', [AttendanceController::class, 'timeInMemberByReference'])->name('attendance.member.time-in');
Route::post('/attendance/member/time-out', [AttendanceController::class, 'timeOutMemberByReference'])->name('attendance.member.time-out');
Route::post('/attendance/visitor/time-in', [AttendanceController::class, 'timeInVisitor'])->name('attendance.visitor.time-in');
Route::post('/attendance/visitor/time-out', [AttendanceController::class, 'timeOutVisitor'])->name('attendance.visitor.time-out');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.process');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('admin.only')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance/personnel/time-in', [AttendanceController::class, 'timeInCurrentPersonnel'])->name('attendance.personnel.time-in');
        Route::post('/attendance/personnel/time-out', [AttendanceController::class, 'timeOutCurrentPersonnel'])->name('attendance.personnel.time-out');

        Route::get('/members', [LibraryMemberController::class, 'index'])->name('members.index');
        Route::get('/members/create', [LibraryMemberController::class, 'create'])->name('members.create');
        Route::post('/members', [LibraryMemberController::class, 'store'])->name('members.store');
        Route::get('/members/{memberId}/edit', [LibraryMemberController::class, 'edit'])->name('members.edit');
        Route::put('/members/{memberId}', [LibraryMemberController::class, 'update'])->name('members.update');
        Route::delete('/members/{memberId}', [LibraryMemberController::class, 'destroy'])->name('members.destroy');

        Route::get('/books', [BookController::class, 'index'])->name('books.index');
        Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
        Route::post('/books', [BookController::class, 'store'])->name('books.store');
        Route::get('/books/{bookId}/edit', [BookController::class, 'edit'])->name('books.edit');
        Route::put('/books/{bookId}', [BookController::class, 'update'])->name('books.update');
        Route::delete('/books/{bookId}', [BookController::class, 'destroy'])->name('books.destroy');

        Route::get('/book-list', [BookListController::class, 'index'])->name('book-list.index');

        Route::get('/borrow', [BorrowController::class, 'index'])->name('borrow.index');
        Route::post('/borrow', [BorrowController::class, 'store'])->name('borrow.store');

        Route::get('/return', [ReturnController::class, 'index'])->name('return.index');
        Route::post('/return/{transactionId}/normal', [ReturnController::class, 'returnNormal'])->name('return.normal');
        Route::post('/return/{transactionId}/lost', [ReturnController::class, 'markLost'])->name('return.lost');
        Route::post('/return/{transactionId}/damaged', [ReturnController::class, 'markDamaged'])->name('return.damaged');
    });