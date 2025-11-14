{{-- resources/views/vendor/cubewikipackage/filament/pages/knowledge-base.blade.php --}}
<x-filament-panels::page>
    <div class="max-w-5xl mx-auto py-2">
        {{-- Breadcrumbs boven de content - use local breadcrumbs to avoid duplicate rendering in Filament header --}}
        <x-filament::breadcrumbs
            :breadcrumbs="$this->getLocalBreadcrumbs()"
            class="mb-2"
        />

        {{-- Hierna gewoon je formulier / content --}}
        {{ $this->form }}
    </div>
</x-filament-panels::page>
