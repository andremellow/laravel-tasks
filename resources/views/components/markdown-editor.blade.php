@props(['model', 'value' => '', 'label' => __('Description'), 'rows' => 12])

<div class="tasks-description-editor">
    <flux:editor
        wire:model.live.debounce.500ms="{{ $model }}"
        :label="$label"
        :description="__('Format the task description visually. Images, screen recordings, and files are uploaded separately as task media.')"
        toolbar="heading | bold italic underline strike | bullet ordered blockquote code link | align | undo redo"
        class="min-h-80"
    />

    <p class="mt-2 text-xs text-[#737a72]">
        {{ __('Formatting is saved as Markdown automatically.') }}
    </p>
</div>
