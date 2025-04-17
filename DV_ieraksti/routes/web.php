<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

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
        return Inertia::render('AdminDashboard');
    })->name('admin-dashboard');
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

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
