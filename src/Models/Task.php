<?php

namespace Andremellow\Tasks\Models;

use Andremellow\Tasks\Database\Factories\TaskFactory;
use Andremellow\Tasks\Enums\TaskPriority;
use Andremellow\Tasks\Enums\TaskStatus;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['task_type_id', 'created_by', 'assignee_id', 'title', 'description', 'priority', 'status', 'board_position', 'due_date', 'completed_at'])]
class Task extends Model implements HasMedia
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected function casts(): array
    {
        return ['priority' => TaskPriority::class, 'status' => TaskStatus::class, 'due_date' => 'date', 'completed_at' => 'datetime'];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TaskType::class, 'task_type_id')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('tasks.user_model'), 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(config('tasks.user_model'), 'assignee_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(TaskTag::class, 'task_tag')->withTimestamps()->withTrashed();
    }

    public function statusChanges(): HasMany
    {
        return $this->hasMany(TaskStatusChange::class);
    }

    public function changes(): HasMany
    {
        return $this->hasMany(TaskChange::class);
    }

    public function renderedDescription(): string
    {
        return Str::markdown($this->description ?? '', ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }

    public function scopeOverdue($query, ?Authenticatable $viewer = null)
    {
        $timezone = $viewer?->profile?->timezone ?? config('tasks.timezone', config('app.timezone', 'UTC'));

        return $query->where('status', '!=', TaskStatus::Done->value)->whereDate('due_date', '<', Carbon::now($timezone)->toDateString());
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('task-attachments')->useDisk((string) (config('tasks.media_disk') ?: config('filesystems.default')));
    }
}
