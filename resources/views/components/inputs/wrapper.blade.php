@props(['label', 'name', 'required' => false, 'helperText' => null])

<div {{ $attributes->merge(['class' => 'mb-2 ']) }}>

    {{-- --- AQUÍ MOSTRAMOS LA AYUDA --- --}}

    <label for="{{ $name }}" class="block font-medium text-gray-700 mb-1">
        {{ $label }}
        @if ($required)
            <span class="text-red-500">*</span>
        @endif
        @if ($helperText)
            <p class="mt-1 text-sm text-gray-500 flex items-start gap-1 block">
                {{-- Icono opcional de información (i) --}}
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mt-0.5 text-red-400" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{!! $helperText !!}</span>
            </p>
        @endif
    </label>

    {{ $slot }}

</div>

@error($name)
    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
@enderror
