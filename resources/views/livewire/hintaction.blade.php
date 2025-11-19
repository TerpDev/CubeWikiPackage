{{-- resources/views/vendor/cubewikipackage/livewire/hintaction.blade.php --}}
<div class="inline-flex items-center gap-1">
    <x-filament::icon :icon="$icon" class="h-3 w-3 text-current" />

    <button
        type="button"
        wire:click="openBySlug('{{ $slug }}')"
        class="cursor-pointer text-xs font-medium hover:underline duration-300"
    >
        {{ $label }}
    </button>

    <x-filament-actions::modals />
</div>
