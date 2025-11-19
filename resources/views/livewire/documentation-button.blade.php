<div>
    <button
        type="button"
        wire:click="mountAction('create')"
        class="flex items-center gap-x-2 px-3 py-2 border border-gray-500 rounded-lg hover:border-gray-400 duration-300 ml-3 mb-3"
    >
        <x-filament::icon
            :icon="$icon"
            class="h-6 w-6"
        />
        <span class="flex-1">{{ $label }}</span>
    </button>
</div>
