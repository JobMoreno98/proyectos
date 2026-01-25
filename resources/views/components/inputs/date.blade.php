@props(['question', 'value', 'type' => 'date', 'name' => null])

@php
    $inputName = $name ?? "answers[{$question->id}]";
    $errorKey = str_replace(['[', ']'], ['.', ''], $inputName);

@endphp

<x-inputs.wrapper :label="$question->label" :name="$errorKey" :required="$question->is_required" :helper-text="$question->helper_text">
    <input type="{{ $type }}" name="{{ $inputName }}" id="{{ $errorKey }}" value="{{ $value }}"
        class="p-2 form-input w-full  text-center border border-stone-300 p-2 text-stone-900 border-gray-300 rounded-md shadow-sm focus:border-blue-500 
        focus:ring focus:ring-blue-200 w-full"
        {{ $attributes }}>
</x-inputs.wrapper>
