@props(['question', 'value', 'type' => 'date', 'name' => null])

@php
    $inputName = $name ?? "answers[{$question->id}]";
    $errorKey = str_replace(['[', ']'], ['.', ''], $inputName);

@endphp

<x-inputs.wrapper :label="$question->label" :name="$errorKey" :required="$question->is_required" :helper-text="$question->helper_text">
    <input type="{{ $type }}" name="{{ $inputName }}" id="{{ $errorKey }}" value="{{ $value }}"
        class="p-2 form-input w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        {{ $attributes }}>
</x-inputs.wrapper>
