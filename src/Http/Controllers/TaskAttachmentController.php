<?php

namespace Andremellow\Tasks\Http\Controllers;

use Andremellow\Tasks\Models\Task;
use Illuminate\Support\Facades\Gate;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TaskAttachmentController
{
    public function show(Task $task, Media $media): BinaryFileResponse
    {
        return $this->response($task, $media, false);
    }

    public function download(Task $task, Media $media): BinaryFileResponse
    {
        return $this->response($task, $media, true);
    }

    private function response(Task $task, Media $media, bool $download): BinaryFileResponse
    {
        Gate::authorize('view', $task);
        abort_unless($media->model_type === Task::class && (int) $media->model_id === $task->getKey() && $media->collection_name === 'task-attachments', 404);

        return $download ? response()->download($media->getPath(), $media->file_name) : response()->file($media->getPath());
    }
}
