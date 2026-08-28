<?php

namespace Andremellow\Tasks\Actions;

use Andremellow\Tasks\Models\Task;
use Andremellow\Tasks\Services\RecordTaskChange;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeleteTask
{
    public function __construct(private RecordTaskChange $audit) {}

    public function handle(Authenticatable $actor, Task $task): void
    {
        Gate::forUser($actor)->authorize('delete', $task);
        DB::transaction(function () use ($actor, $task): void {
            $this->audit->handle($task, $actor, 'deleted', ['deleted_at' => null], ['deleted_at' => now()->toISOString()]);
            $task->delete();
        });
    }
}
