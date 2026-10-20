<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExecutiveController;
use App\Http\Controllers\ItTaskController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaffTaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'role:staff'])->prefix('staff')->name('staff.')->group(function () {
    Route::post('/tasks', [StaffTaskController::class, 'store'])->name('tasks.store');
    Route::patch('/tasks/{task}/complete', [StaffTaskController::class, 'complete'])->name('tasks.complete');
});

Route::middleware(['auth', 'verified', 'role:ba|developer'])->prefix('it')->name('it.')->group(function () {
    Route::get('/board', [ItTaskController::class, 'board'])->name('board');

    Route::middleware('role:ba')->group(function () {
        Route::get('/tasks/create', [ItTaskController::class, 'create'])->name('tasks.create');
        Route::post('/tasks', [ItTaskController::class, 'store'])->name('tasks.store');
        Route::post('/tasks/{task}/approve', [ItTaskController::class, 'approve'])->name('tasks.approve');
        Route::post('/tasks/{task}/reject', [ItTaskController::class, 'reject'])->name('tasks.reject');
    });

    Route::get('/tasks/{task}', [ItTaskController::class, 'show'])->name('tasks.show');
    Route::post('/tasks/{task}/start', [ItTaskController::class, 'start'])->name('tasks.start');
    Route::patch('/tasks/{task}/dev-info', [ItTaskController::class, 'updateDevInfo'])->name('tasks.dev-info');
    Route::post('/tasks/{task}/testing', [ItTaskController::class, 'markTesting'])->name('tasks.testing');
    Route::post('/tasks/{task}/submit', [ItTaskController::class, 'submitForApproval'])->name('tasks.submit');
    Route::post('/tasks/{task}/deploy', [ItTaskController::class, 'deploy'])->name('tasks.deploy');
});

Route::middleware(['auth', 'verified', 'role:owner|hrd'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ExecutiveController::class, 'index'])->name('index');
    Route::get('/department/{department}', [ExecutiveController::class, 'department'])->name('department');
});

Route::middleware(['auth', 'verified'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::patch('/{notification}/read', [NotificationController::class, 'markRead'])->name('read');
    Route::patch('/read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
