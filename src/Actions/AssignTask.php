<?php

namespace Andremellow\Tasks\Actions;

use Andremellow\Tasks\Models\Task;
use Andremellow\Tasks\Services\EligibleTaskAssignees;
use Andremellow\Tasks\Services\RecordTaskChange;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AssignTask
{
    public function __construct(private EligibleTaskAssignees $eligible, private RecordTaskChange $audit) {}

    public function handle(Authenticatable $actor, Task $task, ?Authenticatable $assignee): Task
    {
        Gate::forUser($actor)->authorize('assign', $task);
        if ($assignee !== null && ! $this->eligible->eligible($assignee)) {
            throw ValidationException::withMessages(['assignee_id' => __('The selected assignee is not eligible.')]);
        }

        return DB::transaction(function () use ($actor, $task, $assignee): Task {
            $before = $task->assignee_id;
            $task->update(['assignee_id' => $assignee?->id]);
            $this->audit->handle($task, $actor, 'assigned', ['assignee_id' => $before], ['assignee_id' => $assignee?->id]);

            return $task->fresh('assignee');
        });
    }
}
