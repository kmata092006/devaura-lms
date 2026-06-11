<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\AuditTrailController;
use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\Admin\BookListController;
use App\Http\Controllers\Admin\BorrowController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\LibraryMemberController;
use App\Http\Controllers\Admin\LibraryPersonnelController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReturnController;
use App\Http\Controllers\Admin\RoleController; // Added for Role Permissions
use App\Http\Middleware\AdminOnly;
use App\Http\Middleware\AuditTrailLogger;
use Illuminate\Support\Facades\Route;

// Sends the root URL to the LMS login page.
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->name('login.submit');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

Route::get('/attendance/admin-login', [AuthController::class, 'kioskAdminLogin'])
    ->name('attendance.admin-login');

Route::get('/attendance', [AttendanceController::class, 'kiosk'])
    ->name('attendance.kiosk');

Route::post('/attendance/member/time-in', [AttendanceController::class, 'timeInMemberByReference'])
    ->middleware(AuditTrailLogger::class)
    ->name('attendance.member.time-in');

Route::post('/attendance/member/time-out', [AttendanceController::class, 'timeOutMemberByReference'])
    ->middleware(AuditTrailLogger::class)
    ->name('attendance.member.time-out');

Route::post('/attendance/visitor/time-in', [AttendanceController::class, 'timeInVisitor'])
    ->middleware(AuditTrailLogger::class)
    ->name('attendance.visitor.time-in');

Route::post('/attendance/visitor/time-out', [AttendanceController::class, 'timeOutVisitor'])
    ->middleware(AuditTrailLogger::class)
    ->name('attendance.visitor.time-out');

// ========================================================================
// ADMIN ROUTE GROUPS (All URLs here start with /admin and name 'admin.')
// ========================================================================
Route::prefix('admin')
    ->name('admin.')
    ->group(function () {
        /* #region ALL ROLES - Dashboard, Attendance, Borrow, Return, Book List */
        Route::middleware(['role:admin,staff,student staff', AuditTrailLogger::class])->group(function () {
            Route::get('/dashboard', function () {
                return view('admin.dashboard');
            })->name('dashboard');

            Route::get('/book-list', [BookListController::class, 'index'])
                ->name('book-list.index');

            Route::get('/borrow', [BorrowController::class, 'index'])
                ->name('borrow.index');

            Route::post('/borrow', [BorrowController::class, 'store'])
                ->name('borrow.store');

            Route::get('/return', [ReturnController::class, 'index'])
                ->name('return.index');

            Route::post('/return/{transactionId}/return', [ReturnController::class, 'returnBook'])
                ->name('return.return-book');

            Route::post('/return/{transactionId}/lost', [ReturnController::class, 'markAsLost'])
                ->name('return.mark-lost');

            Route::post('/return/{transactionId}/damaged', [ReturnController::class, 'markAsDamaged'])
                ->name('return.mark-damaged');

            Route::get('/attendance', [AttendanceController::class, 'index'])
                ->name('attendance.index');

            Route::post('/attendance/personnel/time-in', [AttendanceController::class, 'timeInPersonnel'])
                ->name('attendance.personnel.time-in');

            Route::post('/attendance/personnel/time-out', [AttendanceController::class, 'timeOutPersonnel'])
                ->name('attendance.personnel.time-out');
        });
        /* #endregion */

        /* #region ADMIN & STAFF ONLY - Library Members, Reports, Audit Trail */
        Route::middleware(['role:admin,staff', AuditTrailLogger::class])->group(function () {
            
            // Moved Library Members here to restrict student staff
            Route::get('/members', [LibraryMemberController::class, 'index'])
                ->name('members.index');

            Route::get('/members/create', [LibraryMemberController::class, 'create'])
                ->name('members.create');

            Route::post('/members', [LibraryMemberController::class, 'store'])
                ->name('members.store');

            Route::get('/members/{memberId}/edit', [LibraryMemberController::class, 'edit'])
                ->name('members.edit');

            Route::put('/members/{memberId}', [LibraryMemberController::class, 'update'])
                ->name('members.update');

            Route::delete('/members/{memberId}', [LibraryMemberController::class, 'destroy'])
                ->name('members.destroy');

            // Moved Reports here to grant staff access
            Route::get('/reports', [ReportController::class, 'index'])
                ->name('reports.index');

            Route::get('/audit-trail', [AuditTrailController::class, 'index'])
                ->name('audit-trail.index');
        });
        /* #endregion */

        /* #region STRICTLY ADMIN ONLY - Departments, Book Management, Personnel, Role Permissions */
        Route::middleware(['role:admin', AuditTrailLogger::class])->group(function () {
            
            // New Role Permissions Route
            Route::get('/roles', [RoleController::class, 'index'])
                ->name('roles.index');
                
            Route::get('/books/google-books/search', [BookController::class, 'searchGoogleBooks'])
                ->name('books.google-books.search');
                
            Route::get('/books', [BookController::class, 'index'])
                ->name('books.index');

            Route::get('/books/create', [BookController::class, 'create'])
                ->name('books.create');

            Route::post('/books', [BookController::class, 'store'])
                ->name('books.store');

            Route::get('/books/{bookId}/edit', [BookController::class, 'edit'])
                ->name('books.edit');

            Route::put('/books/{bookId}', [BookController::class, 'update'])
                ->name('books.update');

            Route::delete('/books/{bookId}', [BookController::class, 'destroy'])
                ->name('books.destroy');

            Route::get('/personnel', [LibraryPersonnelController::class, 'index'])
                ->name('personnel.index');

            Route::get('/personnel/create', [LibraryPersonnelController::class, 'create'])
                ->name('personnel.create');

            Route::post('/personnel', [LibraryPersonnelController::class, 'store'])
                ->name('personnel.store');

            Route::get('/personnel/{personnelId}/edit', [LibraryPersonnelController::class, 'edit'])
                ->name('personnel.edit');

            Route::put('/personnel/{personnelId}', [LibraryPersonnelController::class, 'update'])
                ->name('personnel.update');

            Route::delete('/personnel/{personnelId}', [LibraryPersonnelController::class, 'destroy'])
                ->name('personnel.destroy');

            // Consolidated all Departments routes here to strictly restrict staff
            Route::get('/departments', [DepartmentController::class, 'index'])
                ->name('departments.index');

            Route::get('/departments/create', [DepartmentController::class, 'create'])
                ->name('departments.create');

            Route::post('/departments', [DepartmentController::class, 'store'])
                ->name('departments.store');

            Route::get('/departments/{departmentId}/edit', [DepartmentController::class, 'edit'])
                ->name('departments.edit');

            Route::put('/departments/{departmentId}', [DepartmentController::class, 'update'])
                ->name('departments.update');

            Route::delete('/departments/{departmentId}', [DepartmentController::class, 'destroy'])
                ->name('departments.destroy');
        });
        /* #endregion */
    });