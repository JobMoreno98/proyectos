{{-- Agregamos 'name' => null para soportar sub-formularios --}}
@props(['question', 'value', 'name' => null])

@php
    $inputName = $name ?? "answers[{$question->id}]";
    $errorKey = str_replace(['[', ']'], ['.', ''], $inputName);

@endphp

<x-inputs.wrapper class="flex justify-between" :label="$question->label" :name="$errorKey" :required="$question->is_required" :helper-text="$question->helper_text">
    <input type="number" name="{{ $inputName }}" id="{{ $errorKey }}" value="{{ $value }}" step="10"
        class="text-center border border-stone-300 p-2 text-stone-900 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 w-24">
</x-inputs.wrapper>
