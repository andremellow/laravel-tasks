<?php

namespace Andremellow\Tasks\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class TaskMediaPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $this->basePath($media).'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->basePath($media).'/conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->basePath($media).'/responsive-images/';
    }

    private function basePath(Media $media): string
    {
        $prefix = trim((string) $media->getCustomProperty('task_media_path'), '/');

        // Media created before configurable task paths keeps its original
        // Media Library location instead of becoming unreachable.
        if ($prefix === '') {
            return (string) $media->getKey();
        }

        return "{$prefix}/{$media->model_id}/{$media->getKey()}";
    }
}
