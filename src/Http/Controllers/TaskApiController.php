<?php

namespace Andremellow\Tasks\Http\Controllers;

use Andremellow\Tasks\Actions\AssignTask;
use Andremellow\Tasks\Actions\CreateTask;
use Andremellow\Tasks\Actions\DeleteTask;
use Andremellow\Tasks\Actions\MoveTask;
use Andremellow\Tasks\Actions\RestoreTask;
use Andremellow\Tasks\Actions\UpdateTask;
use Andremellow\Tasks\Enums\TaskStatus;
use Andremellow\Tasks\Http\Resources\TaskResource;
use Andremellow\Tasks\Models\Task;
use Andremellow\Tasks\Services\TaskUsers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class TaskApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Task::class);
        $tasks = Task::query()->with(['type', 'tags'])->when($request->string('status')->isNotEmpty(), fn ($q) => $q->where('status', $request->string('status')))->latest('updated_at')->paginate(min(100, max(1, $request->integer('per_page', 25))));

        return TaskResource::collection($tasks);
    }

    public function store(Request $request, CreateTask $action): TaskResource
    {
        return new TaskResource($action->handle($request->user(), $request->all()));
    }

    public function show(Task $task): TaskResource
    {
        Gate::authorize('view', $task);

        return new TaskResource($task->load(['type', 'tags']));
    }

    public function update(Request $request, Task $task, UpdateTask $action): TaskResource
    {
        return new TaskResource($action->handle($request->user(), $task, $request->all()));
    }

    public function assign(Request $request, Task $task, AssignTask $action, TaskUsers $users): TaskResource
    {
        $request->validate(['assignee_id' => ['nullable']]);

        return new TaskResource($action->handle($request->user(), $task, $users->find($request->input('assignee_id'))));
    }

    public function move(Request $request, Task $task, MoveTask $action): TaskResource
    {
        $data = $request->validate(['status' => ['required', 'string'], 'position' => ['required', 'integer', 'min:1']]);

        return new TaskResource($action->handle($request->user(), $task, TaskStatus::from($data['status']), $data['position']));
    }

    public function destroy(Request $request, Task $task, DeleteTask $action): Response
    {
        $action->handle($request->user(), $task);

        return response()->noContent();
    }

    public function restore(Request $request, int $task, RestoreTask $action): TaskResource
    {
        return new TaskResource($action->handle($request->user(), Task::onlyTrashed()->findOrFail($task)));
    }
}
