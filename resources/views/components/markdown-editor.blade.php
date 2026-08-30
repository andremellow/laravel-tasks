@props(['model', 'value' => '', 'label' => __('Description'), 'rows' => 12, 'compact' => false])

<div class="tasks-description-editor">
    <flux:editor
        wire:model.live.debounce.500ms="{{ $model }}"
        :label="$label"
        :description="$compact ? __('Format your comment visually.') : __('Format the task description visually. Images, screen recordings, and files are uploaded separately as task media.')"
        toolbar="{{ $compact ? 'bold italic underline strike | bullet ordered blockquote code link | undo redo' : 'heading | bold italic underline strike | bullet ordered blockquote code link | align | undo redo' }}"
        @class(['min-h-36' => $compact, 'min-h-80' => ! $compact])
    />

    <p class="mt-2 text-xs text-[#737a72]">
        {{ __('Formatting is saved as Markdown automatically.') }}
    </p>
</div>
