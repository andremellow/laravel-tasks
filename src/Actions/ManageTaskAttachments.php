<?php

namespace Andremellow\Tasks\Actions;

use Andremellow\Tasks\Models\Task;
use Andremellow\Tasks\Services\RecordTaskChange;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ManageTaskAttachments
{
    public function __construct(private RecordTaskChange $audit) {}

    public function add(Authenticatable $actor, Task $task, UploadedFile $file): Media
    {
        Gate::forUser($actor)->authorize('manageAttachments', $task);
        $category = $this->category($file);
        $mimes = config("tasks.{$category}_mimes", config('tasks.attachment_mimes'));
        $max = (int) config("tasks.{$category}_max_kb", config('tasks.attachment_max_kb', 10240));

        Validator::make(
            ['file' => $file],
            ['file' => ['required', File::types($mimes)->max($max)]],
        )->validate();

        $path = trim((string) config('tasks.media_path', 'tasks'), '/') ?: 'tasks';
        $media = $task->addMedia($file)
            ->usingName($file->getClientOriginalName())
            ->withCustomProperties([
                'task_media_category' => $category,
                'task_media_path' => $path,
            ])
            ->toMediaCollection('task-attachments');
        $this->audit->handle($task, $actor, 'attachment_added', [], ['media_id' => $media->id, 'name' => $media->name, 'mime_type' => $media->mime_type, 'size' => $media->size, 'category' => $category]);

        return $media;
    }

    private function category(UploadedFile $file): string
    {
        $mime = strtolower((string) $file->getMimeType());

        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'video/') => 'video',
            default => 'attachment',
        };
    }

    public function remove(Authenticatable $actor, Task $task, Media $media): void
    {
        Gate::forUser($actor)->authorize('manageAttachments', $task);
        abort_unless($media->model_type === Task::class && (int) $media->model_id === $task->id, 404);
        $before = ['media_id' => $media->id, 'name' => $media->name, 'mime_type' => $media->mime_type, 'size' => $media->size];
        $media->delete();
        $this->audit->handle($task, $actor, 'attachment_removed', $before, []);
    }

    /** @param list<int> $mediaIds */
    public function reorder(Authenticatable $actor, Task $task, array $mediaIds): void
    {
        Gate::forUser($actor)->authorize('manageAttachments', $task);
        $current = $task->getMedia('task-attachments')->pluck('id')->all();
        abort_unless(collect($current)->sort()->values()->all() === collect($mediaIds)->map(fn ($id) => (int) $id)->sort()->values()->all(), 422);
        Media::setNewOrder($mediaIds);
        $this->audit->handle($task, $actor, 'attachment_reordered', ['media_ids' => $current], ['media_ids' => array_values($mediaIds)]);
    }
}
