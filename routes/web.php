<?php

use Andremellow\Tasks\Http\Controllers\TaskAttachmentController;
use Andremellow\Tasks\Models\Task;
use Illuminate\Support\Facades\Route;

Route::middleware(config('tasks.web.middleware', ['web', 'auth', 'tasks.access']))
    ->prefix(config('tasks.web.prefix', 'tasks'))
    ->name(config('tasks.web.name', 'tasks.'))
    ->group(function (): void {
        Route::view('/', 'tasks::page')->name('index');
        Route::get('/{task}', fn (Task $task) => view('tasks::show', compact('task')))->name('show');
        Route::get('/{task}/attachments/{media}', [TaskAttachmentController::class, 'show'])->name('attachments.show');
        Route::get('/{task}/attachments/{media}/download', [TaskAttachmentController::class, 'download'])->name('attachments.download');
    });
