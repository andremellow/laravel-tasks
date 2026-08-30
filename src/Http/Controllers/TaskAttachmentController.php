<?php

namespace Andremellow\Tasks\Http\Controllers;

use Andremellow\Tasks\Models\Task;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskAttachmentController
{
    public function show(Task $task, Media $media): StreamedResponse
    {
        return $this->response($task, $media, false);
    }

    public function download(Task $task, Media $media): StreamedResponse
    {
        return $this->response($task, $media, true);
    }

    private function response(Task $task, Media $media, bool $download): StreamedResponse
    {
        Gate::authorize('view', $task);
        abort_unless($media->model_type === Task::class && (int) $media->model_id === $task->getKey() && $media->collection_name === 'task-attachments', 404);

        $headers = ['Content-Type' => $media->mime_type ?: 'application/octet-stream'];
        $disposition = $download ? 'attachment' : 'inline';

        return Storage::disk($media->disk)->response(
            $media->getPathRelativeToRoot(),
            $media->file_name,
            $headers,
            $disposition,
        );
    }
}
