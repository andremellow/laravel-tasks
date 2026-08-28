<?php

namespace Andremellow\Tasks\Actions;

use Andremellow\Tasks\Models\TaskTag;
use Andremellow\Tasks\Models\TaskType;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManageTaskClassification
{
    public function saveType(Authenticatable $actor, array $input, ?TaskType $type = null): TaskType
    {
        Gate::forUser($actor)->authorize(config('tasks.ability', 'tasks.access'));
        $data = $this->validate($input, 'task_types', $type?->id, true);
        if ($type === null) {
            $data['slug'] = $this->slug($data['name'], TaskType::class);
        } ($type ??= new TaskType)->fill($data)->save();

        return $type;
    }

    public function saveTag(Authenticatable $actor, array $input, ?TaskTag $tag = null): TaskTag
    {
        Gate::forUser($actor)->authorize(config('tasks.ability', 'tasks.access'));
        $data = $this->validate($input, 'task_tags', $tag?->id, false);
        if ($tag === null) {
            $data['slug'] = $this->slug($data['name'], TaskTag::class);
        } ($tag ??= new TaskTag)->fill($data)->save();

        return $tag;
    }

    public function archive(Authenticatable $actor, Model $classification): void
    {
        Gate::forUser($actor)->authorize($classification instanceof TaskType ? config('tasks.ability', 'tasks.access') : config('tasks.ability', 'tasks.access'));
        $classification->delete();
    }

    public function restore(Authenticatable $actor, Model $classification): void
    {
        Gate::forUser($actor)->authorize($classification instanceof TaskType ? config('tasks.ability', 'tasks.access') : config('tasks.ability', 'tasks.access'));
        $classification->restore();
    }

    private function validate(array $input, string $table, ?int $ignore, bool $type): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $input['name'] = $name;
        $duplicate = DB::table($table)->whereNull('deleted_at')->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->when($ignore, fn ($query) => $query->where('id', '!=', $ignore))->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['name' => __('The name has already been taken.')]);
        }

        return Validator::make($input, ['name' => ['required', 'string', 'max:100', Rule::unique($table, 'name')->ignore($ignore)], 'description' => [$type ? 'nullable' : 'exclude', 'string', 'max:500'], 'color' => ['nullable', 'string', 'max:20'], 'sort_order' => [$type ? 'nullable' : 'exclude', 'integer', 'min:0']])->validate();
    }

    private function slug(string $name, string $model): string
    {
        $base = Str::slug($name) ?: 'classification';
        $slug = $base;
        $i = 2;
        while ($model::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
