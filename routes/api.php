<?php

use App\Http\Controllers\Api\TaskAutoLogController;
use Illuminate\Support\Facades\Route;

Route::post('/v1/tasks/auto-log', [TaskAutoLogController::class, 'store'])
    ->middleware(['ksp.token', 'throttle:60,1'])
    ->name('api.tasks.auto-log');
