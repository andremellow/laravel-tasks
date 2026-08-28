<?php

namespace Andremellow\Tasks\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority->value,
            'status' => $this->status->value,
            'board_position' => $this->board_position,
            'due_date' => $this->due_date?->toDateString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'created_by' => $this->created_by,
            'assignee_id' => $this->assignee_id,
            'type' => $this->whenLoaded('type'),
            'tags' => $this->whenLoaded('tags'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
