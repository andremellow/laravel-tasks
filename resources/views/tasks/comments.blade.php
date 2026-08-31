<?php

use Andremellow\Tasks\Actions\AddTaskComment;
use Andremellow\Tasks\Actions\DeleteTaskComment;
use Andremellow\Tasks\Models\Task;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public Task $task;
    public string $newComment = '';

    public function mount(Task $task): void
    {
        Gate::authorize('view', $task);
        $this->task = $task;
    }

    public function hydrate(): void
    {
        Gate::authorize('view', $this->task->fresh());
    }

    #[Computed]
    public function comments()
    {
        return $this->task->comments()->with('author')->oldest()->get();
    }

    public function addComment(AddTaskComment $add): void
    {
        $this->newComment = trim($this->newComment);
        $this->validate(['newComment' => ['required', 'string', 'max:'.config('tasks.comment_max', 5000)]]);
        $add->handle(auth()->user(), $this->task, $this->newComment);
        $this->newComment = '';
        unset($this->comments);
    }

    public function deleteComment(int $id, DeleteTaskComment $delete): void
    {
        $comment = $this->task->comments()->findOrFail($id);
        $delete->handle(auth()->user(), $comment);
        unset($this->comments);
    }
};
?>

<section class="overflow-hidden rounded-[22px] border border-[#dde3e7] bg-white shadow-[0_12px_32px_-28px_rgba(20,28,34,.42)]">
    <header class="flex items-start gap-3 px-5 py-5 sm:px-6">
        <span class="grid size-10 shrink-0 place-items-center rounded-[14px] bg-[#e4f0f5] text-[#1c6b84]"><flux:icon.chat-bubble-left-right class="size-5" /></span>
        <div><h2 class="text-base font-bold text-[#262d33]">{{ __('Comments') }}</h2><p class="mt-1 text-sm text-[#5f6a71]">{{ __('Share progress, decisions, and what still needs attention.') }}</p></div>
    </header>

    <div class="max-h-[28rem] space-y-3 overflow-y-auto border-t border-[#edf1f3] px-5 py-5 sm:px-6" aria-live="polite">
        @forelse($this->comments as $comment)
            <article wire:key="task-comment-{{ $comment->id }}" class="flex items-start gap-3">
                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-[#e4f2f7] text-xs font-bold text-[#1c6b84]" aria-hidden="true">{{ str($comment->author?->name ?? '?')->substr(0, 1)->upper() }}</span>
                <div class="min-w-0 flex-1 rounded-2xl rounded-tl-md bg-[#f4f8fa] px-4 py-3">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                        <strong class="text-sm text-[#1f262b]">{{ $comment->author?->name ?? __('Deleted user') }}</strong>
                        <div class="flex items-center gap-2"><time datetime="{{ $comment->created_at->toIso8601String() }}" class="text-xs text-[#7b8790]">{{ $comment->created_at->diffForHumans() }}</time>@if((string) $comment->author_id === (string) auth()->id())<flux:button type="button" variant="ghost" size="sm" icon="trash" wire:click="deleteComment({{ $comment->id }})" wire:confirm="{{ __('Delete this comment?') }}" :aria-label="__('Delete comment')" class="!size-7 !p-0 !text-[#a23c3c]" />@endif</div>
                    </div>
                    <div class="prose prose-sm mt-2 max-w-none text-[#3f4a51] prose-a:text-[#1c6b84] prose-blockquote:border-[#b7d5df] prose-blockquote:text-[#53616a]">{!! $comment->renderedBody() !!}</div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-[#cfd9de] bg-[#f8fbfc] px-5 py-8 text-center">
                <p class="text-sm font-semibold text-[#44515a]">{{ __('No comments yet.') }}</p>
                <p class="mt-1 text-xs text-[#7b8790]">{{ __('Start the conversation with an update or question.') }}</p>
            </div>
        @endforelse
    </div>

    @can('comment', $task)
        <form wire:submit="addComment" class="border-t border-[#edf1f3] bg-[#f9fbfc] p-5 sm:p-6">
            <x-tasks::markdown-editor model="newComment" :label="__('Add a comment')" compact />
            @error('newComment')<p class="mt-2 text-sm font-semibold text-[#b42318]" role="alert">{{ $message }}</p>@enderror
            <div class="mt-3 flex justify-end">
                <flux:button type="submit" variant="primary" icon="paper-airplane" wire:loading.attr="disabled" wire:target="addComment">{{ __('Comment') }}</flux:button>
            </div>
        </form>
    @endcan
</section>
