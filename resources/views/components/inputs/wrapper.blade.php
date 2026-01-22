@props(['label', 'name', 'required' => false])

<div class="mb-4">
    <label for="{{ $name }}" class="block  font-medium text-gray-700 mb-1">
        {{ $label }}
        @if($required) <span class="text-red-500">*</span> @endif
    </label>

    {{-- Aquí se inyecta el input específico (slot) --}}
    {{ $slot }}

    {{-- Lógica de Errores Unificada --}}
    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>