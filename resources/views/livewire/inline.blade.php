@php
    /** @var string $slug */
    /** @var string $label */
@endphp

<span
    x-data
    x-on:click="Livewire.dispatch('cubewiki-open-help', '{{ $slug }}')"
    class="cursor-pointer hover:underline duration-300"
    style="color: var(--primary-500, var(--primary-color, currentColor));"
>
    {{ $label }}
</span>
