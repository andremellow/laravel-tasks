<?php

namespace Andremellow\Tasks\Actions;

use Andremellow\Tasks\Enums\TaskStatus;
use Andremellow\Tasks\Models\Task;
use Andremellow\Tasks\Services\RecordTaskChange;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MoveTask
{
    public function __construct(private RecordTaskChange $audit) {}

    public function handle(Authenticatable $actor, Task $task, TaskStatus $status, int $position): Task
    {
        Gate::forUser($actor)->authorize('changeStatus', $task);

        return DB::transaction(function () use ($actor, $task, $status, $position): Task {
            $task = Task::query()->lockForUpdate()->findOrFail($task->id);
            $from = $task->status;
            $oldPosition = $task->board_position;

            $lockedTasks = Task::query()
                ->whereIn('status', collect([$from->value, $status->value])->unique())
                ->lockForUpdate()
                ->orderBy('board_position')
                ->orderBy('id')
                ->get(['id', 'status']);

            $destinationIds = $lockedTasks
                ->where('status', $status)
                ->pluck('id')
                ->reject(fn (int $id): bool => $id === $task->id)
                ->values();
            $position = min(max(1, $position), $destinationIds->count() + 1);
            $destinationIds->splice($position - 1, 0, [$task->id]);

            $task->update([
                'status' => $status,
                'board_position' => $position,
                'completed_at' => $status === TaskStatus::Done ? ($from === TaskStatus::Done ? $task->completed_at : now()) : null,
            ]);

            $destinationIds->each(fn (int $id, int $index) => Task::query()->whereKey($id)->update(['board_position' => $index + 1]));

            if ($from !== $status) {
                $lockedTasks
                    ->where('status', $from)
                    ->pluck('id')
                    ->reject(fn (int $id): bool => $id === $task->id)
                    ->values()
                    ->each(fn (int $id, int $index) => Task::query()->whereKey($id)->update(['board_position' => $index + 1]));
            }

            if ($from !== $status) {
                $task->statusChanges()->create(['changed_by' => $actor->id, 'from_status' => $from->value, 'to_status' => $status->value]);
            }
            $this->audit->handle($task, $actor, $from === $status ? 'reordered' : 'status_changed', ['status' => $from->value, 'board_position' => $oldPosition], ['status' => $status->value, 'board_position' => $position]);

            return $task->fresh();
        });
    }
}
