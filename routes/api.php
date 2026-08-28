<?php

use Andremellow\Tasks\Http\Controllers\TaskApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(config('tasks.api.middleware', ['api', 'auth:sanctum', 'tasks.access']))
    ->prefix(config('tasks.api.prefix', 'api/tasks'))
    ->name(config('tasks.api.name', 'api.tasks.'))
    ->group(function (): void {
        Route::get('/', [TaskApiController::class, 'index'])->name('index');
        Route::post('/', [TaskApiController::class, 'store'])->name('store');
        Route::get('/{task}', [TaskApiController::class, 'show'])->name('show');
        Route::patch('/{task}', [TaskApiController::class, 'update'])->name('update');
        Route::put('/{task}/assignee', [TaskApiController::class, 'assign'])->name('assign');
        Route::put('/{task}/position', [TaskApiController::class, 'move'])->name('move');
        Route::delete('/{task}', [TaskApiController::class, 'destroy'])->name('destroy');
        Route::post('/{task}/restore', [TaskApiController::class, 'restore'])->name('restore');
    });
