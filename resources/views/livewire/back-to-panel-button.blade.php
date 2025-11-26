@if (! $hasMultiplePanels && $singlePanel)
    <a
        href="{{ $singlePanel['url'] }}"
        class="flex items-center gap-2 pl-3 py-2 border border-gray-500 rounded-lg hover:border-gray-400 duration-300 mb-3 ml-6 mr-9"
    >
        <x-filament::icon
            icon="heroicon-o-arrow-uturn-left"
            class="h-6 w-6"
        />
        <span>Terug naar panel</span>
    </a>
@else
    <div class="mb-3 ml-6 mr-9">
        <x-filament::dropdown placement="top-start">
            <x-slot name="trigger">
                <button
                    type="button"
                    class="flex items-center justify-between gap-2 w-full pl-3 pr-2 py-2 border border-gray-500 rounded-lg hover:border-gray-400 duration-300"
                >
                    <div class="flex items-center gap-2">
                        <x-filament::icon
                            icon="heroicon-o-arrow-uturn-left"
                            class="h-6 w-6"
                        />
                        <span>Terug naar panel</span>
                    </div>
                    <x-filament::icon
                        icon="heroicon-o-chevron-down"
                        class="h-4 w-4"
                    />
                </button>
            </x-slot>

            <x-filament::dropdown.list>
                @foreach ($panels as $panel)
                    <x-filament::dropdown.list.item tag="a" href="{{ $panel['url'] }}">
                        {{ $panel['label'] }}
                        <!---If label is null show the id-->
                        @if($panel['label'] == null)
                            {{ $panel['id'] }}
                        @endif
                    </x-filament::dropdown.list.item>
                @endforeach
            </x-filament::dropdown.list>
        </x-filament::dropdown>
    </div>
@endif
