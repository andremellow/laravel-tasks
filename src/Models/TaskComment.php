<?php

namespace Andremellow\Tasks\Models;

use Andremellow\Tasks\Services\TaskRichTextRenderer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['task_id', 'author_id', 'body'])]
class TaskComment extends Model
{
    use SoftDeletes;

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(config('tasks.user_model'), 'author_id');
    }

    public function renderedBody(): string
    {
        return app(TaskRichTextRenderer::class)->render($this->body);
    }
}
