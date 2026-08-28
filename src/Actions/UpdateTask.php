<?php

namespace Andremellow\Tasks\Actions;

use Andremellow\Tasks\Enums\TaskPriority;
use Andremellow\Tasks\Models\Task;
use Andremellow\Tasks\Services\RecordTaskChange;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateTask
{
    public function __construct(private RecordTaskChange $audit) {}

    public function handle(Authenticatable $actor, Task $task, array $input): Task
    {
        Gate::forUser($actor)->authorize('update', $task);
        $data = Validator::make($input, ['title' => ['sometimes', 'required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:'.config('tasks.description_max', 100000)], 'task_type_id' => ['sometimes', Rule::exists('task_types', 'id')->whereNull('deleted_at')], 'priority' => ['sometimes', Rule::enum(TaskPriority::class)], 'due_date' => ['nullable', 'date'], 'tag_ids' => ['sometimes', 'array'], 'tag_ids.*' => [Rule::exists('task_tags', 'id')->whereNull('deleted_at')]])->validate();

        return DB::transaction(function () use ($actor, $task, $data): Task {
            $before = $task->only(['title', 'description', 'task_type_id', 'priority', 'due_date']);
            $oldTags = $task->tags()->pluck('task_tags.id')->all();
            $task->update(Arr::except($data, ['tag_ids']));
            if (array_key_exists('tag_ids', $data)) {
                $task->tags()->sync($data['tag_ids']);
            }
            $after = $task->fresh()->only(array_keys($before));
            $newTags = $task->tags()->pluck('task_tags.id')->all();
            if ($oldTags !== $newTags) {
                $before['tag_ids'] = $oldTags;
                $after['tag_ids'] = $newTags;
            }
            $this->audit->handle($task, $actor, 'updated', $before, $after);

            return $task->fresh(['type', 'tags', 'assignee']);
        });
    }
}
