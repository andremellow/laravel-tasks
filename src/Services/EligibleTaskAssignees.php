<?php

namespace Andremellow\Tasks\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;

class EligibleTaskAssignees
{
    public function __construct(private TaskUsers $users) {}

    public function query(?string $search = null): Builder
    {
        $column = (string) config('tasks.user_name_column', 'name');

        return $this->users->query()
            ->when($search, fn (Builder $query) => $query->whereLike($column, '%'.trim($search).'%', caseSensitive: false))
            ->orderBy($column);
    }

    public function eligible(?Authenticatable $user): bool
    {
        return $this->users->eligible($user);
    }
}
