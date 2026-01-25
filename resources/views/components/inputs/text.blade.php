{{-- Agregamos 'name' => null para soportar sub-formularios --}}
@props(['question', 'value', 'type' => 'text', 'name' => null])

@php
    // 1. LÓGICA DE NOMBRE FLEXIBLE
    // Si nos pasan un nombre manual (desde un sub-form), lo usamos. Si no, el estándar.
    $inputName = $name ?? "answers[{$question->id}]";
    
    // Generar la llave de error (brackets a puntos)
    $errorKey = str_replace(['[', ']'], ['.', ''], $inputName);
    
    $codeTag = $question->options['code_tag'] ?? null;
@endphp

<x-inputs.wrapper :label="$question->label" :name="$errorKey" :required="$question->is_required" :helper-text="$question->helper_text">
    <input 
        type="{{ $type }}" 
        name="{{ $inputName }}" 
        id="{{ $errorKey }}"
        
        {{-- Usamos old() para no perder lo escrito si falla la validación --}}
        value="{{ old($errorKey, $value) }}"
        
        class="p-2 form-input w-full rounded-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 border-gray-300"
        
        {{-- 2. ETIQUETA PARA EL CALCULADOR --}}
        @if($codeTag) data-code-tag="{{ $codeTag }}" @endif
        
        {{-- 3. EVENTO CORREGIDO --}}
        {{-- Debe llamarse 'recalculate-code' para coincidir con system-code --}}
        x-on:input.debounce.300ms="$dispatch('recalculate-code')"
        
        {{ $attributes }} 
    >
</x-inputs.wrapper>