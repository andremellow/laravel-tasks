<?php

use Andremellow\Tasks\Actions\AssignTask;
use Andremellow\Tasks\Actions\DeleteTask;
use Andremellow\Tasks\Actions\ManageTaskAttachments;
use Andremellow\Tasks\Actions\MoveTask;
use Andremellow\Tasks\Actions\UpdateTask;
use Andremellow\Tasks\Enums\TaskPriority;
use Andremellow\Tasks\Enums\TaskStatus;
use Andremellow\Tasks\Models\Task;
use Andremellow\Tasks\Models\TaskTag;
use Andremellow\Tasks\Models\TaskType;
use Andremellow\Tasks\Services\EligibleTaskAssignees;
use Andremellow\Tasks\Services\TaskUsers;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

new class extends Component {
    use WithFileUploads;
    public Task $task; public string $title = ''; public string $description = ''; public string $priority = ''; public ?int $taskTypeId = null; public ?string $dueDate = null; public ?int $assigneeId = null; public string $status = '';
    /** @var list<int> */ public array $tagIds = []; public array $uploads = []; public bool $mediaUploadOpen = false; public string $assigneeSearch = ''; public bool $panelOpen = true; public bool $embedded = false;

    public function mount(Task $task, bool $embedded = false): void { Gate::authorize('view', $task); $this->task = $task; $this->embedded = $embedded; $this->fillState(); }
    public function hydrate(): void { Gate::authorize('view', $this->task->fresh()); }
    private function fillState(): void { $this->task->load(['tags', 'assignee', 'type']); $this->title = $this->task->title; $this->description = $this->task->description ?? ''; $this->priority = $this->task->priority->value; $this->taskTypeId = $this->task->task_type_id; $this->dueDate = $this->task->due_date?->toDateString(); $this->assigneeId = $this->task->assignee_id; $this->status = $this->task->status->value; $this->tagIds = $this->task->tags->pluck('id')->all(); }
    #[Computed] public function types() { return TaskType::query()->orderBy('sort_order')->get(); }
    #[Computed] public function tags() { return TaskTag::query()->orderBy('name')->get(); }
    #[Computed] public function assignees() { return app(EligibleTaskAssignees::class)->query($this->assigneeSearch)->limit(20)->get(); }
    #[Computed] public function history() { return $this->task->changes()->with('actor')->latest()->limit(100)->get(); }
    #[Computed] public function attachments() { return $this->task->getMedia('task-attachments'); }
    #[Computed] public function images() { return $this->attachments->filter(fn (Media $media) => $this->task->mediaCategory($media) === 'image'); }
    #[Computed] public function videos() { return $this->attachments->filter(fn (Media $media) => $this->task->mediaCategory($media) === 'video'); }
    #[Computed] public function documents() { return $this->attachments->filter(fn (Media $media) => $this->task->mediaCategory($media) === 'attachment'); }
    public function save(UpdateTask $update): void { $this->task = $update->handle(auth()->user(), $this->task, ['title' => $this->title, 'description' => $this->description ?: null, 'priority' => $this->priority, 'task_type_id' => $this->taskTypeId, 'due_date' => $this->dueDate, 'tag_ids' => $this->tagIds]); $this->fillState(); unset($this->history); $this->dispatch('task-editor-saved'); session()->flash('task-saved', true); }
    public function assign(AssignTask $assign): void { $this->task = $assign->handle(auth()->user(), $this->task, app(TaskUsers::class)->find($this->assigneeId)); unset($this->history); $this->dispatch('task-editor-saved'); }
    public function move(MoveTask $move): void { $this->task = $move->handle(auth()->user(), $this->task, TaskStatus::from($this->status), max(1, $this->task->board_position)); $this->fillState(); unset($this->history); $this->dispatch('task-editor-saved'); }
    public function upload(ManageTaskAttachments $manage): void { foreach ($this->uploads as $upload) $manage->add(auth()->user(), $this->task, $upload); $this->uploads = []; $this->mediaUploadOpen = false; unset($this->attachments, $this->images, $this->videos, $this->documents, $this->history); $this->dispatch('task-editor-saved'); }
    public function removeAttachment(int $id, ManageTaskAttachments $manage): void { $manage->remove(auth()->user(), $this->task, Media::findOrFail($id)); unset($this->attachments, $this->images, $this->videos, $this->documents, $this->history); $this->dispatch('task-editor-saved'); }
    public function delete(DeleteTask $delete): void { $delete->handle(auth()->user(), $this->task); $this->embedded ? $this->dispatch('task-editor-closed') : $this->redirectRoute(config('tasks.web.name').'index', navigate: true); }
    public function updatedPanelOpen(bool $open): void { if (! $open && $this->embedded) $this->dispatch('task-editor-closed'); }
};
?>

<div>
    @if($embedded)
        <flux:modal wire:model="panelOpen" variant="flyout" data-task-editor-panel class="!w-[calc(100vw-.5rem)] sm:!w-[min(92vw,52rem)] xl:!w-[min(76vw,72rem)] !max-w-none !border-[#dde2da] !bg-[#f8faf6] !p-4 sm:!p-6" style="color-scheme: light">
            @include('tasks::components.editor-content')
        </flux:modal>
    @else
        <div class="admin-page space-y-7">
            @include('tasks::components.editor-content')
        </div>
    @endif
</div>
