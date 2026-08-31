<?php

namespace Andremellow\Tasks\Actions;

use Andremellow\Tasks\Models\Task;
use Andremellow\Tasks\Models\TaskComment;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class AddTaskComment
{
    public function handle(Authenticatable $actor, Task $task, string $body): TaskComment
    {
        Gate::forUser($actor)->authorize('comment', $task);
        $body = trim($body);

        $data = Validator::make(
            ['body' => $body],
            ['body' => ['required', 'string', 'max:'.config('tasks.comment_max', 5000)]],
        )->validate();

        return $task->comments()->create([
            'author_id' => $actor->getAuthIdentifier(),
            'body' => trim($data['body']),
        ])->load('author');
    }
}
