<?php

namespace Andremellow\Tasks\Actions;

use Andremellow\Tasks\Models\Task;
use Andremellow\Tasks\Services\RecordTaskChange;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RestoreTask
{
    public function __construct(private RecordTaskChange $audit) {}

    public function handle(Authenticatable $actor, Task $task): Task
    {
        Gate::forUser($actor)->authorize('restore', $task);

        return DB::transaction(function () use ($actor, $task): Task {
            $deletedAt = $task->deleted_at?->toISOString();
            $task->restore();
            $this->audit->handle($task, $actor, 'restored', ['deleted_at' => $deletedAt], ['deleted_at' => null]);

            return $task->fresh();
        });
    }
}
