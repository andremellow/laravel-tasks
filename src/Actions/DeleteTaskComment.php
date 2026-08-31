<?php

namespace Andremellow\Tasks\Actions;

use Andremellow\Tasks\Models\TaskComment;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

class DeleteTaskComment
{
    public function handle(Authenticatable $actor, TaskComment $comment): void
    {
        if ((string) $comment->author_id !== (string) $actor->getAuthIdentifier()) {
            throw new AuthorizationException;
        }

        Gate::forUser($actor)->authorize('comment', $comment->task);

        $comment->delete();
    }
}
