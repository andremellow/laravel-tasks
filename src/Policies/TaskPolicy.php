<?php

namespace Andremellow\Tasks\Policies;

use Andremellow\Tasks\Models\Task;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

class TaskPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return $this->access($user);
    }

    public function view(Authenticatable $user, Task $task): bool
    {
        return $this->access($user);
    }

    public function create(Authenticatable $user): bool
    {
        return $this->access($user);
    }

    public function update(Authenticatable $user, Task $task): bool
    {
        return $this->access($user);
    }

    public function delete(Authenticatable $user, Task $task): bool
    {
        return $this->access($user);
    }

    public function restore(Authenticatable $user, Task $task): bool
    {
        return $this->access($user);
    }

    public function assign(Authenticatable $user, Task $task): bool
    {
        return $this->access($user);
    }

    public function changeStatus(Authenticatable $user, Task $task): bool
    {
        return $this->access($user);
    }

    public function manageAttachments(Authenticatable $user, Task $task): bool
    {
        return $this->access($user);
    }

    public function comment(Authenticatable $user, Task $task): bool
    {
        return $this->access($user);
    }

    private function access(Authenticatable $user): bool
    {
        return Gate::forUser($user)->allows((string) config('tasks.ability', 'tasks.access'));
    }
}
