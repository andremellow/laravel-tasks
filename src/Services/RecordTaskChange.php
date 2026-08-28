<?php

namespace Andremellow\Tasks\Services;

use Andremellow\Tasks\Models\Task;
use Andremellow\Tasks\Models\TaskChange;
use Illuminate\Contracts\Auth\Authenticatable;

class RecordTaskChange
{
    public function handle(Task $task, ?Authenticatable $actor, string $operation, array $before = [], array $after = []): TaskChange
    {
        return $task->changes()->create(['changed_by' => $actor?->id, 'operation' => $operation, 'changed_attributes' => array_values(array_unique([...array_keys($before), ...array_keys($after)])), 'before' => $before ?: null, 'after' => $after ?: null]);
    }
}
