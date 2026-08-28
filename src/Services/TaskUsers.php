<?php

namespace Andremellow\Tasks\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;

class TaskUsers
{
    public function model(): string
    {
        $model = config('tasks.user_model');
        throw_if(! is_string($model) || $model === '', \LogicException::class, 'Configure tasks.user_model with the host authenticatable model.');

        return $model;
    }

    public function query(): Builder
    {
        return ($this->model())::query();
    }

    public function find(int|string|null $id): ?Authenticatable
    {
        return $id === null ? null : $this->query()->find($id);
    }

    public function eligible(?Authenticatable $user): bool
    {
        $resolver = config('tasks.assignee_resolver');

        return $user !== null && (! is_callable($resolver) || (bool) $resolver($user));
    }
}
