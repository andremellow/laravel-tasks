<?php

use Andremellow\Tasks\Actions\CreateTask;
use Andremellow\Tasks\Actions\ManageTaskClassification;
use Andremellow\Tasks\Actions\MoveTask;
use Andremellow\Tasks\Actions\RestoreTask;
use Andremellow\Tasks\Enums\Permission;
use Andremellow\Tasks\Enums\TaskPriority;
use Andremellow\Tasks\Enums\TaskStatus;
use Andremellow\Tasks\Models\Task;
use Andremellow\Tasks\Models\TaskTag;
use Andremellow\Tasks\Models\TaskType;
use Andremellow\Tasks\Services\EligibleTaskAssignees;
use Andremellow\Tasks\Services\TaskUsers;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $view = 'backlog'; public string $search = ''; public string $status = ''; public string $priority = '';
    public ?int $typeId = null; public ?int $assigneeId = null; public ?string $dueFrom = null; public ?string $dueTo = null;
    public bool $overdue = false; public bool $unassigned = false; public bool $creating = false;
    /** @var list<int|string> */ public array $boardAssigneeIds = []; public bool $boardUnassigned = false;
    public ?int $editingTaskId = null;
    public string $title = ''; public string $description = ''; public string $newPriority = 'medium'; public string $newStatus = 'backlog'; public ?int $newTypeId = null; public ?int $newAssigneeId = null; public ?string $dueDate = null;
    public string $newTypeName = '';
    public string $newTagName = '';

    public function mount(?string $view = null): void { Gate::authorize(config('tasks.ability', 'tasks.access')); $requestedView = $view ?? request('view'); $this->view = in_array($requestedView, ['backlog', 'board', 'deleted', 'classifications'], true) ? $requestedView : 'backlog'; $this->authorizeCurrentView(); }
    public function hydrate(): void { Gate::authorize(config('tasks.ability', 'tasks.access')); $this->authorizeCurrentView(); }
    private function authorizeCurrentView(): void { if ($this->view === 'deleted') Gate::authorize(config('tasks.ability', 'tasks.access')); }
    public function updated($property): void { if (in_array($property, ['search', 'status', 'priority', 'typeId', 'assigneeId', 'dueFrom', 'dueTo', 'overdue', 'unassigned'], true)) $this->resetPage(); }

    #[Computed] public function types() { return TaskType::query()->orderBy('sort_order')->orderBy('name')->get(); }
    #[Computed] public function tags() { return TaskTag::query()->orderBy('name')->get(); }
    #[Computed] public function allTypes() { return TaskType::withTrashed()->orderBy('sort_order')->orderBy('name')->get(); }
    #[Computed] public function allTags() { return TaskTag::withTrashed()->orderBy('name')->get(); }
    #[Computed] public function assignees() { return app(EligibleTaskAssignees::class)->query()->limit(100)->get(); }
    #[Computed] public function boardAssignees() { return app(TaskUsers::class)->query()->whereIn('id', Task::query()->whereIn('status', [TaskStatus::ToDo, TaskStatus::InProgress, TaskStatus::InReview, TaskStatus::Done])->whereNotNull('assignee_id')->select('assignee_id'))->orderBy(config('tasks.user_name_column', 'name'))->get(); }
    #[Computed] public function tasks()
    {
        $this->authorizeCurrentView();
        $query = $this->view === 'deleted' ? Task::onlyTrashed() : Task::query();
        return $query->with(['type', 'assignee', 'tags'])->withCount(['media', 'comments'])
            ->when(trim($this->search) !== '', fn ($q) => $q->where(fn ($inner) => $inner->whereLike('title', '%'.trim($this->search).'%', caseSensitive: false)->orWhereLike('description', '%'.trim($this->search).'%', caseSensitive: false)))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))->when($this->priority !== '', fn ($q) => $q->where('priority', $this->priority))
            ->when($this->typeId, fn ($q) => $q->where('task_type_id', $this->typeId))->when($this->assigneeId, fn ($q) => $q->where('assignee_id', $this->assigneeId))
            ->when($this->dueFrom, fn ($q) => $q->whereDate('due_date', '>=', $this->dueFrom))->when($this->dueTo, fn ($q) => $q->whereDate('due_date', '<=', $this->dueTo))
            ->when($this->overdue, fn ($q) => $q->overdue(auth()->user()))->when($this->unassigned, fn ($q) => $q->whereNull('assignee_id'))
            ->latest('updated_at')->paginate(25);
    }
    #[Computed] public function editingTask(): ?Task { return $this->editingTaskId ? Task::findOrFail($this->editingTaskId) : null; }
    #[Computed] public function board()
    {
        $statuses = [TaskStatus::ToDo, TaskStatus::InProgress, TaskStatus::InReview, TaskStatus::Done];
        return collect($statuses)->mapWithKeys(fn ($status) => [$status->value => Task::query()->where('status', $status)->with(['type', 'assignee', 'tags'])->withCount(['media', 'comments'])
            ->when($this->boardAssigneeIds !== [] || $this->boardUnassigned, function ($query): void {
                $query->where(function ($assignees): void {
                    if ($this->boardAssigneeIds !== []) $assignees->whereIn('assignee_id', $this->boardAssigneeIds);
                    if ($this->boardUnassigned) $this->boardAssigneeIds !== [] ? $assignees->orWhereNull('assignee_id') : $assignees->whereNull('assignee_id');
                });
            })
            ->when(
                $status === TaskStatus::Done,
                fn ($query) => $query->orderByDesc('completed_at')->orderByDesc('id')->limit((int) config('tasks.board.done_limit', 100)),
                fn ($query) => $query->orderBy('board_position'),
            )->get()]);
    }
    public function clearBoardAssignees(): void { $this->boardAssigneeIds = []; $this->boardUnassigned = false; unset($this->board); }
    public function openTask(int $id): void { $task = Task::findOrFail($id); Gate::authorize('view', $task); $this->editingTaskId = $task->id; unset($this->editingTask); }
    #[On('task-editor-saved')] public function refreshTaskLists(): void { unset($this->tasks, $this->board, $this->boardAssignees); }
    #[On('task-editor-closed')] public function closeTaskEditor(): void { $this->editingTaskId = null; unset($this->editingTask, $this->tasks, $this->board, $this->boardAssignees); }
    public function create(CreateTask $create): void { $create->handle(auth()->user(), ['title' => $this->title, 'description' => $this->description ?: null, 'task_type_id' => $this->newTypeId, 'priority' => $this->newPriority, 'status' => $this->newStatus, 'assignee_id' => $this->newAssigneeId, 'due_date' => $this->dueDate]); $this->reset('creating', 'title', 'description', 'newTypeId', 'newAssigneeId', 'dueDate'); $this->newPriority = 'medium'; $this->newStatus = 'backlog'; unset($this->tasks, $this->board, $this->boardAssignees); }
    public function restore(int $id, RestoreTask $restore): void { $restore->handle(auth()->user(), Task::onlyTrashed()->findOrFail($id)); unset($this->tasks); session()->flash('task-restored', true); }
    public function move(int $id, string $status, int $position, MoveTask $move): void { $move->handle(auth()->user(), Task::findOrFail($id), TaskStatus::from($status), $position); unset($this->board); }
    public function createType(ManageTaskClassification $manage): void { $manage->saveType(auth()->user(), ['name' => $this->newTypeName]); $this->newTypeName = ''; unset($this->types, $this->allTypes); }
    public function createTag(ManageTaskClassification $manage): void { $manage->saveTag(auth()->user(), ['name' => $this->newTagName]); $this->newTagName = ''; unset($this->tags, $this->allTags); }
    public function archiveType(int $id, ManageTaskClassification $manage): void { $manage->archive(auth()->user(), TaskType::findOrFail($id)); unset($this->types, $this->allTypes); }
    public function restoreType(int $id, ManageTaskClassification $manage): void { $manage->restore(auth()->user(), TaskType::onlyTrashed()->findOrFail($id)); unset($this->types, $this->allTypes); }
    public function archiveTag(int $id, ManageTaskClassification $manage): void { $manage->archive(auth()->user(), TaskTag::findOrFail($id)); unset($this->tags, $this->allTags); }
    public function restoreTag(int $id, ManageTaskClassification $manage): void { $manage->restore(auth()->user(), TaskTag::onlyTrashed()->findOrFail($id)); unset($this->tags, $this->allTags); }
};
?>

<div class="admin-page space-y-7" data-task-view="{{ $view }}">
    <section class="admin-hero flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between"><div><p class="admin-kicker">{{ __('Operations') }}</p><h1 class="mt-2 text-3xl font-bold">{{ __('Task management') }}</h1><p class="mt-2 text-sm text-[#626a61]">{{ __('Capture, prioritize, and move team work from backlog to completion.') }}</p></div>@can(config('tasks.ability', 'tasks.access'))<flux:button wire:click="$toggle('creating')" variant="primary" icon="plus">{{ __('New task') }}</flux:button>@endcan</section>
    <nav class="flex flex-wrap gap-2" aria-label="{{ __('Task views') }}"><flux:button href="{{ route(config('tasks.web.name').'index', ['view' => 'backlog']) }}" wire:navigate>{{ __('Backlog') }}</flux:button><flux:button href="{{ route(config('tasks.web.name').'index', ['view' => 'board']) }}" wire:navigate>{{ __('Kanban') }}</flux:button>@can(config('tasks.ability', 'tasks.access'))<flux:button href="{{ route(config('tasks.web.name').'index', ['view' => 'deleted']) }}" wire:navigate>{{ __('Deleted') }}</flux:button>@endcan @if(auth()->user()->can(config('tasks.ability', 'tasks.access')) || auth()->user()->can(config('tasks.ability', 'tasks.access')))<flux:button href="{{ route(config('tasks.web.name').'index', ['view' => 'classifications']) }}" wire:navigate>{{ __('Classifications') }}</flux:button>@endif</nav>
    @if(session('task-restored'))<div role="status" class="rounded-xl bg-[#edf5e5] p-4 text-sm font-semibold text-[#398344]">{{ __('Task restored.') }}</div>@endif
    <flux:modal wire:model="creating" variant="flyout" class="!w-[calc(100vw-.5rem)] sm:!w-[min(92vw,48rem)] xl:!w-[min(68vw,64rem)] !max-w-none !border-[#dde2da] !bg-[#f8faf6] !p-4 sm:!p-7" style="color-scheme: light">
        <div class="mb-6 pr-10">
            <p class="admin-kicker">{{ __('Operations') }}</p>
            <flux:heading size="xl" class="mt-2">{{ __('New task') }}</flux:heading>
        </div>
        <form wire:submit="create" class="grid gap-5 md:grid-cols-2">
            <flux:input wire:model="title" :label="__('Title')" required/>
            <flux:select wire:model="newTypeId" :label="__('Type')" required><option value="">{{ __('Select a type') }}</option>@foreach($this->types as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</flux:select>
            <flux:select wire:model="newPriority" :label="__('Priority')">@foreach(TaskPriority::cases() as $value)<option value="{{ $value->value }}">{{ $value->label() }}</option>@endforeach</flux:select>
            <flux:select wire:model="newStatus" :label="__('Initial status')"><option value="backlog">{{ __('Backlog') }}</option><option value="to_do">{{ __('To Do') }}</option></flux:select>
            @can(config('tasks.ability', 'tasks.access'))<flux:select wire:model="newAssigneeId" :label="__('Assignee')"><option value="">{{ __('Unassigned') }}</option>@foreach($this->assignees as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</flux:select>@endcan
            <flux:input type="date" wire:model="dueDate" :label="__('Due date')"/>
            <div class="md:col-span-2"><x-tasks::markdown-editor model="description" :value="$description" rows="10"/></div>
            <div class="flex justify-end gap-2 md:col-span-2">
                <flux:button type="button" variant="ghost" wire:click="$set('creating', false)">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Create task') }}</flux:button>
            </div>
        </form>
    </flux:modal>
    @if($view === 'board')
        @php($boardFiltered = $boardAssigneeIds !== [] || $boardUnassigned)
        <section class="relative rounded-[22px] border border-[#e1e4dd] bg-white p-4 shadow-[0_10px_28px_rgba(32,36,32,.05)] sm:p-5" aria-label="{{ __('Assignees') }}">
            <div class="flex min-h-8 items-center pr-10">
                <div class="flex min-w-0 flex-wrap items-center gap-x-5 gap-y-3">
                    <span class="text-sm font-bold text-[#292e29]">{{ __('Assignees') }}</span>
                    @foreach($this->boardAssignees as $user)
                        <flux:checkbox wire:model.live="boardAssigneeIds" value="{{ $user->id }}" :label="$user->name" />
                    @endforeach
                    <flux:checkbox wire:model.live="boardUnassigned" :label="__('Unassigned')" />
                </div>
            </div>
            @if($boardAssigneeIds !== [] || $boardUnassigned)
                <flux:button type="button" size="sm" variant="ghost" icon="x-mark" wire:click="clearBoardAssignees" aria-label="{{ __('Clear') }}" title="{{ __('Clear') }}" class="!absolute right-3 top-1/2 !size-8 -translate-y-1/2 sm:right-4" />
            @endif
        </section>
        @if($boardFiltered)<p class="text-xs font-semibold text-[#737a72]">{{ __('Clear assignee filters to reorder tasks.') }}</p>@endif
        <section
            class="grid gap-4 md:grid-cols-2 xl:grid-cols-4"
            aria-label="{{ __('Kanban board') }}"
            x-data="{ dropPosition(event) { const cards = [...event.currentTarget.querySelectorAll('[data-task-id]')]; const index = cards.findIndex((card) => event.clientY < card.getBoundingClientRect().top + card.getBoundingClientRect().height / 2); return index === -1 ? cards.length + 1 : index + 1 } }"
        >
            @foreach([TaskStatus::ToDo, TaskStatus::InProgress, TaskStatus::InReview, TaskStatus::Done] as $lane)
                @php($laneTasks = $this->board[$lane->value])
                <div class="min-w-0 rounded-[22px] border border-[#dde3e7] bg-[#eef3f5] p-4 shadow-[0_12px_30px_-28px_rgba(20,28,34,.45)]" @unless($boardFiltered) x-on:dragover.prevent x-on:drop.prevent="$wire.move(parseInt($event.dataTransfer.getData('text/plain')), '{{ $lane->value }}', dropPosition($event))" @endunless>
                    <header class="mb-4 flex items-center justify-between gap-3 px-1">
                        <h2 class="font-bold text-[#292e29]">{{ $lane->label() }}</h2>
                        <span class="inline-flex min-w-7 items-center justify-center rounded-full border border-[#d7ded2] bg-white px-2 py-1 text-xs font-bold text-[#626a61]">{{ $laneTasks->count() }}</span>
                    </header>
                    <div class="min-h-36 space-y-3">
                        @forelse($laneTasks as $task)
                            <article
                                wire:key="board-task-{{ $task->id }}"
                                data-task-id="{{ $task->id }}"
                                class="rounded-2xl border border-[#dde3e7] bg-white p-4 shadow-[0_10px_24px_-20px_rgba(20,28,34,.5)] transition hover:-translate-y-0.5 hover:border-[#b9cbd2] hover:shadow-[0_14px_28px_-20px_rgba(20,28,34,.55)]"
                                @can('changeStatus', $task)
                                    @unless($boardFiltered)
                                    draggable="true"
                                    x-on:dragstart="$event.dataTransfer.setData('text/plain', '{{ $task->id }}')"
                                    @endunless
                                @endcan
                            >
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex rounded-full bg-[#edf5e5] px-2.5 py-1 text-[10px] font-bold uppercase tracking-[.08em] text-[#4f7841]">{{ $task->type->name }}</span>
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-[.08em]',
                                        'bg-[#eef1ed] text-[#626a61]' => $task->priority === TaskPriority::Low,
                                        'bg-[#fff3d6] text-[#80651b]' => $task->priority === TaskPriority::Medium,
                                        'bg-[#ffead6] text-[#9a5b21]' => $task->priority === TaskPriority::High,
                                        'bg-[#fde7e7] text-[#a63737]' => $task->priority === TaskPriority::Urgent,
                                    ])>{{ $task->priority->label() }}</span>
                                </div>

                                <button type="button" wire:click="openTask({{ $task->id }})" class="mt-3 block text-left text-sm font-bold leading-5 text-[#292e29] transition hover:text-[#4f7841]">{{ $task->title }}</button>

                                @if($task->tags->isNotEmpty())
                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        @foreach($task->tags as $tag)
                                            <span class="inline-flex rounded-full border border-[#dfe3dc] bg-[#f8faf6] px-2 py-0.5 text-[11px] font-semibold text-[#626a61]">{{ $tag->name }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                <footer class="mt-4 flex items-end justify-between gap-3 border-t border-[#edf0ea] pt-3">
                                    <div class="flex min-w-0 items-center gap-2">
                                        @if($task->assignee)
                                            <span class="grid size-7 shrink-0 place-items-center rounded-full bg-[#e9efe5] text-[10px] font-bold text-[#47683d]" aria-hidden="true">{{ str($task->assignee->name)->substr(0, 1)->upper() }}</span>
                                            <span class="truncate text-xs font-semibold text-[#626a61]">{{ $task->assignee->name }}</span>
                                        @else
                                            <span class="text-xs text-[#92988f]">{{ __('Unassigned') }}</span>
                                        @endif
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2 text-[11px] font-semibold text-[#737a72]">
                                        @if($task->due_date)
                                            <time datetime="{{ $task->due_date->toDateString() }}">{{ $task->due_date->translatedFormat('M j') }}</time>
                                        @endif
                                        @if($task->media_count)
                                            <span class="inline-flex items-center gap-1" aria-label="{{ __('Attachments') }}: {{ $task->media_count }}">
                                                <flux:icon.paper-clip class="size-3.5"/>
                                                {{ $task->media_count }}
                                            </span>
                                        @endif
                                    </div>
                                </footer>
                            </article>
                        @empty
                            <div class="grid min-h-36 place-items-center rounded-2xl border border-dashed border-[#cad5da] bg-white/60 px-4 py-8 text-center text-sm text-[#7b8790]">{{ __('No tasks in this column.') }}</div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </section>
    @elseif($view === 'classifications')
        <section class="grid gap-5 lg:grid-cols-2">
            <article class="overflow-hidden rounded-[22px] border border-[#e1e4dd] bg-white shadow-[0_16px_36px_rgba(32,36,32,.06)]">
                <header class="border-b border-[#edf0ea] p-5 sm:p-6">
                    <h2 class="text-base font-bold text-[#292e29]">{{ __('Task types') }}</h2>
                    @can(config('tasks.ability', 'tasks.access'))
                        <form wire:submit="createType" class="mt-4 grid items-end gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                            <flux:input wire:model="newTypeName" :label="__('New classification')" required/>
                            <flux:button type="submit" variant="primary" class="sm:mb-px">{{ __('Add type') }}</flux:button>
                        </form>
                    @endcan
                </header>
                <ul class="divide-y divide-[#edf0ea]">
                    @foreach($this->allTypes as $type)
                        <li wire:key="task-type-{{ $type->id }}" class="flex min-h-16 items-center justify-between gap-4 px-5 py-3 sm:px-6">
                            <span class="min-w-0 truncate text-sm font-semibold text-[#292e29]">
                                {{ $type->name }}
                                @if($type->trashed())<span class="ml-2 text-xs font-normal text-[#8b9189]">{{ __('archived') }}</span>@endif
                            </span>
                            @can(config('tasks.ability', 'tasks.access'))
                                @if($type->trashed())
                                    <flux:button size="sm" wire:click="restoreType({{ $type->id }})">{{ __('Restore') }}</flux:button>
                                @else
                                    <flux:button size="sm" wire:click="archiveType({{ $type->id }})">{{ __('Archive') }}</flux:button>
                                @endif
                            @endcan
                        </li>
                    @endforeach
                </ul>
            </article>

            <article class="overflow-hidden rounded-[22px] border border-[#e1e4dd] bg-white shadow-[0_16px_36px_rgba(32,36,32,.06)]">
                <header class="border-b border-[#edf0ea] p-5 sm:p-6">
                    <h2 class="text-base font-bold text-[#292e29]">{{ __('Task tags') }}</h2>
                    @can(config('tasks.ability', 'tasks.access'))
                        <form wire:submit="createTag" class="mt-4 grid items-end gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                            <flux:input wire:model="newTagName" :label="__('New classification')" required/>
                            <flux:button type="submit" variant="primary" class="sm:mb-px">{{ __('Add tag') }}</flux:button>
                        </form>
                    @endcan
                </header>
                <ul class="divide-y divide-[#edf0ea]">
                    @foreach($this->allTags as $tag)
                        <li wire:key="task-tag-{{ $tag->id }}" class="flex min-h-16 items-center justify-between gap-4 px-5 py-3 sm:px-6">
                            <span class="min-w-0 truncate text-sm font-semibold text-[#292e29]">
                                {{ $tag->name }}
                                @if($tag->trashed())<span class="ml-2 text-xs font-normal text-[#8b9189]">{{ __('archived') }}</span>@endif
                            </span>
                            @can(config('tasks.ability', 'tasks.access'))
                                @if($tag->trashed())
                                    <flux:button size="sm" wire:click="restoreTag({{ $tag->id }})">{{ __('Restore') }}</flux:button>
                                @else
                                    <flux:button size="sm" wire:click="archiveTag({{ $tag->id }})">{{ __('Archive') }}</flux:button>
                                @endif
                            @endcan
                        </li>
                    @endforeach
                </ul>
            </article>
        </section>
    @else
        <section class="saas-feature-card grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4">
            <flux:input wire:model.live.debounce.300ms="search" :label="__('Search tasks')"/>
            <flux:select wire:model.live="status" :label="__('Status')"><option value="">{{ __('All statuses') }}</option>@foreach(TaskStatus::cases() as $value)<option value="{{ $value->value }}">{{ $value->label() }}</option>@endforeach</flux:select>
            <flux:select wire:model.live="priority" :label="__('Priority')"><option value="">{{ __('All priorities') }}</option>@foreach(TaskPriority::cases() as $value)<option value="{{ $value->value }}">{{ $value->label() }}</option>@endforeach</flux:select>
            <flux:checkbox wire:model.live="overdue" :label="__('Overdue only')"/>
        </section>

        <section class="space-y-3" aria-label="{{ __('Tasks') }}">
            @forelse($this->tasks as $task)
                <article wire:key="task-list-{{ $task->id }}" class="group rounded-[20px] border border-[#dde3e7] bg-white p-5 shadow-[0_8px_24px_rgba(31,38,43,.05)] transition duration-200 hover:-translate-y-0.5 hover:border-[#bfd0d7] hover:shadow-[0_14px_32px_rgba(31,38,43,.08)] sm:p-6">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full bg-[#e4f2f7] px-2.5 py-1 text-[10px] font-bold uppercase tracking-[.09em] text-[#1c6b84]">{{ $task->type->name }}</span>
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-[.09em]',
                                    'bg-[#eef2f4] text-[#61707a]' => $task->priority === TaskPriority::Low,
                                    'bg-[#fff3d6] text-[#80651b]' => $task->priority === TaskPriority::Medium,
                                    'bg-[#ffead6] text-[#9a5b21]' => $task->priority === TaskPriority::High,
                                    'bg-[#fde7e7] text-[#a63737]' => $task->priority === TaskPriority::Urgent,
                                ])>{{ $task->priority->label() }}</span>
                                <span class="inline-flex rounded-full border border-[#dce4e8] bg-[#f8fafb] px-2.5 py-1 text-[10px] font-bold uppercase tracking-[.09em] text-[#53616a]">{{ $task->status->label() }}</span>
                            </div>

                            <button type="button" wire:click="openTask({{ $task->id }})" class="mt-3 block max-w-full text-left text-base font-bold leading-6 text-[#1f262b] transition group-hover:text-[#1c6b84]">{{ $task->title }}</button>
                            @if(filled($task->description))
                                <p class="mt-1 line-clamp-2 text-sm leading-6 text-[#68747c]">{{ str(strip_tags($task->description))->limit(180) }}</p>
                            @endif

                            <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-[#76828a]">
                                <span class="inline-flex items-center gap-1.5"><flux:icon.user class="size-3.5"/>{{ $task->assignee?->name ?? __('Unassigned') }}</span>
                                @if($task->due_date)<time datetime="{{ $task->due_date->toDateString() }}" class="inline-flex items-center gap-1.5"><flux:icon.calendar-days class="size-3.5"/>{{ $task->due_date->translatedFormat('M j, Y') }}</time>@endif
                                @if($task->comments_count)<span class="inline-flex items-center gap-1.5"><flux:icon.chat-bubble-left class="size-3.5"/>{{ trans_choice(':count comment|:count comments', $task->comments_count, ['count' => $task->comments_count]) }}</span>@endif
                                @if($task->media_count)<span class="inline-flex items-center gap-1.5"><flux:icon.paper-clip class="size-3.5"/>{{ $task->media_count }}</span>@endif
                            </div>
                        </div>

                        <div class="shrink-0">
                            @if($view === 'deleted')
                                <flux:button wire:click="restore({{ $task->id }})">{{ __('Restore') }}</flux:button>
                            @else
                                <flux:button type="button" wire:click="openTask({{ $task->id }})" icon-trailing="chevron-right">{{ __('Open task') }}</flux:button>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-[20px] border border-dashed border-[#cad6dc] bg-white px-6 py-12 text-center text-sm text-[#737f87]">{{ __('No tasks match these filters.') }}</div>
            @endforelse
        </section>
        <div>{{ $this->tasks->links() }}</div>
    @endif

    @if($this->editingTask)
        <livewire:tasks::tasks.show :task="$this->editingTask" :embedded="true" :key="'task-editor-'.$this->editingTask->id" />
    @endif
</div>
