<button
    type="button"
    wire:click="mountAction('create')"
    class="flex items-center gap-2 pl-3 py-2 border border-gray-500 rounded-lg hover:border-gray-400 duration-300 mb-3 ml-6 mr-9"
>
    <x-filament::icon
        :icon="$icon"
        class="h-6 w-6"
    />
    <span>{{ $label }}</span>
</button>
