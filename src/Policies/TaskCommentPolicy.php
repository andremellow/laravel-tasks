<?php

namespace Andremellow\Tasks\Policies;

use Andremellow\Tasks\Models\TaskComment;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

class TaskCommentPolicy
{
    public function delete(Authenticatable $user, TaskComment $comment): bool
    {
        return (string) $comment->author_id === (string) $user->getAuthIdentifier()
            && Gate::forUser($user)->allows('comment', $comment->task);
    }
}
