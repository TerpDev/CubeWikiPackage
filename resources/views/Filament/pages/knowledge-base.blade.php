<x-filament-panels::page>
    <div class="max-w-5xl py-2">
        <x-filament::breadcrumbs
            :breadcrumbs="$this->getLocalBreadcrumbs()"
            class="mb-2"
        />

        {{ $this->form }}

    </div>
</x-filament-panels::page>
