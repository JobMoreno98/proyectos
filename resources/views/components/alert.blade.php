@php
    $styles = [
        'success' => 'bg-green-100 border-green-500 text-green-700',
        'error' => 'bg-red-100 border-red-500 text-red-700',
        'warning' => 'bg-yellow-100 border-yellow-500 text-yellow-700',
        'info' => 'bg-blue-100 border-blue-500 text-blue-700',
    ];
@endphp

<div x-data="{ show: true }" x-init="setTimeout(() => show = false, {{ $timeout }})" x-show="show" x-transition
    {{ $attributes->merge([
        'class' => "border-l-4 p-4 mb-6 rounded relative {$styles[$type]}",
    ]) }}>
    <span>{{ $slot }}</span>

    <!-- Botón cerrar -->
    <button type="button" @click="show = false"
        class="absolute right-3 top-1/2 -translate-y-1/2
               text-xl font-bold leading-none
               hover:opacity-70 focus:outline-none"
        aria-label="Cerrar">
        &times;
    </button>
</div>
