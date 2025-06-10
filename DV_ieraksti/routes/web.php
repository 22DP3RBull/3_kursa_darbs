<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\AdminRegisterController;

Route::get('/', function () {
    return Inertia::render('MainPage');
})->name('main');

Route::get('main', function () {
    return Inertia::render('MainPage');
})->name('main');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('students', StudentController::class)->except(['create', 'edit', 'show']);
    Route::post('students', [StudentController::class, 'store'])->name('students.store'); // Ensure store route is defined
    Route::get('admin-dashboard', function () {
        sleep(0.3);
        return Inertia::render('AdminDashboard');
    })->name('admin-dashboard');
});

Route::middleware(['auth', 'is_admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
});

Route::get('login', [AuthenticatedSessionController::class, 'create'])
    ->name('login');

Route::post('login', [AuthenticatedSessionController::class, 'store']);

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/students', [StudentController::class, 'index']);
Route::post('/students', [StudentController::class, 'store']);
Route::get('/students/{id}', [StudentController::class, 'show']);
Route::put('/students/{id}', [StudentController::class, 'update']);
Route::delete('/students/{id}', [StudentController::class, 'destroy']);
Route::patch('/students/{id}/checked-in', [StudentController::class, 'updateCheckedInStatus'])->withoutMiddleware('auth');
Route::get('/students/group-by-floor', [StudentController::class, 'groupByFloor'])->name('students.groupByFloor');
Route::get('/students/order-by', [StudentController::class, 'orderByField'])->name('students.orderByField');
Route::get('/students/group-by-room', [StudentController::class, 'groupByRoom'])->name('students.groupByRoom');
Route::get('/students/group-by-checked-in', [StudentController::class, 'groupByCheckedInStatus'])->name('students.groupByCheckedInStatus');
Route::get('/history', [StudentController::class, 'fetchHistory'])->name('history.fetch');
Route::get('/students/{id}/time-since-last-action', [StudentController::class, 'calculateTimeSinceLastAction'])->name('students.timeSinceLastAction');
Route::get('/history/search', [StudentController::class, 'searchHistory'])->name('history.search');
Route::get('/dashboard-stats', [StudentController::class, 'getDashboardStats'])->name('dashboard.stats');
Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
Route::get('/register', [AdminRegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [AdminRegisterController::class, 'register']);

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
