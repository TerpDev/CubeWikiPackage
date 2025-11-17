<div>
    <button
        type="button"
        wire:click="mountAction('create')"
        class="group flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium outline-none transition duration-75 hover:bg-gray-100 focus-visible:bg-gray-100 dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
    >
        <x-filament::icon
            :icon="$icon"
            class="h-6 w-6"
        />
        <span class="flex-1">{{ $label }}</span>
    </button>

    {{-- This ensures the modal is rendered at body level, centered --}}
    <x-filament-actions::modals slide-over/>
</div>
