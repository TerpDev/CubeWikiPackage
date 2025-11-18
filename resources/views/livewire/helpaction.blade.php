<div>
    <x-filament::dropdown placement="bottom-end">
        <x-slot name="trigger">
            <x-filament::button icon="{{ $icon }}">
                Help
            </x-filament::button>
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

    <x-filament-actions::modals />
</div>
