<?php

namespace Andremellow\Tasks\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['task_id', 'changed_by', 'operation', 'changed_attributes', 'before', 'after'])]
class TaskChange extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['changed_attributes' => 'array', 'before' => 'array', 'after' => 'array'];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(config('tasks.user_model'), 'changed_by');
    }

    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }
}
