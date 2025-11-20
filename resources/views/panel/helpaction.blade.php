<div>
    <x-filament::dropdown placement="bottom-end">
        <x-slot name="trigger">
            <button
                type="button"
                class="flex items-center gap-2 px-3 py-2 text-sm border border-gray-500 rounded-lg hover:border-gray-400 duration-300 text-black dark:text-white"
            >
                <x-filament::icon :icon="$icon" class="h-6 w-6 text-current" />
                <span class="flex-1">Help</span>
            </button>
        </x-slot>

        <x-filament::dropdown.list>
            @forelse($pages as $page)
                <x-filament::dropdown.list.item
                    x-on:click.prevent="$wire.openBySlug('{{ $page['slug'] }}')"
                >
                    {{ $page['title'] ?? $page['slug'] }}
                </x-filament::dropdown.list.item>
            @empty
                <x-filament::dropdown.list.item disabled>
                    Geen help-pagina's ingesteld
                </x-filament::dropdown.list.item>
            @endforelse
        </x-filament::dropdown.list>
    </x-filament::dropdown>

    {{-- Modals voor Filament actions --}}
    <x-filament-actions::modals />
</div>
