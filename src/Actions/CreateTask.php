<?php

namespace Andremellow\Tasks\Actions;

use Andremellow\Tasks\Enums\TaskPriority;
use Andremellow\Tasks\Enums\TaskStatus;
use Andremellow\Tasks\Models\Task;
use Andremellow\Tasks\Services\EligibleTaskAssignees;
use Andremellow\Tasks\Services\RecordTaskChange;
use Andremellow\Tasks\Services\TaskUsers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateTask
{
    public function __construct(private EligibleTaskAssignees $assignees, private RecordTaskChange $audit, private TaskUsers $users) {}

    public function handle(Authenticatable $actor, array $input): Task
    {
        Gate::forUser($actor)->authorize('create', Task::class);
        $data = Validator::make($input, ['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:'.config('tasks.description_max', 100000)], 'task_type_id' => ['required', Rule::exists('task_types', 'id')->whereNull('deleted_at')], 'priority' => ['required', Rule::enum(TaskPriority::class)], 'status' => ['required', Rule::in([TaskStatus::Backlog->value, TaskStatus::ToDo->value])], 'assignee_id' => ['nullable', 'integer'], 'due_date' => ['nullable', 'date'], 'tag_ids' => ['array'], 'tag_ids.*' => [Rule::exists('task_tags', 'id')->whereNull('deleted_at')]])->validate();
        if (isset($data['assignee_id'])) {
            Gate::forUser($actor)->authorize(config('tasks.ability', 'tasks.access'));
        }
        if (isset($data['assignee_id']) && ! $this->assignees->eligible($this->users->find($data['assignee_id']))) {
            abort(422, __('The selected assignee is not eligible.'));
        }

        return DB::transaction(function () use ($actor, $data): Task {
            $tagIds = $data['tag_ids'] ?? [];
            unset($data['tag_ids']);
            $position = (int) Task::withTrashed()->where('status', $data['status'])->max('board_position') + 1;
            $task = Task::create([...$data, 'created_by' => $actor->id, 'board_position' => $position]);
            $task->tags()->sync($tagIds);
            $task->statusChanges()->create(['changed_by' => $actor->id, 'from_status' => null, 'to_status' => $task->status->value]);
            $this->audit->handle($task, $actor, 'created', [], $task->only(['title', 'description', 'task_type_id', 'priority', 'status', 'assignee_id', 'due_date']));

            return $task->load(['type', 'tags', 'assignee']);
        });
    }
}
