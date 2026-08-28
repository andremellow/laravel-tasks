<?php

namespace Andremellow\Tasks\Models;

use Andremellow\Tasks\Database\Factories\TaskTagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'color'])]
class TaskTag extends Model
{
    /** @use HasFactory<TaskTagFactory> */
    use HasFactory, SoftDeletes;

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_tag')->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }
}
