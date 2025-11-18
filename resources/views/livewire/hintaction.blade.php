{{-- resources/views/vendor/cubewikipackage/livewire/hintaction.blade.php --}}
<div class="inline-flex items-center gap-1">
    <button
        type="button"
        wire:click="openBySlug('{{ $slug }}')"
        class="cursor-pointer text-xs font-medium text-primary-500 hover:underline"
    >
        {{ $label ?? 'Hint' }}
    </button>

    <x-filament-actions::modals />
</div>
