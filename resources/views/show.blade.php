<x-dynamic-component :component="config('tasks.layout', 'tasks::layouts.standalone')">
    <livewire:tasks::tasks.show :task="$task" />
</x-dynamic-component>
