<?php

namespace Andremellow\Tasks\Models;

use Andremellow\Tasks\Database\Factories\TaskTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'description', 'color', 'sort_order'])]
class TaskType extends Model
{
    /** @use HasFactory<TaskTypeFactory> */
    use HasFactory, SoftDeletes;

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }
}
