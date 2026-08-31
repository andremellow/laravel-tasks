<?php

namespace Andremellow\Tasks\Actions;

use Andremellow\Tasks\Models\TaskComment;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

class DeleteTaskComment
{
    public function handle(Authenticatable $actor, TaskComment $comment): void
    {
        Gate::forUser($actor)->authorize('delete', $comment);

        $comment->delete();
    }
}
