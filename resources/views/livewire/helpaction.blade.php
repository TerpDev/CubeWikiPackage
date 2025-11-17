<div>
    <button
        type="button"
        wire:click="mountAction('create')"
        class="group flex items-center gap-x-2 border border-white
        rounded-lg px-3 py-2 text-sm font-bold
        transition duration-200
        hover:bg-gray-100 focus-visible:bg-gray-100 dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
    >

        <x-filament::icon
            :icon="$icon"
            class="h-6 w-6"
        />
        <span class="flex-1">{{ $label }}</span>
    </button>

    <x-filament-actions::modals slide-over/>
</div>
